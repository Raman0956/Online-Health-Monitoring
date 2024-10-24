<?php
require_once('User.php');

class Staff extends User {
    private $workingID;

    public function __construct($userID, $name, $email, $phoneNumber, $password, $userType, $workingID = null) {
        parent::__construct($userID, $name, $email, $phoneNumber, $password, $userType);
        $this->workingID = $workingID;
    }

    // Implementing the abstract login method
    public function login($conn, $email, $password) {
        try {
            // Fetch patient details based on email
            $sql = "SELECT * FROM user WHERE email = :email AND userType = 'Staff'";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                if (password_verify($password, $userData['password'])) {
                    session_start();
                    $_SESSION['userID'] = $userData['userID'];
                    $_SESSION['userType'] = $userData['userType'];
                    header("Location: staff_dashboard.php");
                    exit();
                } else {
                    echo "<script>alert('Invalid password.');</script>";
                }
            } else {
                echo "<script>alert('No account found with this email.');</script>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Get account information, including working ID
    public function getAccountInfo($conn) {
        try {
            $sql = "SELECT User.name, User.email, User.phoneNumber, Staff.workingID 
                    FROM User 
                    INNER JOIN Staff ON User.userID = Staff.staffID
                    WHERE User.userID = :userID AND User.userType = 'Staff'";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':userID', $this->userID, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
       // Change password with validation for current password
       public function changePassword($conn, $currentPassword, $newPassword) {
        try {
            $sql = "SELECT password FROM User WHERE userID = :userID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':userID', $this->userID, PDO::PARAM_INT);
            $stmt->execute();
            $existingPassword = $stmt->fetchColumn();

            if (!password_verify($currentPassword, $existingPassword)) {
                return 'Current password is incorrect.';
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE User SET password = :password WHERE userID = :userID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindValue(':userID', $this->userID, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return true;
            } else {
                return 'Failed to update password.';
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
 }

    // Method to modify staff account details
    public function modifyAccount($conn, $name, $email, $phoneNumber) {
        try {
            $sql = "UPDATE user SET name = :name, email = :email, phoneNumber = :phoneNumber WHERE userID = :userID AND userType = 'Staff'";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':phoneNumber', $phoneNumber, PDO::PARAM_STR);
            $stmt->bindValue(':userID', $this->userID, PDO::PARAM_INT);
    
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    // Method to retrieve all exams for a specific patient or filter by status
    public function getExamsByPatient($conn, $patientName, $status = null) {
        try {
            $query = "SELECT exam.*, patient.patientID, patient.dateOfBirth, patient.healthID, user.name AS patientName 
                      FROM exam 
                      INNER JOIN patient ON exam.patientID = patient.patientID 
                      INNER JOIN user ON patient.patientID = user.userID 
                      WHERE user.name LIKE :patientName";
                      
            if ($status) {
                $query .= " AND exam.status = :status";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':patientName', '%' . $patientName . '%', PDO::PARAM_STR);
            if ($status) {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    // Method to retrieve all pending exams for all patients
    public function getPendingExams($conn) {
        try {
            $sql = "SELECT exam.*, patient.patientID, patient.dateOfBirth, patient.healthID, user.name AS patientName 
                    FROM exam 
                    INNER JOIN patient ON exam.patientID = patient.patientID 
                    INNER JOIN user ON patient.patientID = user.userID 
                    WHERE exam.status = 'Pending'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    

    // Method to add or modify exam results
    public function modifyExamResult($conn, $examID, $isAbnormal) {
        try {
            $sql = "UPDATE exam SET isAbnormal = :isAbnormal, status = 'Completed' WHERE examID = :examID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':isAbnormal', $isAbnormal, PDO::PARAM_BOOL);
            $stmt->bindValue(':examID', $examID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    // retrieve exams based on patientDetails
    public function searchExamsByPatientDetails($conn, $patientName = null, $dateOfBirth = null, $healthID = null) {
        try {
            $query = "SELECT exam.*, patient.patientID, patient.dateOfBirth, patient.healthID, user.name AS patientName 
                      FROM exam 
                      INNER JOIN patient ON exam.patientID = patient.patientID 
                      INNER JOIN user ON patient.patientID = user.userID 
                      WHERE 1=1"; // Start with a default true condition
    
            // Add conditions based on available parameters
            if (!empty($patientName)) {
                $query .= " AND user.name LIKE :patientName";
            }
            if (!empty($dateOfBirth)) {
                $query .= " AND patient.dateOfBirth = :dateOfBirth";
            }
            if (!empty($healthID)) {
                $query .= " AND patient.healthID = :healthID";
            }
    
            $stmt = $conn->prepare($query);
    
            // Bind parameters based on available values
            if (!empty($patientName)) {
                $stmt->bindValue(':patientName', '%' . $patientName . '%', PDO::PARAM_STR);
            }
            if (!empty($dateOfBirth)) {
                $stmt->bindValue(':dateOfBirth', $dateOfBirth, PDO::PARAM_STR);
            }
            if (!empty($healthID)) {
                $stmt->bindValue(':healthID', $healthID, PDO::PARAM_STR);
            }
    
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    

    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
}
