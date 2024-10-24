<?php 
session_start();

require_once('database.php');
require_once('User.php');
require_once('Patient.php');


class Login {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Method to display the login form
    public function displayForm() {
        echo '
        <h2>Login</h2>
        <form method="POST" action="">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>

            <input type="submit" name="login" value="Login">
        </form>
        <p>Don\'t have an account? <a href="patient_register.php">New Patient Register here</a></p>
        ';
    }

    // Method to handle the login process
    public function handleLogin() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];

            try {
                // Retrieve user details based on email
                $sql = "SELECT userID, userType, password FROM User WHERE email = :email";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($userData && password_verify($password, $userData['password'])) {
                    // Store userID and userType in session variables
                    $_SESSION['userID'] = $userData['userID'];
                    $_SESSION['userType'] = $userData['userType'];

                    // Redirect user based on their type
                    $this->redirectUser($userData['userType']);
                } else {
                    echo "<script>alert('Invalid email or password.');</script>";
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }

    // Method to redirect user based on their user type
    private function redirectUser($userType) {
        switch ($userType) {
            case 'Patient':
                header("Location: patient_dashboard.php");
                break;
            case 'Doctor':
                header("Location: doctor_dashboard.php");
                break;
            case 'Staff':
                header("Location: staff_dashboard.php");
                break;
            case 'Administrator':
                header("Location: admin_dashboard.php");
                break;
            default:
                echo "<script>alert('Unknown user type. Please contact support.');</script>";
                exit();
        }
        exit();
    }
}

// Instantiate the Login class and display the form and handle login
$login = new Login($conn);
$login->displayForm();
$login->handleLogin();
?>
