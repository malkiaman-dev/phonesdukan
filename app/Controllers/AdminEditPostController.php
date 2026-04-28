<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../Models/AdminPostModel.php';

class AdminEditPostController {
    private $postModel;

    public function __construct() {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header("Location: /admin/login.php");
            exit();
        }
        $this->postModel = new AdminPostModel();
    }

    public function index($post_id) {
        try {
            $post = $this->postModel->getPostById($post_id);
            if (!$post) {
                header("HTTP/1.0 404 Not Found");
                echo "<h1>404 - Post Not Found</h1>";
                exit();
            }
            error_log("Loaded post_id: $post_id for editing");
            $categories = $this->postModel->getAllCategories();
            $post_categories = $this->postModel->getPostCategories($post_id);
            $images = $this->postModel->getPostImages($post_id);
            $success = $_GET['success'] ?? null;
            $error = $_GET['error'] ?? null;
            require_once __DIR__ . '/../../admin/edit_post.php';
        } catch (Exception $e) {
            error_log("Index Error: " . $e->getMessage());
            header("HTTP/1.0 500 Internal Server Error");
            echo "<h1>500 - Server Error</h1><p>Failed to load post: " . htmlspecialchars($e->getMessage()) . "</p>";
            exit();
        }
    }

    public function update($post_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_GET['action']) || $_GET['action'] !== 'update') {
            $this->index($post_id);
            return;
        }

        error_log('POST Data: ' . print_r($_POST, true));
        error_log('FILES Data: ' . print_r($_FILES, true));

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'status' => trim($_POST['status'] ?? 'draft'),
            'updated_at' => trim($_POST['updated_at'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'categories' => !empty($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : [],
            'images' => [],
            'existing_images' => !empty($_POST['existing_images']) && is_array($_POST['existing_images']) ? $_POST['existing_images'] : [],
            'remove_image_ids' => !empty($_POST['remove_image_ids']) && is_array($_POST['remove_image_ids']) ? array_filter($_POST['remove_image_ids'], 'is_numeric') : [],
        ];

        $main_image_id = isset($_POST['main_image_id']) ? trim($_POST['main_image_id']) : '';
        error_log("main_image_id received: " . ($main_image_id ?: 'none'));

        $upload_dir = dirname(__DIR__, 2) . '/public/uploads/posts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_file_size = 5 * 1024 * 1024;

        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed_types)) {
                        $error = "Invalid file type for image: $name. Allowed types: " . implode(', ', $allowed_types);
                        error_log("Upload Error: $error");
                        $this->renderEditPage($post_id, $data, $error);
                        return;
                    }
                    if ($_FILES['images']['size'][$key] > $max_file_size) {
                        $error = "File size too large for image: $name. Max size: 5MB";
                        error_log("Upload Error: $error");
                        $this->renderEditPage($post_id, $data, $error);
                        return;
                    }

                    $original_name = pathinfo($name, PATHINFO_FILENAME);
                    $sanitized_name = preg_replace('/[^a-zA-Z0-9-_]/', '', str_replace(' ', '_', $original_name));
                    $filename = $sanitized_name . '.' . $ext;
                    $destination = $upload_dir . $filename;

                    if (file_exists($destination)) {
                        $filename = $sanitized_name . '_' . time() . '.' . $ext;
                        $destination = $upload_dir . $filename;
                    }

                    if (!move_uploaded_file($_FILES['images']['tmp_name'][$key], $destination)) {
                        $error = "Failed to upload image: $name";
                        error_log("Upload Error: $error");
                        $this->renderEditPage($post_id, $data, $error);
                        return;
                    }

                    $new_image_id = 'new_' . $key . '_' . time();
                    $is_main = ($main_image_id === $new_image_id);
                    error_log("New image ID: $new_image_id, is_main: " . ($is_main ? 'true' : 'false'));
                    $data['images'][] = [
                        'url' => '/public/uploads/posts/' . $filename,
                        'name' => $name,
                        'alt_text' => trim($_POST['images_alt_text'][$key] ?? ''),
                        'caption' => trim($_POST['images_caption'][$key] ?? ''),
                        'is_main' => $is_main,
                        'temp_id' => $new_image_id // Store temp ID for mapping
                    ];
                } elseif ($_FILES['images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                    $error = "Upload error for image: $name (Error code: {$_FILES['images']['error'][$key]})";
                    error_log("Upload Error: $error");
                    $this->renderEditPage($post_id, $data, $error);
                    return;
                }
            }
        }

        if (!empty($data['existing_images'])) {
            foreach ($data['existing_images'] as $image_id => &$image_data) {
                $image_data['is_main'] = ($main_image_id === (string)$image_id);
                $image_data['alt_text'] = trim($image_data['alt_text'] ?? '');
                $image_data['caption'] = trim($image_data['caption'] ?? '');
                error_log("Existing image ID: $image_id, is_main: " . ($image_data['is_main'] ? 'true' : 'false'));
            }
        }

        if (empty($data['title']) || empty($data['slug'])) {
            $error = "Title and slug are required.";
            error_log("Validation Error: $error");
            $this->renderEditPage($post_id, $data, $error);
            return;
        }

        try {
            $this->postModel->updatePost($post_id, $data);
            $seo_count = $this->postModel->getSeoRowCount($post_id);
            if ($seo_count > 1) {
                error_log("Warning: Multiple SEO rows detected for post_id $post_id");
            }
            error_log("Post $post_id updated successfully");
            ob_end_clean();
            header("Location: /admin/edit-post/$post_id?success=" . urlencode("Post updated successfully"));
            exit;
        } catch (Exception $e) {
            error_log("Update Error: " . $e->getMessage());
            $this->renderEditPage($post_id, $data, "Update failed: " . $e->getMessage());
        }
    }

    private function renderEditPage($post_id, $data, $error = null) {
        $post = $this->postModel->getPostById($post_id);
        if (!$post) {
            error_log("Post not found in renderEditPage: $post_id");
            header("HTTP/1.0 404 Not Found");
            echo "<h1>404 - Post Not Found</h1>";
            exit;
        }
        $categories = $this->postModel->getAllCategories();
        $post_categories = $this->postModel->getPostCategories($post_id);
        $images = $this->postModel->getPostImages($post_id);
        $success = null;
        error_log("Rendering edit page for post_id: $post_id with error: " . ($error ?? 'none'));
        require_once __DIR__ . '/../../admin/edit_post.php';
        ob_end_flush();
    }

    public function deleteImage($post_id, $image_id) {
        try {
            if ($this->postModel->deleteImage($image_id, $post_id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Image not found or not deleted']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}