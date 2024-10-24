<?php
    $dsn = 'mysql:host=localhost;dbname=health_monitoring';
    $username = 'root';
    $password = '';

    try {
        $conn = new PDO($dsn, $username, $password);
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
    }
?>
