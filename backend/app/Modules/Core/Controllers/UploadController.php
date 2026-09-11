<?php

namespace App\Modules\Core\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 文件上传控制器（安全加固版）
 *
 * 安全策略：
 * 1. 必须登录（auth:sanctum）
 * 2. 服务端 finfo 检测真实文件类型（不信任客户端 Content-Type）
 * 3. MIME 白名单（图片 + 视频）
 * 4. 扩展名由服务端根据真实 MIME 生成（不使用客户端扩展名）
 * 5. 文件名由服务端随机生成
 * 6. 路径穿越防护
 * 7. 空文件 / 超大文件拦截
 */
class UploadController extends BaseController
{
    /**
     * 允许的 MIME 类型 -> 安全扩展名映射
     */
    private const ALLOWED_MIME_MAP = [
        // 图片
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        // 视频
        'video/mp4'        => 'mp4',
        'video/quicktime'  => 'mov',
    ];

    /**
     * 图片 MIME 列表
     */
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * 视频 MIME 列表
     */
    private const VIDEO_MIMES = ['video/mp4', 'video/quicktime'];

    /**
     * 通用上传（自动识别图片/视频）
     * 最大 100MB
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400',
        ]);

        return $this->processUpload($request->file('file'), 'auto');
    }

    /**
     * 图片上传
     * 最大 10MB
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        return $this->processUpload($request->file('file'), 'image');
    }

    /**
     * 视频上传
     * 最大 100MB
     */
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400',
        ]);

        return $this->processUpload($request->file('file'), 'video');
    }

    /**
     * 统一上传处理（服务端安全校验）
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $typeConstraint auto / image / video
     * @return \Illuminate\Http\JsonResponse
     */
    private function processUpload($file, string $typeConstraint)
    {
        // 1. 空文件检查
        if ($file->getSize() === 0) {
            return $this->error('文件不能为空', 422);
        }

        // 2. 服务端真实 MIME 检测（使用 finfo，不信任客户端 Content-Type）
        $realMime = $this->detectRealMime($file->getRealPath());
        if ($realMime === null) {
            return $this->error('无法识别文件类型', 422);
        }

        // 3. MIME 白名单校验
        if (!isset(self::ALLOWED_MIME_MAP[$realMime])) {
            return $this->error('不支持的文件类型: ' . $realMime, 422);
        }

        // 4. 类型约束校验
        if ($typeConstraint === 'image' && !in_array($realMime, self::IMAGE_MIMES, true)) {
            return $this->error('仅支持图片文件', 422);
        }
        if ($typeConstraint === 'video' && !in_array($realMime, self::VIDEO_MIMES, true)) {
            return $this->error('仅支持视频文件', 422);
        }

        // 5. 服务端生成安全扩展名（根据真实 MIME，不使用客户端扩展名）
        $safeExt = self::ALLOWED_MIME_MAP[$realMime];

        // 6. 服务端生成随机文件名（日期目录 + 随机字符串 + 安全扩展名）
        $folder = in_array($realMime, self::VIDEO_MIMES, true) ? 'videos' : 'images';
        $randomName = Str::random(40);
        $relativePath = 'uploads/' . $folder . '/' . date('Ymd') . '/' . $randomName . '.' . $safeExt;

        // 7. 路径穿越防护（双重检查）
        if ($this->containsPathTraversal($relativePath)) {
            return $this->error('非法文件路径', 422);
        }

        // 8. 存储文件（明确使用 public 磁盘，确保文件公开可访问）
        $stored = $file->storeAs(dirname($relativePath), basename($relativePath), 'public');
        if ($stored === false) {
            return $this->error('文件存储失败', 500);
        }

        // 9. 验证存储后的文件仍然是允许的类型（防止 TOCTOU）
        $storedPath = storage_path('app/public/' . $relativePath);
        if (file_exists($storedPath)) {
            $storedMime = $this->detectRealMime($storedPath);
            if ($storedMime !== $realMime) {
                // 存储后类型发生变化，删除并拒绝
                @unlink($storedPath);
                return $this->error('文件类型校验失败', 422);
            }
        }

        $url = '/storage/' . $relativePath;

        return $this->success([
            'url'      => $url,
            'full_url' => config('app.url') . $url,
            'name'     => $file->getClientOriginalName(),
            'size'     => $file->getSize(),
            'type'     => $realMime,
            'ext'      => $safeExt,
        ]);
    }

    /**
     * 使用 finfo 检测文件真实 MIME 类型
     * 不信任客户端上传时的 Content-Type 头
     *
     * @param string $filePath
     * @return string|null
     */
    private function detectRealMime(string $filePath): ?string
    {
        if (!function_exists('finfo_open')) {
            // finfo 扩展不可用时回退到 PHP 内置 mime_content_type
            $mime = @mime_content_type($filePath);
            return $mime !== false ? $mime : null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mime !== false ? $mime : null;
    }

    /**
     * 检查路径是否包含穿越字符
     *
     * @param string $path
     * @return bool
     */
    private function containsPathTraversal(string $path): bool
    {
        $patterns = ['..', '\\', "\0", '%2e%2e', '%2E%2E'];
        foreach ($patterns as $pattern) {
            if (stripos($path, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}
