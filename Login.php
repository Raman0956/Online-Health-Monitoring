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
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login</title>
            <link rel="stylesheet" href="main.css">
        </head>
        <body>
            <div class="container">
                <h2>Login</h2>
                <form method="POST" action="">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>

                    <input type="submit" name="login" value="Login">
                </form>
                <p>Don\'t have an account? <a href="patient_register.php">New Patient Register here</a></p>
            </div>
        </body>
        </html>
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
                    // Check if the user is a Patient and if the account is approved
                    if ($userData['userType'] === 'Patient') {
                        $approvalStatus = $this->checkPatientApprovalStatus($userData['userID']);
                        if (!$approvalStatus) {
                            echo "<script>alert('Your account is pending approval. Check back later or contact Clinic helpdesk');</script>";
                            return;
                        }
                    }

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

    private function checkPatientApprovalStatus($userID) {
        try {
            $sql = "SELECT isApproved FROM Patient WHERE patientID = :userID";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Debugging: Check approval status retrieved from the database
            echo "<script>console.log('Database isApproved:', " . json_encode($result['isApproved']) . ");</script>";
    
            if ($result && $result['isApproved'] == 1) {
                $_SESSION['isApproved'] = true; // Ensure session variable is set to true
                return true;
            } else {
                $_SESSION['isApproved'] = false;
                return false;
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
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
