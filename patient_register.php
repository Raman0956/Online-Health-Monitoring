
<?php
require('database.php');
require('Patient.php');

// Initialize variables for form input values
$name = $email = $phoneNumber = $healthID = $dateOfBirth = "";

$errorMessage = "";
$successMessage = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phoneNumber'];
    $password = $_POST['password'];
    $healthID = $_POST['healthID'];
    $dateOfBirth = $_POST['dateOfBirth'];

    // Call the register function and capture the result
    $result = Patient::register($conn, $name, $email, $phoneNumber, $password, $healthID, $dateOfBirth);
    if (strpos($result, 'Account registration request sent') !== false) {
        $successMessage = $result;
        // Clear inputs on successful registration
        $name = $email = $phoneNumber = $healthID = $dateOfBirth = "";
    } else {
        $errorMessage = $result;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <h2>Patient Registration Form</h2>
    <div class="container">


    <!-- Display error or success messages -->
        <?php if (!empty($errorMessage)): ?>
            <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>


        <form method="POST" action="">
        
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required><br><br>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br>
            </div>
            <div class="form-group">
                <label for="phoneNumber">Phone Number:</label>
                <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($phoneNumber); ?>" required><br><br>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br><br>
            </div>
            <div class="form-group">
                <label for="healthID">Health ID:</label>
                <input type="text" id="healthID" name="healthID" value="<?php echo htmlspecialchars($healthID); ?>" required><br><br>
            </div>
            <div class="form-group">
                <label for="dateOfBirth">Date of Birth:</label>
                <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($dateOfBirth); ?>" required><br><br>
            </div>
            <input type="submit" value="Register" style="display: block; margin: 0 auto;">
        </form>
    </div>
</body>
</html>