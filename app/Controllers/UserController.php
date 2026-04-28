<?php
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../../database/db.php';

class UserController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Register user
    public function register($full_name, $email, $password, $phone) {
        $userModel = new User($this->db);
        return $userModel->register($full_name, $email, $password, $phone);
    }

    // Login user
    public function login($email, $password) {
        $userModel = new User($this->db);
        return $userModel->login($email, $password);
    }
}
