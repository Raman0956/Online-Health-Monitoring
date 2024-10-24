<?php
session_start();
require_once('database.php');
require_once('Admin.php');

$admin = new Admin($_SESSION['userID'], "Admin Name", "admin@example.com", "1234567890", "hashed_password", "Administrator");

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
        
        $admin->createDoctor($conn, $doctorName, $doctorEmail, $doctorPhoneNumber, $doctorPassword, $doctorWorkingID);
    }

    // Create Staff
    if ($action === 'createStaff' && isset($_POST['staffName'])) {
        $staffName = $_POST['staffName'];
        $staffEmail = $_POST['staffEmail'];
        $staffPhoneNumber = $_POST['staffPhoneNumber'];
        $staffPassword = $_POST['staffPassword'];
        $staffWorkingID = $_POST['staffWorkingID'];
        
        $admin->createStaff($conn, $staffName, $staffEmail, $staffPhoneNumber, $staffPassword, $staffWorkingID);
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
</head>
<body>
    <h2>Admin Dashboard</h2>
    
    <!-- Dashboard Options Menu -->
    <form method="POST" action="">
        <button type="submit" name="action" value="viewPendingRequests">View Pending Requests</button>
        <button type="submit" name="action" value="createDoctorForm">Create New Doctor Account</button>
        <button type="submit" name="action" value="createStaffForm">Create New Staff Account</button>
        <button type="submit" name="action" value="deleteAccountForm">Delete Account</button>
        <button type="submit" name="action" value="logout">Logout</button>
    </form>

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
        <form method="POST" action="">
            <label for="doctorName">Name:</label>
            <input type="text" id="doctorName" name="doctorName" required><br><br>
            
            <label for="doctorEmail">Email:</label>
            <input type="email" id="doctorEmail" name="doctorEmail" required><br><br>
            
            <label for="doctorPhoneNumber">Phone Number:</label>
            <input type="text" id="doctorPhoneNumber" name="doctorPhoneNumber" required><br><br>
            
            <label for="doctorPassword">Password:</label>
            <input type="password" id="doctorPassword" name="doctorPassword" required><br><br>
            
            <label for="doctorWorkingID">Working ID:</label>
            <input type="text" id="doctorWorkingID" name="doctorWorkingID" required><br><br>
            
            <input type="hidden" name="action" value="createDoctor">
            <input type="submit" value="Create Doctor">
        </form>
    <?php endif; ?>

    <?php if (isset($action) && $action === 'createStaffForm'): ?>
        <h3>Create New Staff Account</h3>
        <form method="POST" action="">
            <label for="staffName">Name:</label>
            <input type="text" id="staffName" name="staffName" required><br><br>
            
            <label for="staffEmail">Email:</label>
            <input type="email" id="staffEmail" name="staffEmail" required><br><br>
            
            <label for="staffPhoneNumber">Phone Number:</label>
            <input type="text" id="staffPhoneNumber" name="staffPhoneNumber" required><br><br>
            
            <label for="staffPassword">Password:</label>
            <input type="password" id="staffPassword" name="staffPassword" required><br><br>
            
            <label for="staffWorkingID">Working ID:</label>
            <input type="text" id="staffWorkingID" name="staffWorkingID" required><br><br>
            
            <input type="hidden" name="action" value="createStaff">
            <input type="submit" value="Create Staff">
        </form>
    <?php endif; ?>

    <?php if (isset($action) && $action === 'deleteAccountForm'): ?>
        <h3>Delete User Account</h3>
        <form method="POST" action="">
            <label for="userType">Select User Type:</label>
            <select id="userType" name="userType" required>
                <option value="">--Select User Type--</option>
                <option value="Patient">Patient</option>
                <option value="Doctor">Doctor</option>
                <option value="Staff">Staff</option>
            </select><br><br>

            <label for="searchTerm">Search by Name, Email, User ID (for Patient) or Working ID (for Doctor/Staff):</label>
            <input type="text" id="searchTerm" name="searchTerm" required><br><br>

            <input type="hidden" name="action" value="deleteUserSearch">
            <input type="submit" value="Search">
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
   
    
         
</body>
</html>
