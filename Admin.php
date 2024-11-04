<?php
require_once('User.php');

class Admin extends User {

    public function __construct($userID, $name, $email, $phoneNumber, $password, $userType) {
        parent::__construct($userID, $name, $email, $phoneNumber, $password, $userType);
    }

    // Admin login method
    public function login($conn, $email, $password) {
        try {
            // Fetch admin details based on email
            $sql = "SELECT * FROM User WHERE email = :email AND userType = 'Administrator'";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData && password_verify($password, $userData['password'])) {
                session_start();
                $_SESSION['userID'] = $userData['userID'];
                $_SESSION['userType'] = $userData['userType'];
                header("Location: admin_dashboard.php");
                exit();
            } else {
                echo "<script>alert('Invalid email or password.');</script>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Approve a patient registration
    public function approvePatientRegistration($conn, $patientID) {
        try {
            $sql = "UPDATE Patient SET isApproved = TRUE WHERE patientID = :patientID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':patientID', $patientID, PDO::PARAM_INT);
            if ($stmt->execute()) {
                echo "<script>alert('Patient account (" . $patientID . ") approved successfully.');</script>";
            } else {
                echo "<script>alert('Failed to approve patient account.');</script>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Create a doctor account
    public function createDoctor($conn, $name, $email, $phoneNumber, $password, $workingID) {
        $userType = "Doctor";
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Insert doctor into User table
            $sql = "INSERT INTO User (name, email, phoneNumber, password, userType) VALUES (:name, :email, :phoneNumber, :password, :userType)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':phoneNumber', $phoneNumber);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':userType', $userType);

            if ($stmt->execute()) {
                $userID = $conn->lastInsertId();
                // Insert into Doctor table
                $sql = "INSERT INTO Doctor (doctorID, workingID) VALUES (:doctorID, :workingID)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':doctorID', $userID);
                $stmt->bindValue(':workingID', $workingID);
                $stmt->execute();
                echo "<script>alert('New Doctor account created successfully.');</script>";
            } else {
                echo "<script>alert('Failed to create doctor account.');</script>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Create a staff account
    public function createStaff($conn, $name, $email, $phoneNumber, $password, $workingID) {
        $userType = "Staff";
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Insert staff into User table
            $sql = "INSERT INTO User (name, email, phoneNumber, password, userType) VALUES (:name, :email, :phoneNumber, :password, :userType)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':phoneNumber', $phoneNumber);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':userType', $userType);

            if ($stmt->execute()) {
                $userID = $conn->lastInsertId();
                // Insert into Staff table
                $sql = "INSERT INTO Staff (staffID, workingID) VALUES (:staffID, :workingID)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':staffID', $userID);
                $stmt->bindValue(':workingID', $workingID);
                $stmt->execute();
                echo "<script>alert('Staff account created successfully.');</script>";
            } else {
                echo "<script>alert('Failed to create staff account.');</script>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Delete any account
    public function deleteAccount($conn, $userID) {
        try {
            // Delete from specific table based on user type
            $sql = "SELECT userType FROM User WHERE userID = :userID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            $userType = $stmt->fetchColumn();

            if ($userType) {
                // Delete from role-specific table
                $roleTable = ucfirst(strtolower($userType)); // e.g., "Patient" or "Doctor"
                $sql = "DELETE FROM $roleTable WHERE {$userType}ID = :userID";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Delete from User table
            $sql = "DELETE FROM User WHERE userID = :userID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
            if ($stmt->execute()) {
                echo "<script>alert('User account deleted successfully.');</script>";
            } else {
                echo "<script>alert('Failed to delete user account.');</script>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }


    public function searchExamsByPatientDetails($conn, $patientName = null, $dateOfBirth = null, $healthID = null) {
        try {
            $query = "SELECT pe.prescriptionID, u.name AS patientName, p.dateOfBirth, p.healthID,  
                 e.examName AS examType, ei.itemName AS examItem, 
                 pe.prescriptionDate, pe.status, pe.result, pe.isAbnormal
                FROM prescribed_exam AS pe
                INNER JOIN patient AS p ON pe.patientID = p.patientID
                INNER JOIN user AS u ON p.patientID = u.userID
                LEFT JOIN exam AS e ON pe.examID = e.examID
                LEFT JOIN exam_item AS ei ON pe.itemID = ei.itemID
                WHERE 1=1";

    
            if (!empty($patientName)) {
                $query .= " AND u.name LIKE :patientName";
            }
            if (!empty($dateOfBirth)) {
                $query .= " AND p.dateOfBirth = :dateOfBirth";
            }
            if (!empty($healthID)) {
                $query .= " AND p.healthID = :healthID";
            }
    
            $stmt = $conn->prepare($query);
    
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
    
    public function deleteExam($conn, $prescriptionID) {
        try {
            // Delete the exam from prescribed_exam based on prescriptionID
            $sql = "DELETE FROM prescribed_exam WHERE prescriptionID = :prescriptionID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':prescriptionID', $prescriptionID, PDO::PARAM_INT);
            
            // Execute delete operation
            $stmt->execute();
    
            if ($stmt->rowCount() > 0) {
                return "Exam record deleted successfully.";
            } else {
                return "No matching exam record found to delete.";
            }
        } catch (PDOException $e) {
            echo "Error deleting exam record: " . $e->getMessage();
            return null;
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
?>
