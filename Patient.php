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
    public function isAccountApproved() {
        return $this->isApproved;
    }

    // Implementing the abstract login method
   // Implementing the abstract login method
   public function login($conn, $email, $password) {
    try {
        $sql = "SELECT u.*, p.isApproved 
                FROM User u 
                INNER JOIN Patient p ON u.userID = p.patientID 
                WHERE u.email = :email AND u.userType = 'Patient'";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && password_verify($password, $userData['password'])) {
            if ($userData['isApproved']) {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['userID'] = $userData['userID'];
                $_SESSION['userType'] = $userData['userType'];
                $_SESSION['isApproved'] = (bool)$userData['isApproved'];
                header("Location: patient_dashboard.php");
                exit();
            } else {
                echo "<script>alert('Your account is pending approval.');</script>";
            }
        } else {
            echo "<script>alert('Invalid email or password.');</script>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

    
    // Get account information, including working ID
    public function getAccountInfo($conn) {
        try {
            $sql = "SELECT User.name, User.email, User.phoneNumber, Patient.dateOfBirth, Patient.healthID 
                    FROM User 
                    INNER JOIN Patient ON User.userID = Patient.PatientID 
                    WHERE User.userID = :userID AND User.userType = 'Patient'";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':userID', $this->userID, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }


    // Modify account details
    public function modifyAccount($conn, $name, $email, $phoneNumber, $dateOfBirth, $healthID) {
        try {
            // Begin transaction to ensure both updates happen together
            $conn->beginTransaction();
    
            // Update `User` table for name, email, and phone number
            $sqlUser = "UPDATE User SET name = :name, email = :email, phoneNumber = :phoneNumber 
                        WHERE userID = :userID AND userType = 'Patient'";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bindValue(':name', $name, PDO::PARAM_STR);
            $stmtUser->bindValue(':email', $email, PDO::PARAM_STR);
            $stmtUser->bindValue(':phoneNumber', $phoneNumber, PDO::PARAM_STR);
            $stmtUser->bindValue(':userID', $this->userID, PDO::PARAM_INT);
            $stmtUser->execute();
    
            // Update `Patient` table for dateOfBirth and healthID
            $sqlPatient = "UPDATE Patient SET dateOfBirth = :dateOfBirth, healthID = :healthID 
                           WHERE patientID = :userID";
            $stmtPatient = $conn->prepare($sqlPatient);
            $stmtPatient->bindValue(':dateOfBirth', $dateOfBirth, PDO::PARAM_STR);
            $stmtPatient->bindValue(':healthID', $healthID, PDO::PARAM_STR);
            $stmtPatient->bindValue(':userID', $this->userID, PDO::PARAM_INT);
            $stmtPatient->execute();
    
            // Commit the transaction if both updates succeed
            $conn->commit();
            return true;
    
        } catch (PDOException $e) {
            // Rollback the transaction if any update fails
            $conn->rollBack();
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
    
     // Check exam results 
     public function searchExamResults($conn, $patientID, $prescriptionDate = null, $selectedExams = [], $isAbnormal = false) { 
        try {
            // Base SQL query
            $sql = "SELECT u.userID AS patientID, e.examName AS examType, ei.itemName AS examItem, 
                           pe.prescriptionDate, pe.result, pe.isAbnormal
                    FROM prescribed_exam AS pe
                    INNER JOIN patient AS p ON pe.patientID = p.patientID
                    INNER JOIN user AS u ON p.patientID = u.userID
                    LEFT JOIN exam AS e ON pe.examID = e.examID
                    LEFT JOIN exam_item AS ei ON pe.itemID = ei.itemID
                    WHERE u.userID = :patientID";
            
            // Optional filter for prescription date
            if ($prescriptionDate) {
                $sql .= " AND pe.prescriptionDate = :prescriptionDate";
            }
            // Optional filter for abnormal results
            if ($isAbnormal) {
                $sql .= " AND pe.isAbnormal = 1";
            }
    
            // Exam type and item filters, if any selected
            if (!empty($selectedExams)) {
                $examConditions = [];
                foreach ($selectedExams as $index => $exam) {
                    $examParam = ":examID{$index}";
    
                    // Condition for exams without items
                    if (empty($exam['itemIDs'])) {
                        $examConditions[] = "(pe.examID = $examParam AND pe.itemID IS NULL)";
                    } else {
                        // Condition for exams with specific items
                        $itemConditions = implode(", ", array_map(fn($id) => ":itemID{$index}_{$id}", $exam['itemIDs']));
                        $examConditions[] = "(pe.examID = $examParam AND (pe.itemID IN ($itemConditions) OR pe.itemID IS NULL))";
                    }
                }
                $sql .= " AND (" . implode(" OR ", $examConditions) . ")";
            }
    
            // Prepare statement
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':patientID', $patientID, PDO::PARAM_INT);
    
            // Bind optional parameters
            if ($prescriptionDate) {
                $stmt->bindValue(':prescriptionDate', $prescriptionDate, PDO::PARAM_STR);
            }
    
            // Bind exam type and item parameters
            foreach ($selectedExams as $index => $exam) {
                $stmt->bindValue(":examID{$index}", $exam['examID'], PDO::PARAM_INT);
                foreach ($exam['itemIDs'] as $itemIndex => $itemID) {
                    $stmt->bindValue(":itemID{$index}_{$itemID}", $itemID, PDO::PARAM_INT);
                }
            }
    
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }

    //to retreive exam ID's
    public function getExamIDs($conn) {
        try {
            $sql = "SELECT examID, examName FROM exam";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            $examIDs = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $examIDs[$row['examName']] = $row['examID'];
            }
            
            return $examIDs;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
    
    //To retrieve Blood exam_itemIDs
    public function getBloodTestItemIDs($conn, $bloodTestExamID) {
        try {
            $sql = "SELECT itemID, itemName FROM exam_item WHERE examID = :bloodTestExamID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':bloodTestExamID', $bloodTestExamID, PDO::PARAM_INT);
            $stmt->execute();
            
            $bloodTestItems = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $bloodTestItems[$row['itemName']] = $row['itemID'];
            }
            
            return $bloodTestItems;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return [];
        }
    }
    
    


    public static function register($conn, $name, $email, $phoneNumber, $password, $healthID, $dateOfBirth) {
        $userType = "Patient";
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try{    
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
                    echo "<script>alert('Account registration request sent. Waiting for approval.'); window.location.href='login.php';</script>";
                } else {
                    echo "Error: " . implode(", ", $stmt->errorInfo());
                }
            } else {
                echo "Error: " . implode(", ", $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            echo "Registration failed: " . $e->getMessage();
        }    }


    public function logout() {
            session_start();
            session_unset();
            session_destroy();
            header("Location: login.php");
            exit();
    }

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

