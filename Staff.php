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
            $sql = "SELECT User.name, User.email, User.phoneNumber, Staff.workingID, Staff.imagePath
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

    // Modify photo
    public function uploadImage($conn, $imagePath,) {
        try {
            // Update photo
            $sql = "UPDATE staff 
                SET imagePath = :imagePath 
                WHERE staffID = :staffID";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':imagePath', $imagePath, PDO::PARAM_STR);
                $stmt->bindValue(':staffID', $this->userID, PDO::PARAM_INT);
            
            return $stmt->execute();
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


    // Method to retrieve all pending exams for all patients
    public function getPendingExams($conn) {
        try {
            $sql = "SELECT pe.prescriptionID, u.name AS patientName, p.dateOfBirth, p.healthID, 
                           e.examName AS examType, ei.itemName AS examItem, 
                           pe.prescriptionDate, pe.status
                    FROM prescribed_exam AS pe
                    INNER JOIN patient AS p ON pe.patientID = p.patientID
                    INNER JOIN user AS u ON p.patientID = u.userID
                    LEFT JOIN exam AS e ON pe.examID = e.examID
                    LEFT JOIN exam_item AS ei ON pe.itemID = ei.itemID
                    WHERE pe.status = 'Pending'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    public function searchExamsByPatientDetails($conn, $patientName = null, $dateOfBirth = null, $healthID = null) {
        try {
            $query = "SELECT pe.prescriptionID, u.name AS patientName, p.dateOfBirth, p.healthID,  
                 e.examName AS examType, ei.itemName AS examItem, 
                 pe.prescriptionDate, pe.status, pe.result
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
    
    

    // Method to add or modify exam results
    public function modifyExamResult($conn, $prescriptionID, $result, $isAbnormal) {
        try {
            $sql = "UPDATE prescribed_exam 
                    SET result = :result, isAbnormal = :isAbnormal, status = 'Completed' 
                    WHERE prescriptionID = :prescriptionID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':result', $result, PDO::PARAM_STR);
            $stmt->bindValue(':isAbnormal', $isAbnormal, PDO::PARAM_BOOL);
            $stmt->bindValue(':prescriptionID', $prescriptionID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function getMonitoringData($conn, $prescriptionID) { 
        try {
            $sql = "SELECT u.email AS doctorEmail, pUser.name AS patientName, 
                           e.examName, COALESCE(ei.itemName, 'N/A') AS itemName
                    FROM monitoring AS m
                    INNER JOIN doctor AS d ON m.doctorID = d.doctorID
                    INNER JOIN user AS u ON d.doctorID = u.userID
                    INNER JOIN patient AS p ON m.patientID = p.patientID
                    INNER JOIN user AS pUser ON p.patientID = pUser.userID
                    INNER JOIN exam AS e ON m.examID = e.examID
                    LEFT JOIN exam_item AS ei ON m.itemID = ei.itemID
                    WHERE m.patientID = (SELECT patientID FROM prescribed_exam WHERE prescriptionID = :prescriptionID)
                      AND m.examID = (SELECT examID FROM prescribed_exam WHERE prescriptionID = :prescriptionID)
                      AND (m.itemID = (SELECT itemID FROM prescribed_exam WHERE prescriptionID = :prescriptionID) OR m.itemID IS NULL)";
    
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':prescriptionID', $prescriptionID, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching monitoring data: " . $e->getMessage();
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
