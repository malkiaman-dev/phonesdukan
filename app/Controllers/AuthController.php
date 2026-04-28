class AuthController {
    public function login() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            include_once 'app/Models/UserModel.php';
            $userModel = new UserModel();
            $user = $userModel->checkUser($_POST['email'], $_POST['password']);
            
            if ($user) {
                session_start();
                $_SESSION['user'] = $user;
                header("Location: /dashboard");
                exit();
            } else {
                echo "Invalid login credentials";
            }
        }
        include_once 'app/Views/user/login.php';
    }
}
