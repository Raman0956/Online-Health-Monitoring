<?php
session_start();
require_once('database.php');
require_once('Staff.php');

if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

$staffID = $_SESSION['userID'];
$staff = new Staff($staffID, "Staff Name", "staff@example.com", "1234567890", "hashed_password", "Staff");

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$staffInfo = $staff->getAccountInfo($conn);

if ($action === 'saveChanges' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phoneNumber'];
    $success = $staff->modifyAccount($conn, $name, $email, $phoneNumber);

    if ($success) {
        echo "<script>alert('Account updated successfully.'); window.location.href='staff_dashboard.php?action=viewAccount';</script>";
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
        echo "<script>alert('Passwords do not match.'); window.location.href='staff_dashboard.php?action=modifyAccount';</script>";
    } else {
        $result = $staff->changePassword($conn, $currentPassword, $newPassword);
        if ($result === true) {
            echo "<script>alert('Password changed successfully.'); window.location.href='staff_dashboard.php?action=modifyAccount';</script>";
        } else {
            echo "<script>alert('Current password is incorrect'); window.location.href='staff_dashboard.php?action=modifyAccount';</script>";
        }
    }
}


if ($action === 'getPendingExams') {
    $pendingExams = $staff->getPendingExams($conn);
}

if ($action === 'submitExamResult' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $prescriptionID = $_POST['prescriptionID'];
    $result = $_POST['result'];
    $isAbnormal = isset($_POST['isAbnormal']) ? 1 : 0;
    $success = $staff->modifyExamResult($conn, $prescriptionID, $result, $isAbnormal);

    if ($success) {
        echo "<script>alert('Exam result updated successfully.');window.location.href='staff_dashboard.php?action=getPendingExams';</script>";
    } else {
        echo "<script>alert('Failed to update exam result.');</script>";
    }
}



if ($action === 'searchExams' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientName = $_POST['patientName'] ?? null;
    $dateOfBirth = $_POST['dateOfBirth'] ?? null;
    $healthID = $_POST['healthID'] ?? null;
    $examResults = $staff->searchExamsByPatientDetails($conn, $patientName, $dateOfBirth, $healthID);
}

if ($action === 'modifyExamResult' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $prescriptionID = $_POST['prescriptionID'];
    $result = $_POST['result'];
    $isAbnormal = isset($_POST['isAbnormal']) ? 1 : 0;

    $success = $staff->modifyExamResult($conn, $prescriptionID, $result, $isAbnormal);

    if ($success) {
        echo "<script>alert('Exam result updated successfully.'); window.location.href='staff_dashboard.php?action=searchExams';</script>";
    } else {
        echo "<script>alert('Failed to update exam result.');</script>";
    }
}

if ($action === 'logout') {
    $staff->logout();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
</head>
<body>
    <h2>Staff Dashboard</h2>
    
    <form method="POST" action="">
        <button type="submit" name="action" value="viewAccount">View Account</button>
        <button type="submit" name="action" value="modifyAccount">Modify Account</button>
        <button type="submit" name="action" value="getPendingExams">View All Pending Exams</button>
        <button type="submit" name="action" value="searchExams">Search By Patient Details</button>
        <button type="submit" name="action" value="logout">Logout</button>
    </form>

    <?php if ($action === 'viewAccount'): ?>
        <h3>View Account</h3>
        <p>Name: <?php echo htmlspecialchars($staffInfo['name']); ?></p>
        <p>Email: <?php echo htmlspecialchars($staffInfo['email']); ?></p>
        <p>Phone Number: <?php echo htmlspecialchars($staffInfo['phoneNumber']); ?></p>
        <p>Working ID: <?php echo htmlspecialchars($staffInfo['workingID']); ?></p>
    <?php endif; ?>

    <?php if ($action === 'modifyAccount'): ?>
        <h3>Modify Account</h3>
        <form method="POST" action="">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($staffInfo['name']); ?>" required><br><br>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($staffInfo['email']); ?>" required><br><br>
            
            <label for="phoneNumber">Phone Number:</label>
            <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($staffInfo['phoneNumber']); ?>" required><br><br>
            
            <input type="hidden" name="action" value="modifyAccount">
            <input type="submit" value="Save Changes">
        </form>
        <h3>Change Password</h3>
        <form method="POST" action="" >
            <label for="currentPassword">Current Password:</label>
            <input type="password" id="currentPassword" name="currentPassword" required><br><br>

            <label for="newPassword">New Password:</label>
            <input type="password" id="newPassword" name="newPassword" required><br><br>

            <label for="confirmPassword">Confirm New Password:</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required><br><br>

            <input type="hidden" name="action" value="changePassword">
            <input type="submit" value="Change Password">
        </form>
    <?php endif; ?>

    <?php if ($action === 'getPendingExams' && !empty($pendingExams)): ?>
            <h3>All Pending Exams</h3>
            <table border="1">
                <tr>
                    <th>Patient Name</th>
                    <th>Date of Birth</th>
                    <th>Health ID</th>
                    <th>Exam Type</th>
                    <th>Exam Item</th>
                    <th>Prescription Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($pendingExams as $exam): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['patientName']); ?></td>
                        <td><?php echo htmlspecialchars($exam['dateOfBirth']); ?></td>
                        <td><?php echo htmlspecialchars($exam['healthID']); ?></td>
                        <td><?php echo htmlspecialchars($exam['examType']); ?></td>
                        <td><?php echo htmlspecialchars($exam['examItem']); ?></td>
                        <td><?php echo htmlspecialchars($exam['prescriptionDate']); ?></td>
                        <td><?php echo htmlspecialchars($exam['status']); ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="prescriptionID" value="<?php echo $exam['prescriptionID']; ?>">
                                <label>
                                    Result:
                                    <input type="text" name="result" required>
                                </label>
                                <label>
                                    <input type="checkbox" name="isAbnormal"> Abnormal
                                </label>
                                <input type="hidden" name="action" value="submitExamResult">
                                <input type="submit" value="Submit Result">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>



        <?php if ($action === 'searchExams'): ?>  
    <h3>Search Exams by Patient</h3>
    <form method="POST" action="">
        <label for="patientName">Patient Name:</label>
        <input type="text" id="patientName" name="patientName"><br><br>

        <label for="dateOfBirth">Date of Birth (optional):</label>
        <input type="date" id="dateOfBirth" name="dateOfBirth"><br><br>

        <label for="healthID">Health ID (optional):</label>
        <input type="text" id="healthID" name="healthID"><br><br>

        <input type="hidden" name="action" value="searchExams">
        <input type="submit" value="Search">
    </form>

    <?php if (isset($examResults) && !empty($examResults)): ?>
    <h3>Exam Results for <?php echo htmlspecialchars($patientName ?? ""); ?></h3>
    <table border="1">
        <tr>
            <th>Patient Name</th>
            <th>Date of Birth</th>
            <th>Health ID</th>
            <th>Exam Type</th>
            <th>Exam Item</th>
            <th>Prescription Date</th>
            <th>Status</th>
            <th>Result</th>
            <th>Abnormal</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($examResults as $exam): ?>
            <tr>
                <td><?php echo htmlspecialchars($exam['patientName']); ?></td>
                <td><?php echo htmlspecialchars($exam['dateOfBirth']); ?></td>
                <td><?php echo htmlspecialchars($exam['healthID']); ?></td>
                <td><?php echo htmlspecialchars($exam['examType']); ?></td>
                <td><?php echo htmlspecialchars($exam['examItem'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($exam['prescriptionDate']); ?></td>
                <td><?php echo htmlspecialchars($exam['status']); ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="text" name="result" value="<?php echo htmlspecialchars($exam['result'] ?? ''); ?>" required>
                </td>
                <td>
                    <input type="checkbox" name="isAbnormal" <?php echo (!empty($exam['isAbnormal']) && $exam['isAbnormal']) ? 'checked' : ''; ?>>
                </td>
                <td>
                    <input type="hidden" name="prescriptionID" value="<?php echo htmlspecialchars($exam['prescriptionID']); ?>">
                    <input type="hidden" name="action" value="modifyExamResult">
                    <input type="submit" value="Modify">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p>No exams found for this patient.</p>
    <?php endif; ?>
<?php endif; ?>




</body>
</html>
