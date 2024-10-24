<?php
require_once('User.php');

class Doctor extends User {
    private $workingID;

    public function __construct($userID, $name, $email, $phoneNumber, $password, $userType, $workingID = null) {
        parent::__construct($userID, $name, $email, $phoneNumber, $password, $userType);
        $this->workingID = $workingID;
    }

    // Implementing the abstract login method
    public function login($conn, $email, $password) {
        try {
            // Fetch patient details based on email
            $sql = "SELECT * FROM User WHERE email = :email AND userType = 'Doctor'";
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
                    header("Location: doctor_dashboard.php");
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
            $sql = "SELECT User.name, User.email, User.phoneNumber, Doctor.workingID 
                    FROM User 
                    INNER JOIN Doctor ON User.userID = Doctor.doctorID 
                    WHERE User.userID = :userID AND User.userType = 'Doctor'";
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
    public function modifyAccount($conn, $name, $email, $phoneNumber) {
        try {
            // Update name, email, and phone number
            $sql = "UPDATE User SET name = :name, email = :email, phoneNumber = :phoneNumber WHERE userID = :userID AND userType = 'Doctor'";
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
    
    
    // Add this function in your Doctor.php class
public function searchPatient($conn, $patientName, $dateOfBirth = null, $healthID = null) {
    try {
        // Building query dynamically based on the input provided
        $sql = "SELECT p.patientID, p.healthID, p.dateOfBirth, u.name 
                FROM patient p 
                INNER JOIN user u ON p.patientID = u.userID 
                WHERE u.name LIKE :patientName";
        
        // Add dateOfBirth if provided
        if (!empty($dateOfBirth)) {
            $sql .= " AND p.dateOfBirth = :dateOfBirth";
        }
        
        // Add healthID if provided
        if (!empty($healthID)) {
            $sql .= " AND p.healthID = :healthID";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':patientName', $patientName, PDO::PARAM_STR);

        if (!empty($dateOfBirth)) {
            $stmt->bindValue(':dateOfBirth', $dateOfBirth, PDO::PARAM_STR);
        }
        
        if (!empty($healthID)) {
            $stmt->bindValue(':healthID', $healthID, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC); // Return only one row as we are searching for a single patient

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

    // Prescribe an exam to a patient
    public function prescribeExam($conn, $patientID, $doctorID, $examCategories) {
        try {
            foreach ($examCategories as $examType => $examItems) {
                // Concatenate the subcategories (examItems) into a single string
                $examItem = implode(', ', $examItems);
    
                // Insert a single row with the main category and the concatenated subcategories
                $stmt = $conn->prepare("INSERT INTO exam (examType, examItem, examDate, status, patientID, doctorID) VALUES (:examType, :examItem, CURDATE(), 'Pending', :patientID, :doctorID)");
                $stmt->bindParam(':examType', $examType);
                $stmt->bindParam(':examItem', $examItem);
                $stmt->bindParam(':patientID', $patientID);
                $stmt->bindParam(':doctorID', $doctorID);
    
                if (!$stmt->execute()) {
                    return false; // Return false if the insertion fails
                }
            }
            return true; // Return true if all insertions are successful
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    // Check exam results for a patient
    public function checkExamResults($conn, $patientID, $examType) {
        try {
            $sql = "SELECT * FROM ExamResults WHERE patientID = :patientID AND examType = :examType";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':patientID', $patientID, PDO::PARAM_INT);
            $stmt->bindValue(':examType', $examType, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    // Set monitoring for a patient exam
    public function setMonitoring($conn, $patientID, $examType, $frequency, $duration) {
        try {
            $sql = "INSERT INTO Monitoring (patientID, doctorID, examType, frequency, duration) VALUES (:patientID, :doctorID, :examType, :frequency, :duration)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':patientID', $patientID, PDO::PARAM_INT);
            $stmt->bindValue(':doctorID', $this->userID, PDO::PARAM_INT);
            $stmt->bindValue(':examType', $examType, PDO::PARAM_STR);
            $stmt->bindValue(':frequency', $frequency, PDO::PARAM_STR);
            $stmt->bindValue(':duration', $duration, PDO::PARAM_INT);
            return $stmt->execute();
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
?>
