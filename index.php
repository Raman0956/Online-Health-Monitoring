<?php
require('database.php');

if(isset($_POST['action']) && $_POST['action'] == 'add_exam_results'){
    $exam_id = filter_input(INPUT_POST, 'exam_id'); 
    $patient_id = filter_input(INPUT_POST, 'patient_id');
    $doctor_id = filter_input(INPUT_POST, 'doctor_id');
    $exam_date = filter_input(INPUT_POST, 'exam_date');
    $exam_result = filter_input(INPUT_POST, 'exam_result');
    $status = filter_input(INPUT_POST, 'status');

    if(empty($exam_id) || empty($patient_id) || empty($doctor_id) || 
    empty($exam_date) || empty($exam_result) || empty($status)){
        echo 'Missing information';}
    else{
        $query = 'INSERT INTO patientexams
                 (exam_id, patient_id, doctor_id, exam_date,exam_result,status)
              VALUES
                 (:exam_id, :patient_id, :doctor_id, :exam_date,:exam_result,:status)';
    $statement = $db->prepare($query);
    $statement->bindValue(':exam_id', $exam_id);
    $statement->bindValue(':patient_id', $patient_id);
    $statement->bindValue(':doctor_id', $doctor_id);
    $statement->bindValue(':exam_date', $exam_date);
    $statement->bindValue(':exam_result', $exam_result);
    $statement->bindValue(':status', $status);
    $statement->execute();
    $statement->closeCursor();
    echo 'Exam result added successfully!';
    }

}

?>
