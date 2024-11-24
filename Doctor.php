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
            $sql = "SELECT User.name, User.email, User.phoneNumber, Doctor.workingID, Doctor.imagePath
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

    // Modify account details
    public function uploadImage($conn, $imagePath,) {
        try {
            // Update photo
            $sql = "UPDATE doctor 
                SET imagePath = :imagePath 
                WHERE doctorID = :doctorID";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':imagePath', $imagePath, PDO::PARAM_STR);
                $stmt->bindValue(':doctorID', $this->userID, PDO::PARAM_INT);
            
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

        // Method to get the name of an exam by its ID
        public function getExamNameById($conn, $examID) {
            try {
                $stmt = $conn->prepare("SELECT examName FROM exam WHERE examID = :examID");
                $stmt->bindParam(':examID', $examID, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['examName'] ?? null;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
                return null;
            }
        }
    
        // Method to get the name of an exam item by its ID
        public function getItemNameById($conn, $itemID) {
            try {
                $stmt = $conn->prepare("SELECT itemName FROM exam_item WHERE itemID = :itemID");
                $stmt->bindParam(':itemID', $itemID, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['itemName'] ?? null;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
                return null;
            }
        }
    

        public function prescribeExam($conn, $patientID, $doctorID, $examCategories) { 
            try {
                foreach ($examCategories as $examID => $itemIDs) {
                    if ($examID != 1) {
                        // If examID is 1, insert the main exam with NULL for itemID
                        $stmt = $conn->prepare("INSERT INTO prescribed_exam (patientID, doctorID, examID, itemID, prescriptionDate, status) VALUES (:patientID, :doctorID, :examID, NULL, CURDATE(), 'Pending')");
                        $stmt->bindParam(':patientID', $patientID);
                        $stmt->bindParam(':doctorID', $doctorID);
                        $stmt->bindParam(':examID', $examID);
                        $stmt->execute();
                    } else {
                        // For exams where examID is not 1, insert each selected item as a row
                        foreach ($itemIDs as $itemID) {
                            $stmt = $conn->prepare("INSERT INTO prescribed_exam (patientID, doctorID, examID, itemID, prescriptionDate, status) VALUES (:patientID, :doctorID, :examID, :itemID, CURDATE(), 'Pending')");
                            $stmt->bindParam(':patientID', $patientID);
                            $stmt->bindParam(':doctorID', $doctorID);
                            $stmt->bindParam(':examID', $examID);
                            $stmt->bindParam(':itemID', $itemID);
                            $stmt->execute();
                        }
                    }
                }
                return true;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
                return false;
            }
        }
        
    
    
    
    // Check exam results for a patient
    public function searchExamResults($conn, $patientName, $prescriptionDate = null, $selectedExams = [], $isAbnormal = false) {
        try {
            // Base SQL query
            $sql = "SELECT u.name AS patientName, e.examName AS examType, ei.itemName AS examItem, 
                           pe.prescriptionDate, pe.result, pe.isAbnormal
                    FROM prescribed_exam AS pe
                    INNER JOIN patient AS p ON pe.patientID = p.patientID
                    INNER JOIN user AS u ON p.patientID = u.userID
                    LEFT JOIN exam AS e ON pe.examID = e.examID
                    LEFT JOIN exam_item AS ei ON pe.itemID = ei.itemID
                    WHERE u.name LIKE :patientName";
    
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
            $stmt->bindValue(':patientName', "%$patientName%", PDO::PARAM_STR);
    
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
    
    
    // Set monitoring for a patient exam
    public function setMonitoring($conn, $patientID, $doctorID, $examCategories) {
        try {
            foreach ($examCategories as $examID => $itemIDs) {
                $isBloodTest = $this->isBloodTestExam($conn, $examID);
    
                if ($isBloodTest) {
                    // For Blood Test (or similar exams requiring item-level monitoring), insert each item separately
                    foreach ($itemIDs as $itemID) {
                        $stmt = $conn->prepare("INSERT INTO monitoring (patientID, doctorID, examID, itemID) VALUES (:patientID, :doctorID, :examID, :itemID)");
                        $stmt->bindParam(':patientID', $patientID);
                        $stmt->bindParam(':doctorID', $doctorID);
                        $stmt->bindParam(':examID', $examID);
                        $stmt->bindParam(':itemID', $itemID);
                        $stmt->execute();
                    }
                } else {
                    // For other exams without specific items, insert a single row with NULL for itemID
                    $stmt = $conn->prepare("INSERT INTO monitoring (patientID, doctorID, examID, itemID) VALUES (:patientID, :doctorID, :examID, NULL)");
                    $stmt->bindParam(':patientID', $patientID);
                    $stmt->bindParam(':doctorID', $doctorID);
                    $stmt->bindParam(':examID', $examID);
                    $stmt->execute();
                }
            }
            return true;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    // Helper method to check if an exam requires item-level monitoring
    private function isBloodTestExam($conn, $examID) {
        $sql = "SELECT examName FROM exam WHERE examID = :examID";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':examID', $examID, PDO::PARAM_INT);
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        return $exam && $exam['examName'] === "Blood Test";
    }
    
    
    public function modifyMonitoring($conn, $monitoringID, $examID, $itemID) {
        try {
            // Update monitoring data
            $sql = "UPDATE monitoring SET examID = :examID, itemID = :itemID WHERE monitoringID = :monitoringID AND doctorID = :doctorID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':examID', $examID, PDO::PARAM_INT);
            $stmt->bindValue(':itemID', $itemID, PDO::PARAM_INT);
            $stmt->bindValue(':monitoringID', $monitoringID, PDO::PARAM_INT);
            $stmt->bindValue(':doctorID', $this->userID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }
    
    public function deleteMonitoring($conn, $monitoringID) {
        try {
            // Delete monitoring data
            $sql = "DELETE FROM monitoring WHERE monitoringID = :monitoringID AND doctorID = :doctorID";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':monitoringID', $monitoringID, PDO::PARAM_INT);
            $stmt->bindValue(':doctorID', $this->userID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function getMonitoringItems($conn, $patientID) {
        try {
            // Prepare SQL query to fetch monitoring items for the patient
            $sql = "SELECT m.monitoringID, e.examName, ei.itemName 
                    FROM monitoring AS m
                    INNER JOIN exam AS e ON m.examID = e.examID
                    LEFT JOIN exam_item AS ei ON m.itemID = ei.itemID
                    WHERE m.patientID = :patientID";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':patientID', $patientID, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching monitoring items: " . $e->getMessage();
            return [];
        }
    }
    
    
    public function notifyDoctor($doctorEmail, $patientName, $examName, $itemName) {
        $subject = "Abnormal Test Result Alert for Patient $patientName";
        $message = "Dear Doctor,\n\nAn abnormal result was detected for the following test:\n\n" .
                   "Patient: $patientName\n" .
                   "Test: $examName\n" .
                   "Item: $itemName\n\n" .
                   "Please review the patient's record for further information.";
        $headers = "From: ramandeep0956@gmail.com";
    
        // Send email
        mail($doctorEmail, $subject, $message, $headers);
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
