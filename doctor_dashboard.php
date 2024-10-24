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

if ($action === 'submitPrescription' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientID = $_POST['patientID'];
    $examCategories = $_POST['examCategories'] ?? [];

    if (!empty($examCategories)) {
        // Call the prescribeExam method from the Doctor class
        $result = $doctor->prescribeExam($conn, $patientID, $doctorID, $examCategories);
        
        if ($result) {
            echo "<script>alert('Exams prescribed successfully.'); window.location.href='doctor_dashboard.php?action=viewAccount';</script>";
        } else {
            echo "<script>alert('Failed to prescribe exams. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('No exams selected.');</script>";
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
</head>
<body>
    <h2>Doctor Dashboard</h2>
    
    <form method="POST" action="">
        <button type="submit" name="action" value="viewAccount">View Account</button>
        <button type="submit" name="action" value="modifyAccount">Modify Account</button>
        <button type="submit" name="action" value="prescribeExam">Prescribe Exam</button>
        <button type="submit" name="action" value="checkExamResults">Check Exam Results</button>
        <button type="submit" name="action" value="setMonitoring">Set Monitoring</button>
        <button type="submit" name="action" value="logout">Logout</button>
    </form>

    <?php if ($action === 'viewAccount'): ?>
        <h3>View Account</h3>
        <p>Name: <?php echo htmlspecialchars($doctorInfo['name']); ?></p>
        <p>Email: <?php echo htmlspecialchars($doctorInfo['email']); ?></p>
        <p>Phone Number: <?php echo htmlspecialchars($doctorInfo['phoneNumber']); ?></p>
        <p>Working ID: <?php echo htmlspecialchars($doctorInfo['workingID']); ?></p>

    <?php elseif ($action === 'modifyAccount'): ?>
        <h3>Modify Account</h3>
        <form method="POST" action="">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($doctorInfo['name']); ?>" required><br><br>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($doctorInfo['email']); ?>" required><br><br>
            
            <label for="phoneNumber">Phone Number:</label>
            <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($doctorInfo['phoneNumber']); ?>" required><br><br>
            
            <input type="hidden" name="action" value="saveChanges">
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

    <?php elseif ($action === 'prescribeExam' || !empty($patientResult)): ?>
        <h3>Search Patient</h3>
        <form method="POST" action="">
            <label for="patientName">Patient Name:</label>
            <input type="text" id="patientName" name="patientName" required><br><br>

            <label for="dateOfBirth">Date of Birth (optional):</label>
            <input type="date" id="dateOfBirth" name="dateOfBirth"><br><br>

            <label for="healthID">Health ID (optional):</label>
            <input type="text" id="healthID" name="healthID"><br><br>
            
            <input type="hidden" name="action" value="searchPatient">
            <input type="submit" value="Search Patient">
        </form>

        <?php if ($patientResult): ?>
    <h3>Prescribe Exams for Patient: <?php echo htmlspecialchars($patientResult['name']); ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="patientID" value="<?php echo htmlspecialchars($patientResult['patientID']); ?>">

        <!-- Blood Test -->
        <input type="checkbox" id="BloodTest" name="examCategories[Blood Test][]" value="Blood Test" onchange="toggleSubCategories('BloodTestCategories')"> Blood Test<br>
        <div id="BloodTestCategories" style="display:none; margin-left: 20px;">
            <input type="checkbox" name="examCategories[Blood Test][]" value="Routine Hematology"> Routine Hematology<br>
            <input type="checkbox" name="examCategories[Blood Test][]" value="Coagulation"> Coagulation<br>
            <input type="checkbox" name="examCategories[Blood Test][]" value="Routine Chemistry"> Routine Chemistry<br>
            <input type="checkbox" name="examCategories[Blood Test][]" value="Renal Function"> Renal Function<br>
            <input type="checkbox" name="examCategories[Blood Test][]" value="Liver Function"> Liver Function<br>
            <input type="checkbox" name="examCategories[Blood Test][]" value="Pancreas Function"> Pancreas Function<br>
            <input type="checkbox" name="examCategories[Blood Test][]" value="Endocrinology"> Endocrinology<br>
            <input type="checkbox" name="examCategories[Blood Test][]" value="Tumor Markers"> Tumor Markers<br>
        </div><br>

        <!-- Urine Test -->
        <input type="checkbox" id="UrineTest" name="examCategories[Urine Test][]" value="Urine Test" onchange="toggleSubCategories('UrineTestCategories')"> Urine Test<br>
        <div id="UrineTestCategories" style="display:none; margin-left: 20px;">
            <input type="checkbox" name="examCategories[Urine Test][]" value="Urinalysis"> Urinalysis<br>
            <input type="checkbox" name="examCategories[Urine Test][]" value="Urine Culture"> Urine Culture<br>
            <input type="checkbox" name="examCategories[Urine Test][]" value="Urine Protein Test"> Urine Protein Test<br>
            <input type="checkbox" name="examCategories[Urine Test][]" value="Urine Pregnancy Test"> Urine Pregnancy Test<br>
            <input type="checkbox" name="examCategories[Urine Test][]" value="Urine Drug Screening"> Urine Drug Screening<br>
            <input type="checkbox" name="examCategories[Urine Test][]" value="Microalbumin Test"> Microalbumin Test<br>
        </div><br>

        <!-- Ultrasound -->
        <input type="checkbox" id="Ultrasound" name="examCategories[Ultrasound][]" value="Ultrasound" onchange="toggleSubCategories('UltrasoundCategories')"> Ultrasound<br>
        <div id="UltrasoundCategories" style="display:none; margin-left: 20px;">
            <input type="checkbox" name="examCategories[Ultrasound][]" value="Abdominal Ultrasound"> Abdominal Ultrasound<br>
            <input type="checkbox" name="examCategories[Ultrasound][]" value="Pelvic Ultrasound"> Pelvic Ultrasound<br>
            <input type="checkbox" name="examCategories[Ultrasound][]" value="Obstetric Ultrasound"> Obstetric Ultrasound<br>
            <input type="checkbox" name="examCategories[Ultrasound][]" value="Thyroid Ultrasound"> Thyroid Ultrasound<br>
            <input type="checkbox" name="examCategories[Ultrasound][]" value="Echocardiogram"> Echocardiogram<br>
            <input type="checkbox" name="examCategories[Ultrasound][]" value="Doppler Ultrasound"> Doppler Ultrasound<br>
            <input type="checkbox" name="examCategories[Ultrasound][]" value="Breast Ultrasound"> Breast Ultrasound<br>
            <input type="checkbox" name="examCategories[Ultrasound][]" value="Musculoskeletal Ultrasound"> Musculoskeletal Ultrasound<br>
        </div><br>

        <!-- X-ray -->
        <input type="checkbox" id="Xray" name="examCategories[X-ray][]" value="X-ray" onchange="toggleSubCategories('XrayCategories')"> X-ray<br>
        <div id="XrayCategories" style="display:none; margin-left: 20px;">
            <input type="checkbox" name="examCategories[X-ray][]" value="Chest X-ray"> Chest X-ray<br>
            <input type="checkbox" name="examCategories[X-ray][]" value="Abdominal X-ray"> Abdominal X-ray<br>
            <input type="checkbox" name="examCategories[X-ray][]" value="Bone X-ray"> Bone X-ray<br>
            <input type="checkbox" name="examCategories[X-ray][]" value="Dental X-ray"> Dental X-ray<br>
            <input type="checkbox" name="examCategories[X-ray][]" value="Spinal X-ray"> Spinal X-ray<br>
            <input type="checkbox" name="examCategories[X-ray][]" value="Sinus X-ray"> Sinus X-ray<br>
            <input type="checkbox" name="examCategories[X-ray][]" value="Mammogram"> Mammogram<br>
        </div><br>

        <!-- CT Scan -->
        <input type="checkbox" id="CTScan" name="examCategories[CT Scan][]" value="CT Scan" onchange="toggleSubCategories('CTScanCategories')"> CT Scan<br>
        <div id="CTScanCategories" style="display:none; margin-left: 20px;">
            <input type="checkbox" name="examCategories[CT Scan][]" value="CT Angiography"> CT Angiography<br>
            <input type="checkbox" name="examCategories[CT Scan][]" value="Head/Brain CT"> Head/Brain CT<br>
            <input type="checkbox" name="examCategories[CT Scan][]" value="Chest CT"> Chest CT<br>
            <input type="checkbox" name="examCategories[CT Scan][]" value="Abdominal and Pelvic CT"> Abdominal and Pelvic CT<br>
            <input type="checkbox" name="examCategories[CT Scan][]" value="Sinus CT"> Sinus CT<br>
            <input type="checkbox" name="examCategories[CT Scan][]" value="Spinal CT"> Spinal CT<br>
            <input type="checkbox" name="examCategories[CT Scan][]" value="CT Colonography"> CT Colonography<br>
        </div><br>

        <!-- ECG -->
        <input type="checkbox" id="ECG" name="examCategories[ECG][]" value="ECG" onchange="toggleSubCategories('ECGCategories')"> ECG<br>
        <div id="ECGCategories" style="display:none; margin-left: 20px;">
            <input type="checkbox" name="examCategories[ECG][]" value="Resting ECG"> Resting ECG<br>
            <input type="checkbox" name="examCategories[ECG][]" value="Exercise (Stress) ECG"> Exercise (Stress) ECG<br>
            <input type="checkbox" name="examCategories[ECG][]" value="Holter Monitor ECG"> Holter Monitor ECG<br>
            <input type="checkbox" name="examCategories[ECG][]" value="Event Monitor ECG"> Event Monitor ECG<br>
            <input type="checkbox" name="examCategories[ECG][]" value="12-Lead ECG"> 12-Lead ECG<br>
            <input type="checkbox" name="examCategories[ECG][]" value="Ambulatory ECG"> Ambulatory ECG<br>
        </div><br>

        <input type="hidden" name="action" value="submitPrescription">
        <input type="submit" value="Prescribe Exams">
    </form>
<?php endif; ?>

<script>
    function toggleSubCategories(categoryID) {
        let category = document.getElementById(categoryID);
        category.style.display = category.style.display === 'none' ? 'block' : 'none';
    }
</script>
      
    <?php endif; ?>
</body>
</html>
