<?php
session_start();
require_once('database.php');
require_once('Admin.php');

$admin = new Admin($_SESSION['userID'], "Admin Name", "admin@example.com", "1234567890", "hashed_password", "Administrator");
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check if the admin is logged in
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

// Handle form submissions based on selected action
$deleteResults = [];
$pendingPatients = [];

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Approve Patient
    if ($action === 'approvePatient' && isset($_POST['patientID'])) {
        $patientID = $_POST['patientID'];
        $admin->approvePatientRegistration($conn, $patientID);
    }

    // Create Doctor
    if ($action === 'createDoctor' && isset($_POST['doctorName'])) {
        $doctorName = $_POST['doctorName'];
        $doctorEmail = $_POST['doctorEmail'];
        $doctorPhoneNumber = $_POST['doctorPhoneNumber'];
        $doctorPassword = $_POST['doctorPassword'];
        $doctorWorkingID = $_POST['doctorWorkingID'];
        
        
        $filename= $_FILES["image"]["name"];
        $tempname = $_FILES["image"]["tmp_name"];
        $folder = "images/".$filename;
        move_uploaded_file($tempname , $folder);
        
        $admin->createDoctor($conn, $doctorName, $doctorEmail, $doctorPhoneNumber, $doctorPassword, $doctorWorkingID, $folder);
    }

    // Create Staff
    if ($action === 'createStaff' && isset($_POST['staffName'])) {
        $staffName = $_POST['staffName'];
        $staffEmail = $_POST['staffEmail'];
        $staffPhoneNumber = $_POST['staffPhoneNumber'];
        $staffPassword = $_POST['staffPassword'];
        $staffWorkingID = $_POST['staffWorkingID'];

        $filename1= $_FILES["image"]["name"];
        $tempname1 = $_FILES["image"]["tmp_name"];
        $folder = "images/".$filename1;
        move_uploaded_file($tempname1 , $folder);
        
        $admin->createStaff($conn, $staffName, $staffEmail, $staffPhoneNumber, $staffPassword, $staffWorkingID,$folder);
    }

    // Handle form submissions for deleting a user
    if ($action === 'deleteUserSearch' && isset($_POST['userType']) && isset($_POST['searchTerm'])) {
        $userType = $_POST['userType'];
        $searchTerm = $_POST['searchTerm'];

        try {
            // Determine query based on user type
            if ($userType === 'Patient') {
                $sql = "SELECT User.userID, User.name, User.email FROM User 
                        INNER JOIN Patient ON User.userID = Patient.patientID 
                        WHERE (User.name LIKE :search OR User.email LIKE :search OR User.userID = :id)";
            } elseif ($userType === 'Doctor') {
                $sql = "SELECT User.userID, User.name, Doctor.workingID FROM User 
                        INNER JOIN Doctor ON User.userID = Doctor.doctorID 
                        WHERE (User.name LIKE :search OR Doctor.workingID LIKE :search OR User.userID = :id)";
            } elseif ($userType === 'Staff') {
                $sql = "SELECT User.userID, User.name, Staff.workingID FROM User 
                        INNER JOIN Staff ON User.userID = Staff.staffID 
                        WHERE (User.name LIKE :search OR Staff.workingID LIKE :search OR User.userID = :id)";
            }

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':search', "%$searchTerm%");
            $stmt->bindValue(':id', $searchTerm, PDO::PARAM_INT);
            $stmt->execute();
            $deleteResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Process actual deletion of a user
    if ($action === 'deleteUser' && isset($_POST['userID'])) {
        $userID = $_POST['userID'];
        $admin->deleteAccount($conn, $userID);
    }

    // Fetch pending patient requests if needed
    if ($action === 'viewPendingRequests') {
        try {
            $sql = "SELECT User.userID, User.name, User.email, User.phoneNumber, Patient.healthID, Patient.dateOfBirth 
                    FROM User 
                    INNER JOIN Patient ON User.userID = Patient.patientID 
                    WHERE Patient.isApproved = FALSE";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $pendingPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }


    if ($action === 'searchExams' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $patientName = $_POST['patientName'] ?? null;
        $dateOfBirth = $_POST['dateOfBirth'] ?? null;
        $healthID = $_POST['healthID'] ?? null;
        $examResults = $admin->searchExamsByPatientDetails($conn, $patientName, $dateOfBirth, $healthID);
    }

    if ($action === 'deleteExamResult' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
        $prescriptionID = $_POST['prescriptionID'] ?? null;
        if ($prescriptionID) {
            $resultMessage = $admin->deleteExam($conn, $prescriptionID);
            echo "<script>alert('$resultMessage'); window.location.href='admin_dashboard.php?action=searchExams';</script>";
        } else {
            echo "<script>alert('No prescription ID provided for deletion.');</script>";
        }
    }    


    // Initialize variables
    $reportResults = [];
    $selectedYear = date("Y");
    $selectedMonth = date("m");


    if ($action === 'generateReport' && $_SERVER['REQUEST_METHOD'] === 'POST'){
        $selectedYear = $_POST['year'];
        $selectedMonth = $_POST['month'];
        $reportResults = $admin->generateReport($conn, $selectedYear, $selectedMonth);
    }
    

    $predictionReportResults = [];

    // If the action is to generate the health prediction report
    if ($action === 'generateHealthPredictionReport' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $selectedYear = $_POST['year'];
        $predictionReportResults = $admin->generateHealthPredictionReport($conn, $selectedYear);
    }

    if ($action === 'logout') {
        $admin->logout();
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container">
    <h2>Admin Dashboard</h2>
    
    <!-- Dashboard Options Menu -->
    <div class="navbar">
    <form method="POST" action="" class="navbar-form">
        <button type="submit" name="action" value="viewPendingRequests">View Pending Requests</button>
        <button type="submit" name="action" value="createDoctorForm">Create New Doctor Account</button>
        <button type="submit" name="action" value="createStaffForm">Create New Staff Account</button>
        <button type="submit" name="action" value="deleteAccountForm">Delete Account</button>
        <button type="submit" name="action" value="searchExams">Delete Exam results</button>
        <button type="submit" name="action" value="report">Reports</button>
        <button type="submit" name="action" value="predictionReport">Health Prediction Report</button>
        <button type="submit" name="action" value="logout">Logout</button>
    </form>
    </div>

    <!-- Display Section based on Selection -->
    <?php if (isset($action) && $action === 'viewPendingRequests' && !empty($pendingPatients)): ?>
        <h3>Pending Patient Registration Requests</h3>
        <table border="1">
            <tr>
                <th>Patient ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Health ID</th>
                <th>Date of Birth</th>
                <th>Action</th>
            </tr>
            <?php foreach ($pendingPatients as $patient): ?>
                <tr>
                    <td><?php echo htmlspecialchars($patient['userID']); ?></td>
                    <td><?php echo htmlspecialchars($patient['name']); ?></td>
                    <td><?php echo htmlspecialchars($patient['email']); ?></td>
                    <td><?php echo htmlspecialchars($patient['phoneNumber']); ?></td>
                    <td><?php echo htmlspecialchars($patient['healthID']); ?></td>
                    <td><?php echo htmlspecialchars($patient['dateOfBirth']); ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="patientID" value="<?php echo $patient['userID']; ?>">
                            <input type="hidden" name="action" value="approvePatient">
                            <input type="submit" value="Approve">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <?php if (isset($action) && $action === 'createDoctorForm'): ?>
        <h3>Create New Doctor Account</h3>
        <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="doctorName">Name:</label>
            <input type="text" id="doctorName" name="doctorName" required>
        </div>
        <div class="form-group">    
            <label for="doctorEmail">Email:</label>
            <input type="email" id="doctorEmail" name="doctorEmail" required>
        </div>   
        <div class="form-group">
            <label for="doctorPhoneNumber">Phone Number:</label>
            <input type="text" id="doctorPhoneNumber" name="doctorPhoneNumber" required>
        </div>
        <div class="form-group">
            <label for="doctorPassword">Password:</label>
            <input type="password" id="doctorPassword" name="doctorPassword" required>
        </div>
        <div class="form-group">
            <label for="doctorWorkingID">Working ID:</label>
            <input type="text" id="doctorWorkingID" name="doctorWorkingID" required>
        </div>
        <div class="form-group">
        <label for="file">Add Image</label>
        <input type="file" name="image" id="image" required>
        </div>

            <input type="hidden" name="action" value="createDoctor">
            <input type="submit" value="Create Doctor" style="display: block; margin: 0 auto;">
        </form>

    <?php endif; ?>

    <?php if (isset($action) && $action === 'createStaffForm'): ?>
        <h3>Create New Staff Account</h3>
        <form method="POST" action="" enctype="multipart/form-data">
        
        <div class="form-group">   
            <label for="staffName">Name:</label>
            <input type="text" id="staffName" name="staffName" required>
        </div>
        <div class="form-group">    
            <label for="staffEmail">Email:</label>
            <input type="email" id="staffEmail" name="staffEmail" required>
        </div>
        <div class="form-group">   
            <label for="staffPhoneNumber">Phone Number:</label>
            <input type="text" id="staffPhoneNumber" name="staffPhoneNumber" required>
        </div>
        <div class="form-group">   
            <label for="staffPassword">Password:</label>
            <input type="password" id="staffPassword" name="staffPassword" required>
        </div>
        <div class="form-group">   
            <label for="staffWorkingID">Working ID:</label>
            <input type="text" id="staffWorkingID" name="staffWorkingID" required>
        </div>
        <div class="form-group">

        <label for="file">Add Image</label>
        <input type="file" name="image" id="image" required>
        </div>
            <input type="hidden" name="action" value="createStaff">
            <input type="submit" value="Create Staff" style="display: block; margin: 0 auto;">
        </form>
    <?php endif; ?>

    <?php if (isset($action) && $action === 'deleteAccountForm'): ?>
        <h3>Delete User Account</h3>
        <form method="POST" action="">
        
        <div class="form-group">  
            <label for="userType">Select User Type:</label>
            <select id="userType" name="userType" required>
                <option value="">--Select User Type--</option>
                <option value="Patient">Patient</option>
                <option value="Doctor">Doctor</option>
                <option value="Staff">Staff</option>
            </select>
        </div>
        <div class="form-group">  
            <label for="searchTerm">Search by Name, Email, User ID (for Patient) or Working ID (for Doctor/Staff):</label>
            <input type="text" id="searchTerm" name="searchTerm" required>
        </div>
            
            <input type="hidden" name="action" value="deleteUserSearch">
            <input type="submit" value="Search" style="display: block; margin: 0 auto;">
        </form>
        <?php endif; ?>

        <!-- Display Search Results for Deletion -->
        <?php if (!empty($deleteResults)): ?>
            <h3>Search Results</h3>
            <table border="1">
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <?php if ($_POST['userType'] === 'Patient'): ?>
                        <th>Email</th>
                    <?php else: ?>
                        <th>Working ID</th>
                    <?php endif; ?>
                    <th>Action</th>
                </tr>
                <?php foreach ($deleteResults as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['userID']); ?></td>
                        <td><?php echo htmlspecialchars($result['name']); ?></td>
                        <?php if ($_POST['userType'] === 'Patient'): ?>
                            <td><?php echo htmlspecialchars($result['email']); ?></td>
                        <?php else: ?>
                            <td><?php echo htmlspecialchars($result['workingID']); ?></td>
                        <?php endif; ?>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="userID" value="<?php echo $result['userID']; ?>">
                                <input type="hidden" name="action" value="deleteUser">
                                <input type="submit" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
        <?php elseif (isset($_POST['action']) && $_POST['action'] === 'deleteUserSearch'): ?>
            <p>No results found for the specified search criteria.</p>
        <?php endif; ?>
   
    
    <?php if (isset($action) && $action === 'searchExams'): ?>  
    <h3>Search Exams by Patient</h3>
    <form method="POST" action="">
    
    <div class="form-group"> 
        <label for="patientName">Patient Name:</label>
        <input type="text" id="patientName" name="patientName">
    </div>
    <div class="form-group"> 
        <label for="dateOfBirth">Date of Birth (optional):</label>
        <input type="date" id="dateOfBirth" name="dateOfBirth">
    </div>
    <div class="form-group"> 
        <label for="healthID">Health ID (optional):</label>
        <input type="text" id="healthID" name="healthID">
    </div>
        
        <input type="hidden" name="action" value="searchExams">
        <input type="submit" value="Search" style="display: block; margin: 0 auto;">
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
                <td><?php echo htmlspecialchars($exam['result'] ?? ''); ?></td>
                <td><?php echo (isset($exam['isAbnormal']) && $exam['isAbnormal']) ? 'Yes' : 'No'; ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="prescriptionID" value="<?php echo htmlspecialchars($exam['prescriptionID']); ?>">
                        <input type="hidden" name="action" value="deleteExamResult">
                        <input type="submit" value="Delete">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No exams found for this patient.</p>
<?php endif; ?>

<?php elseif ((isset($action) && $action === 'report')): ?>

    <h2>Generate Monthly or Yearly Report</h2>

    <!-- Report Form -->
    <form method="POST" action="">
    
    <div class="form-group"> 
    <label for="year">Year:</label>
    <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($selectedYear); ?>" required>
    </div>
    <div class="form-group"> 
    <label for="month">Month (optional for specific monthly report):</label>
    <select id="month" name="month">
        <option value="">-- Select Month --</option>
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?php echo $m; ?>">
                <?php echo date("F", mktime(0, 0, 0, $m, 1)); ?>
            </option>
        <?php endfor; ?>
    </select>
    </div>

    <input type="hidden" name="action" value="generateReport">
    <input type="submit" value="Generate Report" style="display: block; margin: 0 auto;">
        
    </form>

    <?php elseif ($action === 'generateReport' && !empty($reportResults)): ?>
        
        <h3>Testing Summary Report for <?php echo $selectedMonth ? htmlspecialchars(date("F Y", mktime(0, 0, 0, $selectedMonth, 1, $selectedYear))) : htmlspecialchars($selectedYear); ?></h3>
    <table border="1">
        <tr>
            <th>Health ID</th>
            <th>Patient Name</th>
            <th>Total Tests</th>
            <th>Abnormal Tests</th>
            <th>Abnormal Percentage</th>
        </tr>
        <?php foreach ($reportResults as $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($result['healthID']); ?></td>
                <td><?php echo htmlspecialchars($result['patientName']); ?></td>
                <td><?php echo htmlspecialchars($result['totalTests']); ?></td>
                <td><?php echo htmlspecialchars($result['abnormalTests']); ?></td>
                <td><?php echo htmlspecialchars(number_format($result['abnormalPercentage'], 2)) . '%'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php elseif ($action === 'generateReport' && empty($reportResults)): ?>
    <p>No testing records found for the selected period.</p>


    <?php elseif ((isset($action) && $action === 'predictionReport')): ?>
        <h2>Generate Health Prediction Report</h2>

        <!-- Form for generating health prediction report -->
        <form method="POST" action="">
        
        <div class="form-group"> 
            <label for="year">Select Year for Prediction Report:</label>
            <input type="number" id="year" name="year" value="<?php echo date('Y'); ?>" required>
        </div>
            <input type="hidden" name="action" value="generateHealthPredictionReport">
            <input type="submit" value="Generate Health Prediction Report" style="display: block; margin: 0 auto;">
        </form>

        <?php elseif ($action === 'generateHealthPredictionReport' && !empty($predictionReportResults)): ?>
            <h3>Health Prediction Report for <?php echo htmlspecialchars($selectedYear); ?></h3>
    <table border="1">
        <tr>
            <th>Health ID</th>
            <th>Patient Name</th>
            <th>Exam Type</th>
            <th>Total Abnormal Occurrences</th>
            <th>Priority</th>
            <th>Predicted Health Risk</th>
        </tr>
        <?php foreach ($predictionReportResults as $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($result['healthID']); ?></td>
                <td><?php echo htmlspecialchars($result['patientName']); ?></td>
                <td><?php echo htmlspecialchars($result['examName']); ?></td>
                <td><?php echo htmlspecialchars($result['abnormalCount']); ?></td>
                <td style="background-color: 
                    <?php 
                        if ($result['Priority'] === 'Low') {
                            echo 'yellow';
                        } elseif ($result['Priority'] === 'Medium') {
                            echo 'orange';
                        } elseif ($result['Priority'] === 'High') {
                            echo 'red';
                        } else {
                            echo 'transparent';
                        } 
                    ?>;">
                    <?php echo htmlspecialchars($result['Priority']); ?>
                </td>
                <td><?php echo htmlspecialchars($result['PredictedRisk']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php elseif ($action === 'generateHealthPredictionReport' && empty($predictionReportResults)): ?>
    <p>No health prediction data found for the selected year.</p>
<?php endif; ?>

</div>

<script>

// Highlighting new image name and showing upload button
    function uploadShow() {
        let uploadBtn = document.getElementsByName('submitPhotoBtn')[0];
        uploadBtn.style.display = "block";


        let uploadBtnTxt = document.getElementById('fileToUpload');
        uploadBtnTxt.style.fontStyle = "italic";
        uploadBtnTxt.style.backgroundColor = "yellow";

    }

    </script>

</body>
</html>
