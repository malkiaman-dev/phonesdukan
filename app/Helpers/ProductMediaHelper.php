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

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        $source = null;
        if (in_array($host, ['youtube.com', 'youtu.be', 'm.youtube.com'], true)) {
            $source = 'youtube';
        } elseif (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            $source = 'vimeo';
        } elseif (preg_match('/\.mp4(\?|$)/i', $path) || preg_match('/\.mp4(\?|$)/i', $url)) {
            $source = 'mp4';
        }

        if ($source === null) {
            return [
                'valid' => false,
                'message' => 'Unsupported URL. Use YouTube, Vimeo, or a direct MP4 link.',
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

        if ($source === 'vimeo') {
            $id = self::extractVimeoId($url);
            return $id ? 'https://vimeo.com/' . $id : $url;
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

    public static function extractVimeoId(string $url): ?string
    {
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * @return string|null Public URL path for auto-generated thumbnail
     */
    public static function buildEmbedUrl(string $videoUrl, string $source): string
    {
        if ($source === 'youtube') {
            $id = self::extractYouTubeId($videoUrl);
            return $id ? 'https://www.youtube.com/embed/' . $id : $videoUrl;
        }

        if ($source === 'vimeo') {
            $id = self::extractVimeoId($videoUrl);
            return $id ? 'https://player.vimeo.com/video/' . $id : $videoUrl;
        }

        return $videoUrl;
    }

    public static function getRemoteThumbnailUrl(string $videoUrl, string $source): ?string
    {
        if ($source === 'youtube') {
            $id = self::extractYouTubeId($videoUrl);
            return $id ? 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg' : null;
        }

        if ($source === 'vimeo') {
            $id = self::extractVimeoId($videoUrl);
            if (!$id) {
                return null;
            }
            $oembed = @file_get_contents('https://vimeo.com/api/oembed.json?url=' . urlencode('https://vimeo.com/' . $id));
            if ($oembed) {
                $data = json_decode($oembed, true);
                if (!empty($data['thumbnail_url'])) {
                    return $data['thumbnail_url'];
                }
            }
        }

        return null;
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
