<?php

require_once dirname(__DIR__) . '/Helpers/ProductMediaHelper.php';
require_once dirname(__DIR__) . '/Models/ProductMediaModel.php';

class ProductMediaService
{
    private ProductMediaModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new ProductMediaModel($db);
        $this->model->ensureSchema();
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     */
    public function saveFromRequest(int $productId, array $post, array $files): void
    {
        if (!empty($post['remove_video_flag']) && $post['remove_video_flag'] === '1') {
            $this->model->deleteProductVideo($productId);
            return;
        }

        if (empty($post['has_product_video']) || $post['has_product_video'] !== '1') {
            return;
        }

        $existing = $this->model->getProductVideo($productId);

        $source = $post['video_source'] ?? 'upload';
        if (!in_array($source, ['upload', 'youtube', 'vimeo', 'mp4'], true)) {
            $source = 'upload';
        }

        $videoUrl = '';
        $thumbnailUrl = trim($post['video_thumbnail_path'] ?? '') ?: null;

        if ($source === 'upload') {
            $videoUrl = trim($post['video_uploaded_path'] ?? '');
            if ($videoUrl === '') {
                return;
            }
        } else {
            $url = trim($post['video_url_hidden'] ?? $post['video_url'] ?? '');
            $validation = ProductMediaHelper::validateVideoUrl($url, $source);
            if (!$validation['valid']) {
                return;
            }
            $videoUrl = $validation['normalized_url'];
            if (!$thumbnailUrl) {
                $thumbnailUrl = ProductMediaHelper::getRemoteThumbnailUrl($videoUrl, $validation['source']);
            }
        }

        $customThumb = $this->resolveCustomThumbnail($post, $files, $existing);

        if ($source === 'upload' && $videoUrl === '' && $existing) {
            $videoUrl = $existing['video_url'];
            $source = $existing['video_source'];
            if (!$thumbnailUrl) {
                $thumbnailUrl = $existing['thumbnail_url'] ?? null;
            }
        }

        $this->model->saveProductVideo($productId, [
            'video_source' => $source,
            'video_url' => $videoUrl,
            'thumbnail_url' => $thumbnailUrl,
            'custom_thumbnail_url' => $customThumb,
            'sort_order' => (int) ($existing['sort_order'] ?? 0),
        ], $existing);
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @param array<string,mixed>|null $existing
     */
    private function resolveCustomThumbnail(array $post, array $files, ?array $existing): ?string
    {
        if (!empty($post['remove_custom_thumbnail_flag']) && $post['remove_custom_thumbnail_flag'] === '1') {
            return null;
        }

        $ajaxPath = trim($post['video_custom_thumbnail_path'] ?? '');
        if ($ajaxPath !== '') {
            return $ajaxPath;
        }

        $uploaded = $this->handleCustomThumbnailUpload($files);
        if ($uploaded !== null) {
            return $uploaded;
        }

        return $existing['custom_thumbnail_url'] ?? null;
    }

    /**
     * @param array<string,mixed> $files
     */
    private function handleCustomThumbnailUpload(array $files): ?string
    {
        if (empty($files['video_custom_thumbnail']['tmp_name'])
            || $files['video_custom_thumbnail']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $thumbDir = dirname(__DIR__, 2) . '/public/uploads/products/videos/thumbnails/';
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $ext = strtolower(pathinfo($files['video_custom_thumbnail']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ProductMediaHelper::ALLOWED_THUMB_EXTENSIONS, true)) {
            return null;
        }

        $filename = 'custom_' . uniqid() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $dest = ProductMediaHelper::uniquePath($thumbDir, $filename);
        if (!move_uploaded_file($files['video_custom_thumbnail']['tmp_name'], $dest)) {
            return null;
        }

        return '/public/uploads/products/videos/thumbnails/' . basename($dest);
    }

    /**
     * @param array<string,int> $keyToImageId
     */
    public function applyGalleryOrder(int $productId, string $orderJson, array $keyToImageId = []): void
    {
        $decoded = json_decode($orderJson, true);
        if (!is_array($decoded)) {
            return;
        }
        $this->model->applyGalleryOrder($productId, $decoded, $keyToImageId);
    }

    public function getModel(): ProductMediaModel
    {
        return $this->model;
    }
}
