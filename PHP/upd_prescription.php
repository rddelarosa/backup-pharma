<?php
session_start();
include("connect.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION["id"];
    $medicine_id = isset($_POST['medicine_id']) ? (int) $_POST['medicine_id'] : 0;
    $dose = trim($_POST['dose'] ?? '');
    $frequency = trim($_POST['frequency'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
    $stock_notif = $_POST['stock_notif'] ?? '';
    $stock_remind_value = isset($_POST['stock_remind_value']) ? (int) $_POST['stock_remind_value'] : 0;
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    
    $time1 = $_POST['time1'] ?? null;
    $time2 = $_POST['time2'] ?? null;
    $time3 = $_POST['time3'] ?? null;
    $time4 = $_POST['time4'] ?? null;

    switch ($frequency) {
        case 'Once daily':
            $time2 = $time3 = $time4 = null;
            break;
        case 'Twice daily':
            $time3 = $time4 = null;
            break;
        case 'Three times daily':
            $time4 = null;
            break;
        case 'Four times daily':
            break;
        default:
            break;
    }

    if ($medicine_id <= 0 || !$dose || !$frequency || !$start_date || !$end_date || $stock < 0) {
        die('Invalid form data.');
    }

    $stmt = $conn->prepare("UPDATE current_medicines SET dose = ?, frequency = ?, start_date = ?, end_date = ?, stock = ?, stock_notif = ?, stock_remind_value = ?, medicine_name = ? WHERE medicine_id = ?");
    $stmt->bind_param("ssssiiisi", $dose, $frequency, $start_date, $end_date, $stock, $stock_notif, $stock_remind_value, $medicine_name, $medicine_id);
    $stmt->execute();
    $stmt->close();

    $stmtTimes = $conn->prepare("UPDATE current_medicines_times SET time1 = ?, time2 = ?, time3 = ?, time4 = ? WHERE medicine_id = ?");
    $stmtTimes->bind_param("ssssi", $time1, $time2, $time3, $time4, $medicine_id);
    $stmtTimes->execute();
    $stmtTimes->close();

    $stmtDelete = $conn->prepare("DELETE FROM medicine_doses WHERE medicine_id = ? AND is_taken = 0");
    $stmtDelete->bind_param("i", $medicine_id);
    $stmtDelete->execute();
    $stmtDelete->close();

    $times = array_filter([$time1, $time2, $time3, $time4]);
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    $stmtInsert = $conn->prepare("INSERT INTO medicine_doses (medicine_id, scheduled_time, is_taken, user_id, medicine_name) VALUES (?, ?, 0, ?, ?)");
    
    $current = clone $start;
    while ($start <= $end) {
        foreach ($times as $time) {
            $datetime = $start->format('Y-m-d') . ' ' . $time;
            $stmtInsert->bind_param("isis", $medicine_id, $datetime, $user_id, $medicine_name);
            $stmtInsert->execute();
        }
        $start->modify('+1 day');
    }

    $stmtInsert->close();
    $_SESSION['redirect_to_prescription'] = true;
    $_SESSION['change_done'] = "Your Medicine is sucessfully updated";
    $_SESSION['change_done_heading'] = "Success!";

    header("Location: ../dashboard.php");
    exit();
}
?>
