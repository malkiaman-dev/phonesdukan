<?php

class ProductMediaHelper
{
    public const VIDEO_MAX_BYTES = 104857600; // 100 MB

    public const ALLOWED_VIDEO_MIMES = [
        'video/mp4',
        'video/webm',
        'video/quicktime', // .mov
    ];

    public const ALLOWED_THUMB_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const VIDEO_SOURCES = ['upload', 'youtube', 'tiktok', 'facebook', 'mp4'];

    /**
     * @return array{valid:bool,source?:string,message?:string,normalized_url?:string}
     */
    public static function validateVideoUrl(string $url, ?string $expectedSource = null): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['valid' => false, 'message' => 'Video URL is required.'];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'message' => 'Please enter a valid URL.'];
        }

        $source = self::detectVideoSource($url);
        if ($source === null) {
            return [
                'valid' => false,
                'message' => 'Unsupported URL. Use YouTube, TikTok, Facebook, or a direct MP4 link.',
            ];
        }

        if ($expectedSource !== null && $source !== $expectedSource) {
            return [
                'valid' => false,
                'message' => 'URL does not match the selected video source.',
            ];
        }

        return [
            'valid' => true,
            'source' => $source,
            'normalized_url' => self::normalizeVideoUrl($url, $source),
        ];
    }

    public static function detectVideoSource(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        if (in_array($host, ['youtube.com', 'youtu.be', 'm.youtube.com'], true)) {
            return 'youtube';
        }

        if (in_array($host, ['tiktok.com', 'vm.tiktok.com', 'vt.tiktok.com'], true)) {
            return 'tiktok';
        }

        if (in_array($host, ['facebook.com', 'm.facebook.com', 'web.facebook.com', 'fb.watch'], true)) {
            return 'facebook';
        }

        if (preg_match('/\.mp4(\?|$)/i', $path) || preg_match('/\.mp4(\?|$)/i', $url)) {
            return 'mp4';
        }

        return null;
    }

    public static function normalizeVideoUrl(string $url, ?string $source = null): string
    {
        $url = trim($url);
        if ($source === null) {
            $check = self::validateVideoUrl($url);
            $source = $check['source'] ?? 'mp4';
        }

        if ($source === 'youtube') {
            $id = self::extractYouTubeId($url);
            return $id ? 'https://www.youtube.com/watch?v=' . $id : $url;
        }

        if ($source === 'tiktok') {
            $id = self::extractTikTokId($url);
            if ($id) {
                return 'https://www.tiktok.com/video/' . $id;
            }
            return $url;
        }

        if ($source === 'facebook') {
            return $url;
        }

        return $url;
    }

    public static function extractYouTubeId(string $url): ?string
    {
        if (preg_match('/youtu\.be\/([A-Za-z0-9_-]{6,})/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/[?&]v=([A-Za-z0-9_-]{6,})/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/youtube\.com\/embed\/([A-Za-z0-9_-]{6,})/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/youtube\.com\/shorts\/([A-Za-z0-9_-]{6,})/', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function extractTikTokId(string $url): ?string
    {
        if (preg_match('/\/video\/(\d+)/', $url, $m)) {
            return $m[1];
        }

        $oembed = self::fetchOembed('https://www.tiktok.com/oembed?url=' . urlencode($url));
        if (!empty($oembed['embed_product_id'])) {
            return (string) $oembed['embed_product_id'];
        }

        return null;
    }

    public static function buildEmbedUrl(string $videoUrl, string $source): string
    {
        if ($source === 'youtube') {
            $id = self::extractYouTubeId($videoUrl);
            return $id ? 'https://www.youtube.com/embed/' . $id : $videoUrl;
        }

        if ($source === 'tiktok') {
            $id = self::extractTikTokId($videoUrl);
            return $id ? 'https://www.tiktok.com/embed/v2/' . $id : $videoUrl;
        }

        if ($source === 'facebook') {
            return 'https://www.facebook.com/plugins/video.php?href='
                . urlencode($videoUrl)
                . '&show_text=false&width=560';
        }

        return $videoUrl;
    }

    public static function getRemoteThumbnailUrl(string $videoUrl, string $source): ?string
    {
        $remote = null;

        if ($source === 'youtube') {
            $id = self::extractYouTubeId($videoUrl);
            $remote = $id ? 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg' : null;
        } elseif ($source === 'tiktok') {
            $oembed = self::fetchOembed('https://www.tiktok.com/oembed?url=' . urlencode($videoUrl));
            $remote = $oembed['thumbnail_url'] ?? null;
        } elseif ($source === 'facebook') {
            $oembed = self::fetchOembed(
                'https://www.facebook.com/plugins/video/oembed.json/?url=' . urlencode($videoUrl)
            );
            $remote = $oembed['thumbnail_url'] ?? null;
        }

        if ($remote === null) {
            return null;
        }

        return self::cacheRemoteThumbnail($remote, $source . '_thumb') ?? $remote;
    }

    /**
     * Download a remote thumbnail and store it locally so signed CDN URLs keep working.
     */
    public static function cacheRemoteThumbnail(string $remoteUrl, string $prefix = 'remote'): ?string
    {
        $remoteUrl = trim($remoteUrl);
        if ($remoteUrl === '' || !preg_match('#^https?://#i', $remoteUrl)) {
            return null;
        }

        $thumbDir = dirname(__DIR__, 2) . '/public/uploads/products/videos/thumbnails/';
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 12,
                'header' => "User-Agent: Mozilla/5.0 (compatible; PhonesDukan/1.0)\r\nAccept: image/*\r\n",
            ],
        ]);
        $data = @file_get_contents($remoteUrl, false, $context);
        if ($data === false || $data === '') {
            return null;
        }

        $ext = 'jpg';
        $path = (string) parse_url($remoteUrl, PHP_URL_PATH);
        if (preg_match('/\.(jpe?g|png|webp)$/i', $path, $m)) {
            $ext = strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
        }

        $filename = preg_replace('/[^a-z0-9_-]+/i', '_', $prefix) . '_' . uniqid('', true) . '.' . $ext;
        $dest = self::uniquePath($thumbDir, $filename);
        if (@file_put_contents($dest, $data) === false) {
            return null;
        }

        return '/public/uploads/products/videos/thumbnails/' . basename($dest);
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function fetchOembed(string $endpoint): ?array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header' => "User-Agent: Mozilla/5.0 (compatible; PhonesDukan/1.0)\r\nAccept: application/json\r\n",
            ],
        ]);
        $raw = @file_get_contents($endpoint, false, $context);
        if (!$raw) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public static function sanitizeFilename(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($base)));
        $base = trim($base, '-') ?: 'file';
        return $ext !== '' ? $base . '.' . $ext : $base;
    }

    public static function uniquePath(string $dir, string $filename): string
    {
        $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($path)) {
            return $path;
        }
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $counter = 1;
        do {
            $candidate = $name . '-' . $counter . ($ext ? '.' . $ext : '');
            $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $candidate;
            $counter++;
        } while (file_exists($path));
        return $path;
    }

    /**
     * Try to extract a poster frame with ffmpeg when available.
     */
    public static function generateVideoThumbnail(string $videoPath, string $thumbDir): ?string
    {
        if (!is_file($videoPath)) {
            return null;
        }

        $ffmpeg = self::findFfmpegBinary();
        if ($ffmpeg === null) {
            return null;
        }

        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $filename = 'thumb_' . uniqid() . '.jpg';
        $thumbPath = self::uniquePath($thumbDir, $filename);
        $cmd = escapeshellarg($ffmpeg)
            . ' -y -i ' . escapeshellarg($videoPath)
            . ' -ss 00:00:01 -vframes 1 -q:v 2 '
            . escapeshellarg($thumbPath)
            . ' 2>&1';

        @exec($cmd, $output, $code);
        if ($code !== 0 || !is_file($thumbPath)) {
            return null;
        }

        return $thumbPath;
    }

    private static function findFfmpegBinary(): ?string
    {
        $candidates = ['ffmpeg', 'ffmpeg.exe'];
        foreach ($candidates as $bin) {
            $which = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'which';
            @exec($which . ' ' . escapeshellarg($bin), $out, $code);
            if ($code === 0 && !empty($out[0]) && is_executable(trim($out[0]))) {
                return trim($out[0]);
            }
        }
        return null;
    }
}
