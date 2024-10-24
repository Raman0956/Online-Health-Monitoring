<?php
session_start();

// Check if user is logged in and is a Patient
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'Patient') {
    header("Location: login.php");
    exit();
}

// Check if account is approved
if (isset($_SESSION['isApproved']) && $_SESSION['isApproved']) {
    echo "<h2>Welcome to your dashboard!</h2>";
    echo "<p>Your account has been approved. You can now access all features.</p>";
    // Display more patient-specific dashboard information here
} else {
    echo "<h2>Account Pending Approval</h2>";
    echo "<p>Your account is currently awaiting approval. Please check back later or contact Clinic helpdesk if you have any questions.</p>";
    // Optionally provide limited or no access to other dashboard features
}
?>