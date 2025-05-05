<?php
session_start();
include("connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['medicine_id'])) {
    $medicineId = (int) $_POST['medicine_id'];
    $userId = $_SESSION['id'] ?? null;

    if ($userId) {
        $stmt = $conn->prepare("DELETE FROM current_medicines WHERE medicine_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $medicineId, $userId);
        $stmt->execute();

        $stmt2 = $conn->prepare("DELETE FROM medicine_doses WHERE medicine_id = ?");
        $stmt2->bind_param("i", $medicineId);
        $stmt2->execute();
    }
}

$_SESSION['redirect_to_prescription'] = true;
exit;

?>