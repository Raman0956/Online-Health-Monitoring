<?php

require('database.php');
include_once('User.php');

// Patient class that extends User
class Patient extends User {
    private $healthID;
    private $dateOfBirth;
    private $isApproved;

    public function __construct($userID, $name, $email, $phoneNumber, $password, $userType, $healthID, $dateOfBirth, $isApproved = false) {
        parent::__construct($userID, $name, $email, $phoneNumber, $password, $userType);
        $this->healthID = $healthID;
        $this->dateOfBirth = $dateOfBirth;
        $this->isApproved = $isApproved;
    }

    // Implementing the abstract login method
    public function login($conn, $email, $password) {
        try {
            // Fetch patient details based on email
            $sql = "SELECT * FROM User WHERE email = :email AND userType = 'Patient'";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                if (password_verify($password, $userData['password'])) {
                    session_start();
                    $_SESSION['userID'] = $userData['userID'];
                    $_SESSION['userType'] = $userData['userType'];
                    $_SESSION['isApproved'] = $userData['isApproved'];
                    header("Location: patient_dashboard.php");
                    exit();
                } else {
                    echo "<script>alert('Invalid password.');</script>";
                }
            } else {
                echo "<script>alert('No patient found with this email.');</script>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public static function register($conn, $name, $email, $phoneNumber, $password, $healthID, $dateOfBirth) {
        $userType = "Patient";
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Check if email already exists
            $sql = "SELECT COUNT(*) FROM User WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $emailExists = $stmt->fetchColumn();

            if ($emailExists > 0) {
                echo "<script>alert('An account already exists with this email');</script>";
                return;
            }

            // Insert user into the User table
            $sql = "INSERT INTO User (name, email, phoneNumber, password, userType) VALUES (:name, :email, :phoneNumber, :password, :userType)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':phoneNumber', $phoneNumber);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':userType', $userType);

            if ($stmt->execute()) {
                $userID = $conn->lastInsertId();
                $isApproved = false;

                // Insert patient-specific details into the Patient table
                $sql = "INSERT INTO Patient (patientID, healthID, dateOfBirth, isApproved) VALUES (:patientID, :healthID, :dateOfBirth, :isApproved)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':patientID', $userID);
                $stmt->bindValue(':healthID', $healthID);
                $stmt->bindValue(':dateOfBirth', $dateOfBirth);
                $stmt->bindValue(':isApproved', $isApproved, PDO::PARAM_BOOL);

                if ($stmt->execute()) {
                    echo "<script>alert('Account registered successfully. Waiting for approval.'); window.location.href='login.php';</script>";
                } else {
                    echo "Error: " . implode(", ", $stmt->errorInfo());
                }
            } else {
                echo "Error: " . implode(", ", $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            echo "Registration failed: " . $e->getMessage();
        }    }

    // Method to get patient-specific information
    public function getHealthID() {
        return $this->healthID;
    }

    public function getDateOfBirth() {
        return $this->dateOfBirth;
    }

    public function isApproved() {
        return $this->isApproved;
    }
}


?>

