<?php
require('database.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam results</title>
</head>
<body>

<h1> Add Exam Results </h1>


<form action="index.php" method="post">

<input type="hidden" name="action" value="add_exam_results">


            <label>Exam ID</label>
            <input type="text" name="exam_id" />
            <br>

            <label>Patient ID</label>
            <input type="text" name="patient_id" />
            <br>

            <label>Doctor ID</label>
            <input type="text" name="doctor_id" />
            <br>

            <label>exam_date</label>
            <input type="date" name="exam_date" />
            <br>

            <label>exam_result</label>
            <input type="text" name="exam_result" />
            <br>

            <label>status</label>
            <input type="text" name="status" />
            <br>

            <label>&nbsp</label>
            <input type="submit" value="Submit" />

</form>
</body>
</html>