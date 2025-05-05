<?php
include("connect.php");


    $medicine_id = isset($_POST['medicine_id']) ? (int) $_POST['medicine_id'] : 0;
    $dose = trim($_POST['dose'] ?? '');
    $frequency = trim($_POST['frequency'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    $stmt = $conn->prepare("UPDATE current_medicines 
                            SET dose = ?, frequency = ?, start_date = ?, end_date = ?, stock = ? 
                            WHERE medicine_id = ?");
    $stmt->bind_param("ssssii", $dose, $frequency, $start_date, $end_date, $stock, $medicine_id);
    $stmt->execute();
    $stmt->close();

    $yesterday = (new DateTime())->modify('-1 day')->format('Y-m-d');

    $fetchStmt = $conn->prepare("SELECT * FROM current_medicines WHERE end_date <= ?");
    $fetchStmt->bind_param("s", $yesterday);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Insert into previous_medicines
        $insertStmt = $conn->prepare("INSERT INTO previous_medicines 
        (medicine_id, user_id, medicine_name, dose, frequency, start_date, end_date, unit, hours_interval) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $insertStmt->bind_param(
        "iissssssi", 
        $row['medicine_id'],
        $row['user_id'],
        $row['medicine_name'],
        $row['dose'],
        $row['frequency'],
        $row['start_date'],
        $row['end_date'],
        $row['unit'],
        $row['hours_interval']
    );
    
    $insertStmt->execute();
    $insertStmt->close();
    
        // Delete from current_medicines
        $deleteStmt = $conn->prepare("DELETE FROM current_medicines WHERE medicine_id = ?");
        $deleteStmt->bind_param("i", $row['medicine_id']);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    $fetchStmt->close();


?>
