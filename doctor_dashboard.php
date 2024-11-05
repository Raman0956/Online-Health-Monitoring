<?php   
session_start();
require_once('database.php');
require_once('Doctor.php');

if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'Doctor') {
    header("Location: login.php");
    exit();
}

$doctorID = $_SESSION['userID'];
$doctor = new Doctor($doctorID, "Doctor Name", "doctor@example.com", "1234567890", "hashed_password", "Doctor");

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$doctorInfo = $doctor->getAccountInfo($conn);

$passwordSuccess = '';
$patientResult = $_SESSION['patientResult'] ?? null;

if ($action === 'saveChanges' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phoneNumber'];
    $success = $doctor->modifyAccount($conn, $name, $email, $phoneNumber);

    if ($success) {
        echo "<script>alert('Account updated successfully.'); window.location.href='doctor_dashboard.php?action=viewAccount';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to update account details.');</script>";
    }
}

if ($action === 'changePassword' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    if ($newPassword !== $confirmPassword) {
        echo "<script>alert('Passwords do not match.'); window.location.href='doctor_dashboard.php?action=modifyAccount';</script>";
    } else {
        $result = $doctor->changePassword($conn, $currentPassword, $newPassword);
        if ($result === true) {
            echo "<script>alert('Password changed successfully.'); window.location.href='doctor_dashboard.php?action=modifyAccount';</script>";
        } else {
            echo "<script>alert('Current password is incorrect'); window.location.href='doctor_dashboard.php?action=modifyAccount';</script>";
        }
    }
}

if ($action === 'searchPatient' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientName = $_POST['patientName'];
    $dateOfBirth = $_POST['dateOfBirth'] ?? null;
    $healthID = $_POST['healthID'] ?? null;

    $patientResult = $doctor->searchPatient($conn, $patientName, $dateOfBirth, $healthID);
    if ($patientResult) {
        $_SESSION['patientResult'] = $patientResult;
        $action = 'prescribeExam'; // Set action to prescribeExam to render the form
    } else {
        echo "<p style='color:red;'>No matching patient found. Please try again.</p>";
        $_SESSION['patientResult'] = null;
    }
}

/// Fetch exam IDs and blood test item IDs
$examIDs = $doctor->getExamIDs($conn);
$bloodTestItems = $doctor->getBloodTestItemIDs($conn, $examIDs['Blood Test'] ?? null);
$prescriptionMessage = '';

// Define main exams and their subcategories if any
$examData = [
    'Blood Test' => $bloodTestItems, // Using the fetched blood test items here
    'Urine Test' => [],
    'Ultrasound' => [],
    'X-ray' => [],
    'CT Scan' => [],
    'ECG' => []
];

// Submit prescription handler
if ($action === 'submitPrescription' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientID = $_POST['patientID'];
    $examCategories = $_POST['examCategories'] ?? [];

    if (!empty($examCategories)) {
        $doctorID = $_SESSION['userID']; // Get the logged-in doctor's ID

        // Call the prescribeExam method from the Doctor class
        $result = $doctor->prescribeExam($conn, $patientID, $doctorID, $examCategories);

        if ($result) {
            // Retrieve the patient's name for the message
            $patientName = htmlspecialchars($patientResult['name']);
            // Format exam names and subcategories for display
            $examSummary = [];
            foreach ($examCategories as $examID => $itemIDs) {
                $examName = $doctor->getExamNameById($conn, $examID); // Get exam name by ID
                
                // Check if only the main exam is selected or if there are subcategories
                if (count($itemIDs) === 1 && $itemIDs[0] == $examID) {
                    // Only the main exam is selected, no subcategories
                    $examSummary[] = $examName;
                } else {
                    // If there are specific items, list them
                    $itemNames = array_map(fn($id) => $id ? $doctor->getItemNameById($conn, $id) : '', $itemIDs);
                    $itemNames = array_filter($itemNames); // Remove empty item names
                    if (!empty($itemNames)) {
                        $examSummary[] = "$examName: " . implode(', ', $itemNames);
                    } else {
                        $examSummary[] = $examName; // Only the main exam if no items
                    }
                }
            }
            $examDetails = implode(', ', $examSummary);
            $prescriptionMessage = "<p style='color: green;'>Exams prescribed successfully for $patientName. Prescribed exams: $examDetails.</p>";
        } else {
            $prescriptionMessage = "<p style='color: red;'>Failed to prescribe exams. Please try again.</p>";
        }
    } else {
        $prescriptionMessage = "<p style='color: red;'>No exams selected.</p>";
    }
}



if ($action === 'executeSearchExamResults' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
    $patientName = $_POST['patientName'];
    $prescriptionDate = $_POST['prescriptionDate'] ?? null;
    $isAbnormal = isset($_POST['isAbnormal']) ? true : false;

    // Collect selected exam types and items
    $selectedExams = [];
    if (isset($_POST['examCategories'])) {
        foreach ($_POST['examCategories'] as $examID => $itemIDs) {
            $selectedExams[] = [
                'examID' => $examID,
                'itemIDs' => array_filter($itemIDs, fn($id) => $id !== $examID) // Filter out the main exam ID if no subcategory
            ];
        }
    }

    // Call search function with selected filters
    $examResults = $doctor->searchExamResults($conn, $patientName, $prescriptionDate, $selectedExams, $isAbnormal);
}

$monitoredItems = [];
$message = '';

// Handle search patient for monitoring setup
if ($action === 'searchPatientToMonitor' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientName = $_POST['patientName'];
    $dateOfBirth = $_POST['dateOfBirth'] ?? null;
    $healthID = $_POST['healthID'] ?? null;

    $patientResult = $doctor->searchPatient($conn, $patientName, $dateOfBirth, $healthID);

    if ($patientResult) {
        $_SESSION['patientResult'] = $patientResult;
        $monitoredItems = $doctor->getMonitoringItems($conn, $patientResult['patientID']);
    } else {
        $message = "<p style='color:red;'>No matching patient found. Please try again.</p>";
    }
}

// Set up new monitoring item
if ($action === 'setupMonitoring' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientID = $_POST['patientID'];
    $examCategories = $_POST['examCategories'] ?? [];

    // Pass doctorID as the current logged-in user ID
    $doctorID = $_SESSION['userID'];
    $result = $doctor->setMonitoring($conn, $patientID, $doctorID, $examCategories);

    if ($result) {
        $message = "<p style='color: green;'>New monitoring has been set successfully.</p>";
    } else {
        $message = "<p style='color: red;'>Failed to set monitoring. Please try again.</p>";
    }
}

// Delete monitoring item
if ($action === 'deleteMonitoring' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $monitorID = $_POST['monitorID'];
    $result = $doctor->deleteMonitoring($conn, $monitorID);

    if ($result) {
        $message = "<p style='color: green;'>Monitoring has been deleted successfully.</p>";
    } else {
        $message = "<p style='color: red;'>Failed to delete monitoring. Please try again.</p>";
    }
}

if ($action === 'logout') {
    $doctor->logout();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <h2>Doctor Dashboard</h2>
    <div class="container">
    <div class="navbar">
    <form method="POST" action="" class="navbar-form">
        <button type="submit" name="action" value="viewAccount">View Account</button>
        <button type="submit" name="action" value="modifyAccount">Modify Account</button>
        <button type="submit" name="action" value="prescribeExam">Prescribe Exam</button>
        <button type="submit" name="action" value="searchExamResults">Check Exam Results</button>
        <button type="submit" name="action" value="setMonitoring">Monitoring</button>
        <button type="submit" name="action" value="logout">Logout</button>
    </form>
    </div>

    <?php if ($action === 'viewAccount'): ?>
        <h3>View Account</h3>
        <p>Name: <?php echo htmlspecialchars($doctorInfo['name']); ?></p>
        <p>Email: <?php echo htmlspecialchars($doctorInfo['email']); ?></p>
        <p>Phone Number: <?php echo htmlspecialchars($doctorInfo['phoneNumber']); ?></p>
        <p>Working ID: <?php echo htmlspecialchars($doctorInfo['workingID']); ?></p>

    <?php elseif ($action === 'modifyAccount'): ?>
        <h3>Modify Account</h3>
        <div class="modify-account-container">
         <div class="modify-account">
        <form method="POST" action="">
        
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($doctorInfo['name']); ?>" required><br><br>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($doctorInfo['email']); ?>" required><br><br>
        </div>
        <div class="form-group">    
            <label for="phoneNumber">Phone Number:</label>
            <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($doctorInfo['phoneNumber']); ?>" required><br><br>
        </div>
            
            <input type="hidden" name="action" value="saveChanges">
            <input type="submit" value="Save Changes" style="display: block; margin: 0 auto;">
        </form>
    </div>
    <div class="change-password">
        <h3>Change Password</h3>
        <form method="POST" action="" >
        
        <div class="form-group"> 
            <label for="currentPassword">Current Password:</label>
            <input type="password" id="currentPassword" name="currentPassword" required><br><br>
        </div>
        <div class="form-group"> 
            <label for="newPassword">New Password:</label>
            <input type="password" id="newPassword" name="newPassword" required><br><br>
        </div>
        <div class="form-group"> 
            <label for="confirmPassword">Confirm New Password:</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required><br><br>
        </div>
            
            <input type="hidden" name="action" value="changePassword">
            <input type="submit" value="Change Password" style="display: block; margin: 0 auto;">
        </form>
    </div>

        <?php elseif ($action === 'prescribeExam'): ?>
        <h3>Search Patient</h3>
        <form method="POST" action="">
        
        <div class="form-group"> 
            <label for="patientName">Patient Name:</label>
            <input type="text" id="patientName" name="patientName" required><br><br>
        </div>
        <div class="form-group"> 
            <label for="dateOfBirth">Date of Birth (optional):</label>
            <input type="date" id="dateOfBirth" name="dateOfBirth"><br><br>
        </div>
        <div class="form-group"> 
            <label for="healthID">Health ID (optional):</label>
            <input type="text" id="healthID" name="healthID"><br><br>
        </div>
        
            <input type="hidden" name="action" value="searchPatient">
            <input type="submit" value="Search Patient" style="display: block; margin: 0 auto;">
        </form>

        <?php if ($patientResult): ?>
            <h3>Prescribe Exams for Patient: <?php echo htmlspecialchars($patientResult['name']); ?></h3>
            <form method="POST" action="">
                <input type="hidden" name="patientID" value="<?php echo htmlspecialchars($patientResult['patientID']); ?>">
                <div style="display: block; margin: 0 auto;">
                    <?php foreach ($examData as $examName => $items): ?>
                        <?php $examID = $examIDs[$examName] ?? null; ?>
                        <?php if ($examID && $examName === "Blood Test" && !empty($items)): ?>
                            <input type="checkbox" id="BloodTest" onchange="toggleSubCategories('BloodTestCategories')"> Blood Test<br>
                            <div id="BloodTestCategories" style="display:none; margin-left: 20px;">
                                <?php foreach ($items as $itemName => $itemID): ?>
                                    <input type="checkbox" name="examCategories[<?php echo $examID; ?>][]" value="<?php echo $itemID; ?>"> <?php echo $itemName; ?><br>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($examID): ?>
                            <input type="checkbox" name="examCategories[<?php echo $examID; ?>][]" value="<?php echo $examID; ?>"> <?php echo $examName; ?><br>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="action" value="submitPrescription">
                <input type="submit" value="Prescribe Exams" style="display: block; margin: 0 auto;">
            </form>
        <?php endif; ?>


        <?php elseif ($action === 'searchExamResults'): ?>
        <h3>Check Exam Results</h3>
        <form method="POST" action="">
        
        <div class="form-group"> 
            <label for="patientName">Patient Name:</label>
            <input type="text" id="patientName" name="patientName" required>
        </div>
        <div class="form-group"> 
            <label for="prescriptionDate">Prescription Date (optional):</label>
            <input type="date" id="prescriptionDate" name="prescriptionDate">
        </div>
            <label>Select Exam Type:</label>
            <div style="display: block; margin: 0 auto;">
                <!-- Exam type checkboxes with subcategories -->
                <?php foreach ($examData as $examName => $items): ?>
                    <?php $examID = $examIDs[$examName] ?? null; ?>
                    <?php if ($examID): ?>
                        <!-- Main Exam Checkbox -->
                        <input type="checkbox" id="<?php echo $examName; ?>" name="examCategories[<?php echo $examID; ?>][]" value="<?php echo $examID; ?>" <?php echo !empty($items) ? 'onchange="toggleSubCategories(\'' . $examName . 'Categories\')"' : ''; ?>>
                        <?php echo $examName; ?><br>

                        <!-- Subcategory checkboxes if there are items -->
                        <?php if (!empty($items)): ?>
                            <div id="<?php echo $examName; ?>Categories" style="display:none; margin-left: 20px;">
                                <?php foreach ($items as $itemName => $itemID): ?>
                                    <input type="checkbox" name="examCategories[<?php echo $examID; ?>][]" value="<?php echo $itemID; ?>"> <?php echo $itemName; ?><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?> <br>
            </div>
            
        <div class="form-group"> 
            <label for="isAbnormal">Only Abnormal Results:</label>
            <input type="checkbox" id="isAbnormal" name="isAbnormal" value="1">
        </div>
            <input type="hidden" name="action" value="executeSearchExamResults">
            <input type="submit" value="Search Results" style="display: block; margin: 0 auto;">
        </form>

        <?php elseif ($action === 'executeSearchExamResults'): ?>
        <h3>Exam Results</h3>
        <?php if (!empty($examResults)): ?>
            <table border="1">
                <tr>
                    <th>Patient Name</th>
                    <th>Exam Type</th>
                    <th>Exam Item</th>
                    <th>Prescription Date</th>
                    <th>Result</th>
                    <th>Abnormal</th>
                </tr>
                <?php foreach ($examResults as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['patientName']); ?></td>
                        <td><?php echo htmlspecialchars($result['examType']); ?></td>
                        <td><?php echo htmlspecialchars($result['examItem'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($result['prescriptionDate']); ?></td>
                        <td><?php echo htmlspecialchars($result['result']); ?></td>
                        <td><?php echo $result['isAbnormal'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No results found for the specified criteria.</p>
        <?php endif; ?>

    <!-- Set Monitoring: Displays search form for a patient -->
    <?php elseif ($action === 'setMonitoring'): ?>
        <h3>Set Monitoring for Patient</h3>
        <form method="POST" action="">
        
        <div class="form-group"> 
            <label for="patientName">Patient Name:</label>
            <input type="text" id="patientName" name="patientName" required>
        </div>
        <div class="form-group"> 
            <label for="dateOfBirth">Date of Birth (optional):</label>
            <input type="date" id="dateOfBirth" name="dateOfBirth">
        </div>
        <div class="form-group"> 
            <label for="healthID">Health ID (optional):</label>
            <input type="text" id="healthID" name="healthID">
        </div>
        
            <input type="hidden" name="action" value="searchPatientToMonitor">
            <input type="submit" value="Search Patient" style="display: block; margin: 0 auto;">
        </form>
    <?php endif; ?>

    <!-- Display Monitoring Options for Found Patient -->
    <?php if ($action === 'searchPatientToMonitor' && isset($patientResult)): ?>
        <h3>Set Monitoring for Patient: <?php echo htmlspecialchars($patientResult['name']); ?></h3>

        <!-- Display Currently Monitored Items if Available -->
        <?php if (!empty($monitoredItems)): ?> <br><br>
            <h4 style="display: flex; justify-content: center; align-items: center; margin: 0;">Currently Monitored Items</h4>
            <form method="POST" action="">
            <div class="form-group"> 
                <?php foreach ($monitoredItems as $item): ?>
                    
                    <p><?php echo htmlspecialchars($item['examName']) . ' - ' . htmlspecialchars($item['itemName']); ?></p>
                    <input type="hidden" name="monitorID" value="<?php echo htmlspecialchars($item['monitoringID']); ?>">
                    <button type="submit" name="action" value="deleteMonitoring">Delete</button><br>
                <?php endforeach; ?>
                </div>
            </form>
        <?php else: ?>
            <p>No current monitoring items found for this patient.</p>
        <?php endif; ?>
        <br><br>

        <!-- Form to Set New Monitoring -->
        <h4 style="display: flex; justify-content: center; align-items: center; margin: 0;">Set New Monitoring</h4>
        <form method="POST" action="">
        <div style="display: block; margin: 0 auto;">
            <input type="hidden" name="patientID" value="<?php echo htmlspecialchars($patientResult['patientID']); ?>">
            <?php foreach ($examData as $examName => $items): ?>
                <?php $examID = $examIDs[$examName] ?? null; ?>
                <?php if ($examID && $examName === "Blood Test" && !empty($items)): ?>
                    <input type="checkbox" id="BloodTest" onchange="toggleSubCategories('BloodTestCategories')"> Blood Test<br>
                    <div id="BloodTestCategories" style="display:none; margin-left: 20px;">
                        <?php foreach ($items as $itemName => $itemID): ?>
                            <input type="checkbox" name="examCategories[<?php echo $examID; ?>][]" value="<?php echo $itemID; ?>"> <?php echo $itemName; ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($examID): ?>
                    <input type="checkbox" name="examCategories[<?php echo $examID; ?>][]" value="<?php echo $examID; ?>"> <?php echo $examName; ?><br>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
            <input type="hidden" name="action" value="setupMonitoring">
            <input type="submit" value="Set Monitoring" style="display: block; margin: 0 auto;">
        </form>
    <?php endif; ?>

    <?php if (!empty($message)) echo $message; ?>

    

    <script>
        function toggleSubCategories(categoryID) {
            let category = document.getElementById(categoryID);
            category.style.display = category.style.display === 'none' ? 'block' : 'none';
        }
    </script>

    <?php if (!empty($prescriptionMessage)): ?>
        <div id="prescriptionMessage">
            <?php echo $prescriptionMessage; ?>
        </div>
    <?php endif; ?>
    </div>
</body>
</html>
