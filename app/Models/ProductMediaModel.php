<?php

require_once dirname(__DIR__) . '/Helpers/ProductMediaHelper.php';

class ProductMediaModel
{
    private static bool $schemaReady = false;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        $this->db->exec("CREATE TABLE IF NOT EXISTS `product_videos` (
            `video_id` INT NOT NULL AUTO_INCREMENT,
            `product_id` INT NOT NULL,
            `video_source` ENUM('upload','youtube','tiktok','facebook','mp4') NOT NULL DEFAULT 'upload',
            `video_url` VARCHAR(500) NOT NULL,
            `thumbnail_url` VARCHAR(500) DEFAULT NULL,
            `custom_thumbnail_url` VARCHAR(500) DEFAULT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `status` TINYINT NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`video_id`),
            KEY `idx_product_videos_product` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $col = $this->db->query("SHOW COLUMNS FROM `product_images` LIKE 'sort_order'")->fetch(PDO::FETCH_ASSOC);
        if (!$col) {
            $this->db->exec('ALTER TABLE `product_images` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0');
        }

        $videoSourceCol = $this->db->query("SHOW COLUMNS FROM `product_videos` LIKE 'video_source'")->fetch(PDO::FETCH_ASSOC);
        if ($videoSourceCol && strpos((string) $videoSourceCol['Type'], 'tiktok') === false) {
            $this->db->exec("UPDATE `product_videos` SET `video_source` = 'mp4' WHERE `video_source` = 'vimeo'");
            $this->db->exec(
                "ALTER TABLE `product_videos` MODIFY `video_source`
                 ENUM('upload','youtube','tiktok','facebook','mp4') NOT NULL DEFAULT 'upload'"
            );
        }

        self::$schemaReady = true;
    }

    public function getProductVideo(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM product_videos WHERE product_id = ? AND status = 1 ORDER BY video_id DESC LIMIT 1'
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deleteProductVideo(int $productId): void
    {
        $video = $this->getProductVideo($productId);
        if (!$video) {
            return;
        }
        $this->deleteVideoFiles($video);
        $stmt = $this->db->prepare('DELETE FROM product_videos WHERE product_id = ?');
        $stmt->execute([$productId]);
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed>|null $previous
     */
    public function saveProductVideo(int $productId, array $data, ?array $previous = null): void
    {
        $existing = $previous ?? $this->getProductVideo($productId);

        if ($existing) {
            $videoChanged = ($existing['video_url'] ?? '') !== ($data['video_url'] ?? '')
                || ($existing['video_source'] ?? '') !== ($data['video_source'] ?? '');
            $thumbChanged = ($existing['custom_thumbnail_url'] ?? '') !== ($data['custom_thumbnail_url'] ?? '')
                && !empty($data['custom_thumbnail_url']);

            if ($videoChanged) {
                $this->deleteVideoFiles($existing);
            } elseif ($thumbChanged && !empty($existing['custom_thumbnail_url'])) {
                $this->deleteFilePath((string) $existing['custom_thumbnail_url']);
            }

            $stmt = $this->db->prepare(
                'UPDATE product_videos SET
                    video_source = ?,
                    video_url = ?,
                    thumbnail_url = ?,
                    custom_thumbnail_url = ?,
                    sort_order = ?,
                    status = 1
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['video_source'],
                $data['video_url'],
                $data['thumbnail_url'] ?? null,
                $data['custom_thumbnail_url'] ?? null,
                (int) ($data['sort_order'] ?? $existing['sort_order'] ?? 0),
                $productId,
            ]);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO product_videos
                (product_id, video_source, video_url, thumbnail_url, custom_thumbnail_url, sort_order, status)
             VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([
            $productId,
            $data['video_source'],
            $data['video_url'],
            $data['thumbnail_url'] ?? null,
            $data['custom_thumbnail_url'] ?? null,
            (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /**
     * @param array<int,array{type:string,id?:int,key?:string}> $orderItems
     * @param array<string,int> $keyToImageId
     */
    public function applyGalleryOrder(int $productId, array $orderItems, array $keyToImageId = []): void
    {
        foreach ($orderItems as $index => $item) {
            $sort = $index + 1;
            $type = $item['type'] ?? '';

            if ($type === 'image' && !empty($item['id'])) {
                $stmt = $this->db->prepare('UPDATE product_images SET sort_order = ? WHERE image_id = ? AND product_id = ?');
                $stmt->execute([$sort, (int) $item['id'], $productId]);
            } elseif ($type === 'image' && !empty($item['key']) && isset($keyToImageId[$item['key']])) {
                $stmt = $this->db->prepare('UPDATE product_images SET sort_order = ? WHERE image_id = ? AND product_id = ?');
                $stmt->execute([$sort, $keyToImageId[$item['key']], $productId]);
            } elseif ($type === 'video') {
                $stmt = $this->db->prepare('UPDATE product_videos SET sort_order = ? WHERE product_id = ?');
                $stmt->execute([$sort, $productId]);
            }
        }
    }

    /**
     * Merged gallery items for the storefront product page.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getFrontendGalleryMedia(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT pi.image_id, pi.image_url, pi.is_primary, pi.sort_order,
                    im.alt_text, im.title, im.description, im.caption
             FROM product_images pi
             LEFT JOIN image_metadata im ON pi.image_id = im.image_id
             WHERE pi.product_id = ?
             ORDER BY pi.sort_order ASC, pi.is_primary DESC, pi.image_id ASC'
        );
        $stmt->execute([$productId]);
        $items = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $img) {
            $items[] = [
                'type' => 'image',
                'sort_order' => (int) ($img['sort_order'] ?? 0),
                'image_url' => $img['image_url'],
                'alt_text' => $img['alt_text'] ?? '',
                'title' => $img['title'] ?? '',
                'description' => $img['description'] ?? '',
                'caption' => $img['caption'] ?? '',
                'is_primary' => (int) ($img['is_primary'] ?? 0),
            ];
        }

        $video = $this->getProductVideo($productId);
        if ($video) {
            $thumb = $video['custom_thumbnail_url'] ?: $video['thumbnail_url'];
            $source = $video['video_source'] ?? 'upload';
            $videoUrl = $video['video_url'] ?? '';
            $items[] = [
                'type' => 'video',
                'sort_order' => (int) ($video['sort_order'] ?? 0),
                'video_source' => $source,
                'video_url' => $videoUrl,
                'embed_url' => ProductMediaHelper::buildEmbedUrl($videoUrl, $source),
                'thumbnail_url' => $thumb,
                'alt_text' => 'Product video',
            ];
        }

        usort($items, function ($a, $b) {
            $ao = (int) ($a['sort_order'] ?? 0);
            $bo = (int) ($b['sort_order'] ?? 0);
            if ($ao === $bo) {
                return ($a['type'] === 'video') <=> ($b['type'] === 'video');
            }
            if ($ao === 0 && $bo === 0) {
                return 0;
            }
            if ($ao === 0) {
                return 1;
            }
            if ($bo === 0) {
                return -1;
            }
            return $ao <=> $bo;
        });

        return $items;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getGalleryOrderItems(int $productId): array
    {
        $items = [];

        $imgStmt = $this->db->prepare(
            'SELECT image_id, image_url, is_primary, sort_order
             FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, image_id ASC'
        );
        $imgStmt->execute([$productId]);
        $imageIndex = 0;
        foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $img) {
            $imageIndex++;
            $items[] = [
                'type' => 'image',
                'id' => (int) $img['image_id'],
                'label' => 'Image ' . $imageIndex,
                'preview' => $img['image_url'],
                'sort_order' => (int) $img['sort_order'],
                'is_primary' => (int) $img['is_primary'],
            ];
        }

        $video = $this->getProductVideo($productId);
        if ($video) {
            $thumb = $video['custom_thumbnail_url'] ?: $video['thumbnail_url'];
            $items[] = [
                'type' => 'video',
                'id' => (int) $video['video_id'],
                'label' => 'Video',
                'preview' => $thumb,
                'sort_order' => (int) $video['sort_order'],
                'video_source' => $video['video_source'],
                'video_url' => $video['video_url'],
            ];
        }

        usort($items, function ($a, $b) {
            $ao = (int) ($a['sort_order'] ?? 0);
            $bo = (int) ($b['sort_order'] ?? 0);
            if ($ao === $bo) {
                return ($a['type'] === 'video') <=> ($b['type'] === 'video');
            }
            if ($ao === 0 && $bo === 0) {
                return 0;
            }
            if ($ao === 0) {
                return 1;
            }
            if ($bo === 0) {
                return -1;
            }
            return $ao <=> $bo;
        });

        return $items;
    }

    /**
     * @param array<string,mixed> $video
     */
    private function deleteVideoFiles(array $video): void
    {
        foreach (['video_url', 'thumbnail_url', 'custom_thumbnail_url'] as $field) {
            if (!empty($video[$field])) {
                $this->deleteFilePath((string) $video[$field]);
            }
        }
    }

    private function deleteFilePath(string $path): void
    {
        if (preg_match('/^https?:\/\//i', $path)) {
            return;
        }
        $root = dirname(__DIR__, 2);
        $relative = ltrim(str_replace(['/public/', 'public/'], '', $path), '/');
        $full = $root . '/public/' . $relative;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
