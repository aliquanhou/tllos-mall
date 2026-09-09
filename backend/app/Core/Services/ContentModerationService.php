<?php
namespace App\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ContentModerationService
{
    private static $words = null;

    /**
     * 检查内容是否包含敏感词
     */
    public static function check($content)
    {
        if (empty($content)) return ['safe' => true, 'words' => []];

        $words = self::getSensitiveWords();
        $found = [];
        foreach ($words as $word) {
            if (mb_strpos($content, $word['word']) !== false) {
                $found[] = $word;
            }
        }

        return [
            'safe' => empty($found),
            'words' => $found,
            'has_block' => !empty(array_filter($found, fn($w) => $w['level'] == 2)),
        ];
    }

    /**
     * 过滤敏感词（替换为***）
     */
    public static function filter($content)
    {
        $words = self::getSensitiveWords();
        foreach ($words as $word) {
            $content = str_replace($word['word'], str_repeat('*', mb_strlen($word['word'])), $content);
        }
        return $content;
    }

    /**
     * 获取敏感词列表（缓存）
     */
    private static function getSensitiveWords()
    {
        if (self::$words === null) {
            try {
                self::$words = Cache::remember('sensitive_words', 3600, function () {
                    return DB::table('sensitive_words')->where('status', 1)->get()->toArray();
                });
            } catch (\Exception $e) {
                self::$words = [];
            }
        }
        return self::$words;
    }

    /**
     * 清除缓存
     */
    public static function clearCache()
    {
        self::$words = null;
        try { Cache::forget('sensitive_words'); } catch (\Exception $e) {}
    }
}
