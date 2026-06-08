<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once dirname(__DIR__) . '/app/Helpers/ProductMediaHelper.php';

$url = trim($_POST['video_url'] ?? $_GET['video_url'] ?? '');
$source = trim($_POST['video_source'] ?? $_GET['video_source'] ?? '');

if ($url === '') {
    echo json_encode(['success' => false, 'message' => 'Video URL is required.']);
    exit();
}

$sourceMap = [
    'youtube' => 'youtube',
    'vimeo' => 'vimeo',
    'mp4' => 'mp4',
    'external_mp4' => 'mp4',
];
$expected = $sourceMap[$source] ?? null;

$validation = ProductMediaHelper::validateVideoUrl($url, $expected);
if (!$validation['valid']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit();
}

$resolvedSource = $validation['source'];
$normalizedUrl = $validation['normalized_url'];
$thumbnail = ProductMediaHelper::getRemoteThumbnailUrl($normalizedUrl, $resolvedSource);

echo json_encode([
    'success' => true,
    'video_url' => $normalizedUrl,
    'video_source' => $resolvedSource,
    'thumbnail_url' => $thumbnail,
    'embed_url' => self_buildEmbedUrl($normalizedUrl, $resolvedSource),
]);

function self_buildEmbedUrl(string $url, string $source): string
{
    if ($source === 'youtube') {
        $id = ProductMediaHelper::extractYouTubeId($url);
        return $id ? 'https://www.youtube.com/embed/' . $id : $url;
    }
    if ($source === 'vimeo') {
        $id = ProductMediaHelper::extractVimeoId($url);
        return $id ? 'https://player.vimeo.com/video/' . $id : $url;
    }
    return $url;
}
