<?php
session_start();
require_once('database.php');
require_once('Patient.php');

if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'Patient') {
    header("Location: login.php");
    exit();
}

$isApproved = $_SESSION['isApproved'] ?? false;

if ($isApproved) {

    $patientID = $_SESSION['userID'];
    $patient = new Patient($patientID, "Patient Name", "Patient@example.com", "1234567890", "hashed_password", "Patient", "", "");

    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
    $patientInfo = $patient->getAccountInfo($conn);

    echo "<h2>Welcome to your dashboard " . htmlspecialchars($patientInfo['name']) . "!</h2>";


    if ($action === 'saveChanges' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phoneNumber = $_POST['phoneNumber'];
        $dateOfBirth = $_POST['dateOfBirth'];
        $healthID = $_POST['healthID'];

        $success = $patient->modifyAccount($conn, $name, $email, $phoneNumber,$dateOfBirth,$healthID);

        if ($success) {
            echo "<script>alert('Account updated successfully.'); window.location.href='patient_dashboard.php?action=viewAccount';</script>";
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
            echo "<script>alert('Passwords do not match.'); window.location.href='patient_dashboard.php?action=modifyAccount';</script>";
        } else {
            $result = $patient->changePassword($conn, $currentPassword, $newPassword);
            if ($result === true) {
                echo "<script>alert('Password changed successfully.'); window.location.href='patient_dashboard.php?action=modifyAccount';</script>";
            } else {
                echo "<script>alert('Current password is incorrect'); window.location.href='patient_dashboard.php?action=modifyAccount';</script>";
            }
        }
    }

    $examIDs = $patient->getExamIDs($conn);
    $bloodTestItems = $patient->getBloodTestItemIDs($conn, $examIDs['Blood Test'] ?? null);
    $examData = [
        'Blood Test' => $bloodTestItems, // Using the fetched blood test items here
        'Urine Test' => [],
        'Ultrasound' => [],
        'X-ray' => [],
        'CT Scan' => [],
        'ECG' => []
    ];

    if ($action === 'executeSearchExamResults' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
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
        $examResults = $patient->searchExamResults($conn, $patientID, $prescriptionDate, $selectedExams, $isAbnormal);
    }

    if ($action === 'logout') {
        $patient->logout();
    }



} else {
    echo "<h2>Account Pending Approval</h2>";
    echo "<p>Your account is currently awaiting approval. Please check back later or contact support.</p>";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container">
    
    <div class="navbar">
    <form method="POST" action="" class="navbar-form">
        <button type="submit" name="action" value="viewAccount">View Account</button>
        <button type="submit" name="action" value="modifyAccount">Modify Account</button>
        <button type="submit" name="action" value="searchExamResults">Check Exam Results</button>
        <button type="submit" name="action" value="logout">Logout</button>
    </form>
</div>

    <?php if ($action === 'viewAccount'): ?>
        <h3>View Account</h3>
        <p>Name: <?php echo htmlspecialchars($patientInfo['name']); ?></p>
        <p>Email: <?php echo htmlspecialchars($patientInfo['email']); ?></p>
        <p>Phone Number: <?php echo htmlspecialchars($patientInfo['phoneNumber']); ?></p>
        <p>Health ID: <?php echo htmlspecialchars($patientInfo['healthID']); ?></p>
        <p>Date Of Birth: <?php echo htmlspecialchars($patientInfo['dateOfBirth']); ?></p>

    <?php elseif ($action === 'modifyAccount'): ?>
        <h3>Modify Account</h3>
        <div class="modify-account-container">
        <div class="modify-account">
        <form method="POST" action="">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($patientInfo['name']); ?>" required>        
         </div>
            <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patientInfo['email']); ?>" required>
            </div>
            <div class="form-group">
            <label for="phoneNumber">Phone Number:</label>
            <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($patientInfo['phoneNumber']); ?>" required>
            </div>
            <div class="form-group">
            <label for="healthID">Health ID:</label>
            <input type="text" id="healthID" name="healthID" value="<?php echo htmlspecialchars($patientInfo['healthID']); ?>" required>
            </div>              
            <div class="form-group">
            <label for="dateOFBirth">Date OF Birth:</label>
            <input type="text" id="dateOFBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($patientInfo['dateOfBirth']); ?>" required>
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
            <input type="password" id="currentPassword" name="currentPassword" required>
            </div>
            <div class="form-group">
            <label for="newPassword">New Password:</label>
            <input type="password" id="newPassword" name="newPassword" required>
            </div>
            <div class="form-group">
            <label for="confirmPassword">Confirm New Password:</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required>
            </div>
            <input type="hidden" name="action" value="changePassword">
            <input type="submit" value="Change Password" style="display: block; margin: 0 auto;">
        </form>
    </div>

    <?php elseif ($action === 'searchExamResults'): ?>
        <h3>Check Exam Results for <?php echo htmlspecialchars($patientInfo['name']); ?> </h3>
        <form method="POST" action="">
        <div class="form-group">
            <label for="prescriptionDate">Prescription Date (optional):</label>
            <input type="date" id="prescriptionDate" name="prescriptionDate"><br><br>
        </div>
        
                
                    <label>Select Exam Type:</label>
                    <div style="display: block; margin: 0 auto;">

                    <!-- Exam type checkboxes with subcategories -->
                    <?php foreach ($examData as $examName => $items): ?>
                        <?php $examID = $examIDs[$examName] ?? null; ?>
                        <?php if ($examID): ?>
                            <!-- Main Exam Checkbox -->
                            <input type="checkbox" id="<?php echo $examName; ?>" name="examCategories[<?php echo $examID; ?>][]" value="<?php echo $examID; ?>" <?php echo !empty($items) ? 'onchange="toggleSubCategories(\'' . $examName . 'Categories\')"' : ''; ?>>
                            <?php echo $examName; ?>

                            <!-- Subcategory checkboxes if there are items -->
                            <?php if (!empty($items)): ?>
                                <div id="<?php echo $examName; ?>Categories" style="display:none; margin-left: 20px;">
                                    <?php foreach ($items as $itemName => $itemID): ?>
                                        <input type="checkbox" name="examCategories[<?php echo $examID; ?>][]" value="<?php echo $itemID; ?>"> <?php echo $itemName; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?> <br>
                    </div>
                     

            <div class="form-group">

            <label for="isAbnormal">Only Abnormal Results:</label>
            <input type="checkbox" id="isAbnormal" name="isAbnormal" value="1"><br><br>
            </div>
            <input type="hidden" name="action" value="executeSearchExamResults">
            <input type="submit" value="Search Results" style="display: block; margin: 0 auto;">
        </form>

        <?php elseif ($action === 'executeSearchExamResults'): ?>
        <h3>Exam Results for <?php echo htmlspecialchars($patientInfo['name']); ?></h3>
        <?php if (!empty($examResults)): ?>
            <table border="1">
                <tr>
                    <th>Exam Type</th>
                    <th>Exam Item</th>
                    <th>Prescription Date</th>
                    <th>Result</th>
                    <th>Abnormal</th>
                </tr>
                <?php foreach ($examResults as $result): ?>
                    <tr>
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

        <?php endif; ?>

        <script>
        function toggleSubCategories(categoryID) {
            let category = document.getElementById(categoryID);
            category.style.display = category.style.display === 'none' ? 'block' : 'none';
        }
    </script>

</body>