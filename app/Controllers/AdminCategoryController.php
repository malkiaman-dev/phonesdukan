<?php
session_start();
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/app/Models/AdminPostModel.php';

class AdminCategoryController {
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
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'create':
                    $this->create($categories);
                    break;
                case 'update':
                    $this->update($categories);
                    break;
                case 'delete':
                    $this->delete($categories);
                    break;
            }
        }

        require_once __DIR__ . '/../../admin/categories.php';
    }

    private function create(&$categories) {
        $data = [
            'category_name' => trim($_POST['category_name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'status' => isset($_POST['status']) ? 1 : 0
        ];

        if (empty($data['category_name']) || empty($data['slug'])) {
            $GLOBALS['error'] = "Category name and slug are required.";
            return;
        }

        try {
            $this->postModel->createCategory($data);
            $GLOBALS['success'] = "Category created successfully.";
            // Reload categories to include the new one
            $categories = $this->postModel->getAllCategories();
        } catch (Exception $e) {
            $GLOBALS['error'] = $e->getMessage();
        }
    }

    private function update(&$categories) {
        $category_id = $_POST['category_id'] ?? null;
        $data = [
            'category_name' => trim($_POST['category_name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'status' => isset($_POST['status']) ? 1 : 0
        ];

        if (!$category_id || empty($data['category_name']) || empty($data['slug'])) {
            $GLOBALS['error'] = "Category ID, name, and slug are required.";
            return;
        }

        try {
            $this->postModel->updateCategory($category_id, $data);
            $GLOBALS['success'] = "Category updated successfully.";
            // Reload categories to reflect changes
            $categories = $this->postModel->getAllCategories();
        } catch (Exception $e) {
            $GLOBALS['error'] = $e->getMessage();
        }
    }

    private function delete(&$categories) {
        $category_id = $_POST['category_id'] ?? null;

        if (!$category_id) {
            $GLOBALS['error'] = "Category ID is required.";
            return;
        }

        try {
            $this->postModel->deleteCategory($category_id);
            $GLOBALS['success'] = "Category deleted successfully.";
            // Reload categories to reflect deletion
            $categories = $this->postModel->getAllCategories();
        } catch (Exception $e) {
            $GLOBALS['error'] = $e->getMessage();
        }
    }
}
?>