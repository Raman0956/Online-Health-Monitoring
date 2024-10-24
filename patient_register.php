
<?php
require('database.php');
require('Patient.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phoneNumber'];
    $password = $_POST['password'];
    $healthID = $_POST['healthID'];
    $dateOfBirth = $_POST['dateOfBirth'];

    // Use the Patient class to register
    Patient::register($conn, $name, $email, $phoneNumber, $password, $healthID, $dateOfBirth);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
</head>
<body>
    <h2>Patient Registration Form</h2>
    <form method="POST" action="">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="phoneNumber">Phone Number:</label>
        <input type="text" id="phoneNumber" name="phoneNumber" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="healthID">Health ID:</label>
        <input type="text" id="healthID" name="healthID" required><br><br>

        <label for="dateOfBirth">Date of Birth:</label>
        <input type="date" id="dateOfBirth" name="dateOfBirth" required><br><br>

        <input type="submit" value="Register">
    </form>
</body>
</html>