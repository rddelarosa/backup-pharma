<?php
include("connect.php");

function process_email_command($email, $subject, $body) {
    global $conn;

    $command = strtoupper(trim($subject));

    $userId = null; 
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        log_action($email, "No matching user found.");
        $stmt->close();
        return;
    }

    $stmt->bind_result($userId);
    $stmt->fetch();
    $stmt->close();

    if ($command === "STOP REMIND") {
        $updateSql = "UPDATE users SET email_notif = 0 WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $userId);
        $updateStmt->execute();
        $updateStmt->close();

        log_action($email, "Disabled reminders via email.");
    } 
    elseif (strpos($command, "MARK TAKEN #") !== false) {
        preg_match('/MARK TAKEN #(\d+)/', $command, $matches);
        if (isset($matches[1])) {
            $doseId = $matches[1];
        } else {
            log_action($email, "Invalid MARK TAKEN command format.");
            return;
        }

$dose = $conn->prepare("SELECT id FROM medicine_doses WHERE user_id = ? AND id = ? AND is_taken = 0 LIMIT 1");
$dose->bind_param("ii", $userId, $doseId);
$dose->execute();
$dose->store_result();

if ($dose->num_rows > 0) {
    $dose->close();

    $mark = $conn->prepare("UPDATE medicine_doses SET is_taken = 1, taken_time = NOW() WHERE id = ?");
    $mark->bind_param("i", $doseId);
    $mark->execute();
    $mark->close();

    log_action($email, "Marked dose ID $doseId as taken.");
} else {
    $dose->close();
    log_action($email, "No pending doses found for dose ID $doseId.");
}

}
}
function log_action($email, $message) {
    $log = "[" . date('Y-m-d H:i:s') . "] $email - $message\n";
    file_put_contents("email_command_log.txt", $log, FILE_APPEND);
}
?>
