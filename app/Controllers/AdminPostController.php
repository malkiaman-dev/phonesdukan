<?php
session_start();
require_once __DIR__ . '/../Models/AdminPostModel.php';

class AdminPostController {
    private $postModel;

    public function __construct() {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header("Location: /admin/login.php");
            exit();
        }
        $this->postModel = new AdminPostModel();
    }

    public function index() {
        $categories = $this->postModel->getAllCategories();
        require_once __DIR__ . '/../../admin/add_post.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'add') {
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'excerpt' => trim($_POST['excerpt'] ?? ''),
                'content' => trim($_POST['content'] ?? ''),
                'status' => trim($_POST['status'] ?? 'draft'),
                'published_at' => trim($_POST['published_at'] ?? '') ?: ($data['status'] === 'published' ? date('Y-m-d H:i:s') : null),
                'meta_title' => trim($_POST['meta_title'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
                'canonical_url' => trim($_POST['canonical_url'] ?? ''),
                'categories' => $_POST['categories'] ?? [],
                'images' => []
            ];

            // Handle main image upload
            if (!empty($_FILES['main_image']['name']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = dirname(__DIR__, 2) . '/public/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $original_name = pathinfo($_FILES['main_image']['name'], PATHINFO_FILENAME);
                $ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
                $sanitized_name = preg_replace('/[^a-zA-Z0-9-_]/', '', str_replace(' ', '_', $original_name));
                $filename = $sanitized_name . '.' . strtolower($ext);
                $destination = $upload_dir . $filename;
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array(strtolower($ext), $allowed_types)) {
                    $error = "Main image must be a JPG, JPEG, PNG, GIF, or WebP.";
                    $categories = $this->postModel->getAllCategories();
                    require_once __DIR__ . '/../../admin/add_post.php';
                    return;
                }

                if (file_exists($destination)) {
                    $filename = $sanitized_name . '_' . time() . '.' . strtolower($ext);
                    $destination = $upload_dir . $filename;
                }

                if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $destination)) {
                    $error = "Failed to upload main image. Check directory permissions.";
                    $categories = $this->postModel->getAllCategories();
                    require_once __DIR__ . '/../../admin/add_post.php';
                    return;
                }

                $data['images'][] = [
                    'url' => '/public/uploads/' . $filename,
                    'name' => $filename,
                    'alt_text' => trim($_POST['main_image_alt_text'] ?? ''),
                    'caption' => trim($_POST['main_image_caption'] ?? ''),
                    'is_main' => 1
                ];
            }

            // Handle additional images (URL-based)
            if (!empty($_POST['image_urls']) && is_array($_POST['image_urls'])) {
                foreach ($_POST['image_urls'] as $index => $url) {
                    if (!empty($url)) {
                        $data['images'][] = [
                            'url' => trim($url),
                            'name' => null,
                            'alt_text' => trim($_POST['image_alt_texts'][$index] ?? ''),
                            'caption' => trim($_POST['image_captions'][$index] ?? ''),
                            'is_main' => 0
                        ];
                    }
                }
            }

            // Validation
            if (empty($data['title']) || empty($data['slug'])) {
                $error = "Title and slug are required.";
                $categories = $this->postModel->getAllCategories();
                require_once __DIR__ . '/../../admin/add_post.php';
                return;
            }

            if (!is_array($data['categories'])) {
                $data['categories'] = [];
            }

            try {
                $author_id = $_SESSION['user_id'] ?? 1;
                $post_id = $this->postModel->createPost($data, $author_id);
                header("Location: /admin/edit-post/$post_id?success=Post created successfully");
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
                $categories = $this->postModel->getAllCategories();
                require_once __DIR__ . '/../../admin/add_post.php';
            }
        } else {
            $this->index();
        }
    }
}
?>