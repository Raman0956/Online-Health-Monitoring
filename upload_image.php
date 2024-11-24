<?php
$target_dir = "images/";

$action = filter_input(INPUT_POST, 'action', FILTER_DEFAULT);

$target_file = $target_dir . basename($_FILES["image"]["name"]);

// Flag to indicate if the upload should proceed
$uploadOk = 1;

if (isset($_POST["submitPhotoBtn"])) {

    // Attempt to upload the file
    if ($uploadOk === 0) {
        echo "Sorry, your file was not uploaded.";
    } else {
        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Redirect to the index with parameters
            header("Location: admin_dashboard.php?action=$action");
            exit;
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}


