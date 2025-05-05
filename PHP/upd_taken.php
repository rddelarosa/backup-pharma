<?php
session_start();
include("connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['medicine_id']) && isset($_POST['dose_id']) && isset($_POST['action'])) {
    $medicineId = intval($_POST['medicine_id']);
    $doseId = intval($_POST['dose_id']);
    $action = $_POST['action']; 

    error_log("Received action: $action for medicine_id: $medicineId and dose_id: $doseId");

    if (!isset($_SESSION['id'])) {
        echo "no_user";
        exit;
    }

    $userId = $_SESSION['id'];
    error_log("User ID: $userId");

    $doseQuery = "SELECT id, medicine_id, scheduled_time, is_taken FROM medicine_doses WHERE id = ? AND medicine_id = ? AND user_id = ? AND is_taken = 0";
    $doseStmt = $conn->prepare($doseQuery);
    $doseStmt->bind_param("iii", $doseId, $medicineId, $userId);
    $doseStmt->execute();
    $doseResult = $doseStmt->get_result();

    if ($doseResult->num_rows === 0) {
        error_log("No untaken dose found for medicine_id: $medicineId and user_id: $userId");
        echo "no_due_dose";
        exit;
    }

    $dose = $doseResult->fetch_assoc();
    $doseId = $dose['id'];

    if ($action === 'taken') {
        $stockQuery = "SELECT stock, stock_notif FROM current_medicines WHERE medicine_id = ? AND user_id = ?";
        $stockStmt = $conn->prepare($stockQuery);
        $stockStmt->bind_param("ii", $medicineId, $userId);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        $stockRow = $stockResult->fetch_assoc();

        if ($stockRow['stock'] <= 0 && $stockRow['stock_notif'] > 0) {
            error_log("No stock available for medicine_id: $medicineId");
            echo "no_stock"; 
            exit;
        }
        

        $updateDose = $conn->prepare("UPDATE medicine_doses SET is_taken = 1, taken_time = NOW() WHERE id = ?");
        $updateDose->bind_param("i", $doseId);
        $updateDose->execute();
        if ($updateDose->affected_rows === 0) {
            error_log("Failed to update dose as taken for dose_id: $doseId");
            echo "update_failed";
            exit;
        }
        $updateDose->close();

        if ($stockRow['stock_notif'] > 0) {
        $updateStock = $conn->prepare("UPDATE current_medicines SET stock = stock - 1 WHERE medicine_id = ? AND user_id = ?");
        $updateStock->bind_param("ii", $medicineId, $userId);
        $updateStock->execute();
        if ($updateStock->affected_rows === 0) {
            error_log("Failed to deduct stock for medicine_id: $medicineId");
            echo "stock_update_failed";
            exit;
        }
        $updateStock->close();
        }

$moveDose = $conn->prepare("SELECT user_id, medicine_id, medicine_name, scheduled_time, taken_time, is_taken, start_date, start_time, last_reminder_sent, is_skip, skipped_time, dosage FROM medicine_doses WHERE medicine_id = ? AND user_id = ?");
$moveDose->bind_param("ii", $medicineId, $userId);
$moveDose->execute();
$result = $moveDose->get_result();

if ($med = $result->fetch_assoc()) {
    $insertDose = $conn->prepare("INSERT INTO done_medicine_doses (user_id, medicine_id, medicine_name, scheduled_time, taken_time, is_taken, start_date, start_time, last_reminder_sent, is_skip, skipped_time, dosage) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertDose->bind_param("iisssissssis", 
        $med['user_id'], 
        $med['medicine_id'], 
        $med['medicine_name'], 
        $med['scheduled_time'], 
        $med['taken_time'], 
        $med['is_taken'], 
        $med['start_date'], 
        $med['start_time'], 
        $med['last_reminder_sent'], 
        $med['is_skip'], 
        $med['skipped_time'], 
        $med['dosage'], 
    );
    
    $insertDose->execute();
    $insertDose->close();
} else {
    echo "No data found for the specified medicine_id and user_id.";
}

$moveDose->close();

        $remainingDosesQuery = $conn->prepare("SELECT COUNT(*) FROM medicine_doses WHERE medicine_id = ? AND user_id = ?");
        $remainingDosesQuery->bind_param("ii", $medicineId, $userId);
        $remainingDosesQuery->execute();
        $remainingDosesQuery->bind_result($remainingDoses);
        $remainingDosesQuery->fetch();
        $remainingDosesQuery->close();

        if ($remainingDoses == 0) {
            $medInfo = $conn->prepare("SELECT user_id, medicine_name, dosage, frequency, start_date, end_date FROM current_medicines WHERE medicine_id = ? AND user_id = ?");
            $medInfo->bind_param("ii", $medicineId, $userId);
            $medInfo->execute();
            $medResult = $medInfo->get_result();
            $med = $medResult->fetch_assoc();
            $medInfo->close();

            if ($med) {
                $insertPrevPres = $conn->prepare("INSERT INTO previous_medicines (user_id, medicine_name, dosage, frequency, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
                $insertPrevPres->bind_param("isssss", $med['user_id'], $med['medicine_name'], $med['dosage'], $med['frequency'], $med['start_date'], $med['end_date']);
                $insertPrevPres->execute();
                $insertPrevPres->close();

                $deleteCurrentMed = $conn->prepare("DELETE FROM current_medicines WHERE medicine_id = ? AND user_id = ?");
                $deleteCurrentMed->bind_param("ii", $medicineId, $userId);
                $deleteCurrentMed->execute();
                $deleteCurrentMed->close();
            }
        }
        echo 'success';
    }

    elseif ($action === 'skip') {
        $updateDose = $conn->prepare("UPDATE medicine_doses SET skipped_time = NOW(), is_skip = 1 WHERE id = ?");
        $updateDose->bind_param("i", $doseId);
        $updateDose->execute();
        if ($updateDose->affected_rows === 0) {
            error_log("Failed to update dose as skipped for dose_id: $doseId");
            echo "update_failed";
            exit;
        }
        $updateDose->close();

        echo "success";
    } else {
        echo "invalid_action"; 
    }

} else {
    echo "invalid_request";
}
?>
