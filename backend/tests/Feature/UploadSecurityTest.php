<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * P0-A Upload Security Test Matrix
 */
class UploadSecurityTest extends TestCase
{
    /**
     * 生成合法 PNG 图片
     */
    private function validPng(): UploadedFile
    {
        return UploadedFile::fake()->image('test.png', 1, 1);
    }

    /**
     * 生成合法 JPEG 图片
     */
    private function validJpeg(): UploadedFile
    {
        return UploadedFile::fake()->image('test.jpg', 1, 1);
    }

    /**
     * 生成 PHP 内容文件（危险类型）
     */
    private function phpFile(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_') . '.php';
        file_put_contents($tmp, '<?php echo "hacked"; ?>');
        return new UploadedFile($tmp, 'test.php', 'application/x-httpd-php', null, true);
    }

    /**
     * 生成 HTML 内容文件（危险类型）
     */
    private function htmlFile(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_') . '.html';
        file_put_contents($tmp, '<script>alert("xss")</script>');
        return new UploadedFile($tmp, 'test.html', 'text/html', null, true);
    }

    /**
     * 生成 SVG 内容文件（危险类型）
     */
    private function svgFile(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_') . '.svg';
        file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script></svg>');
        return new UploadedFile($tmp, 'test.svg', 'image/svg+xml', null, true);
    }

    /**
     * 生成空文件
     */
    private function emptyFile(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        file_put_contents($tmp, '');
        return new UploadedFile($tmp, 'empty.txt', 'text/plain', null, true);
    }

    /**
     * 获取测试用户（不创建新用户，使用现有用户）
     */
    private function getTestUser(): ?User
    {
        return User::first();
    }

    // ============================================================
    // 未授权测试（全部应返回 401）
    // ============================================================

    public function test_unauthenticated_upload_jpg_returns_401(): void
    {
        $response = $this->postJson('/api/v1/upload', ['file' => $this->validJpeg()]);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_upload_php_returns_401(): void
    {
        $response = $this->postJson('/api/v1/upload', ['file' => $this->phpFile()]);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_upload_html_returns_401(): void
    {
        $response = $this->postJson('/api/v1/upload', ['file' => $this->htmlFile()]);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_upload_svg_returns_401(): void
    {
        $response = $this->postJson('/api/v1/upload', ['file' => $this->svgFile()]);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_upload_image_returns_401(): void
    {
        $response = $this->postJson('/api/v1/upload/image', ['file' => $this->validPng()]);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_upload_video_returns_401(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_') . '.mp4';
        file_put_contents($tmp, 'fake video');
        $file = new UploadedFile($tmp, 'test.mp4', 'video/mp4', null, true);

        $response = $this->postJson('/api/v1/upload/video', ['file' => $file]);
        $response->assertStatus(401);
    }

    // ============================================================
    // 授权后危险类型测试（全部应返回 422）
    // ============================================================

    public function test_authenticated_upload_php_rejected(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload', ['file' => $this->phpFile()]);
        $response->assertStatus(422);
    }

    public function test_authenticated_upload_html_rejected(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload', ['file' => $this->htmlFile()]);
        $response->assertStatus(422);
    }

    public function test_authenticated_upload_svg_rejected(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload/image', ['file' => $this->svgFile()]);
        $response->assertStatus(422);
    }

    public function test_authenticated_empty_file_rejected(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload', ['file' => $this->emptyFile()]);
        $response->assertStatus(422);
    }

    public function test_mime_spoofing_php_as_image_rejected(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        // PHP 内容但客户端 MIME 伪装为 image/png
        $tmp = tempnam(sys_get_temp_dir(), 'test_') . '.png';
        file_put_contents($tmp, '<?php echo "hacked"; ?>');
        $file = new UploadedFile($tmp, 'fake.png', 'image/png', null, true);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload/image', ['file' => $file]);
        $response->assertStatus(422);
    }

    // ============================================================
    // 授权后合法文件测试（应返回 200）
    // ============================================================

    public function test_authenticated_upload_valid_png_success(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload/image', ['file' => $this->validPng()]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data['url']);
        $this->assertStringEndsWith('.png', $data['url']);
        $this->assertEquals('image/png', $data['type']);
        $this->assertEquals('png', $data['ext']);
    }

    public function test_authenticated_upload_valid_jpeg_success(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload/image', ['file' => $this->validJpeg()]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertStringEndsWith('.jpg', $data['url']);
        $this->assertEquals('image/jpeg', $data['type']);
    }

    public function test_generic_upload_auto_detects_image(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload', ['file' => $this->validPng()]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertStringContainsString('/images/', $data['url']);
    }

    // ============================================================
    // 文件名安全测试
    // ============================================================

    public function test_uploaded_filename_is_random_not_original(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $file = UploadedFile::fake()->image('my_special_filename_123.png', 1, 1);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload/image', ['file' => $file]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertStringNotContainsString('my_special_filename', $data['url']);
        $this->assertEquals('my_special_filename_123.png', $data['name']);
    }

    public function test_double_extension_renamed_to_safe_ext(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        // 图片内容但扩展名为 .jpg.php，服务端应忽略客户端扩展名，根据真实MIME生成安全扩展名
        $file = UploadedFile::fake()->image('test.jpg.php', 1, 1);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload', ['file' => $file]);

        $response->assertStatus(200);
        $data = $response->json('data');
        // 服务端生成的扩展名必须是安全的图片扩展名，不能是 .php
        $this->assertTrue(
            str_ends_with($data['url'], '.png') || str_ends_with($data['url'], '.jpg') || str_ends_with($data['url'], '.jpeg'),
            'URL must end with safe image extension, got: ' . $data['url']
        );
        $this->assertStringNotContainsString('.php', $data['url']);
    }

    public function test_path_traversal_filename_sanitized(): void
    {
        $user = $this->getTestUser();
        if (!$user) { $this->markTestSkipped('No test user'); }

        $file = UploadedFile::fake()->image('../../etc/passwd.png', 1, 1);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/upload/image', ['file' => $file]);

        if ($response->status() === 200) {
            $data = $response->json('data');
            $this->assertStringNotContainsString('..', $data['url']);
            $this->assertStringNotContainsString('etc/passwd', $data['url']);
        } else {
            $response->assertStatus(422);
        }
    }
}
