    <?php
    require_once __DIR__ . '/../classes/Admin.php';

    class AuthController
    {
        // app/controllers/AuthController.php
        public function login()
        {
            if (isset($_POST['login'])) {
                $adminModel = new Admin();
                $userData = $adminModel->login($_POST['username'], $_POST['password']);

                if ($userData) {
                    // Set the exact keys your Sidebar constructor is looking for:
                    $_SESSION['admin'] = true;
                    $_SESSION['id'] = $userData['id'];
                    $_SESSION['username'] = $userData['username'];

                    header("Location: index.php?page=dashboard");
                    exit();
                } else {
                    $error = "Invalid username or password";
                    require "../app/views/auth/login.php";
                }
            } else {
                require "../app/views/auth/login.php";
            }
        }
    }
