<?php
session_start();
include("connect.php");

require __DIR__ . '/../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['id'])) {
    exit(); 
}

$userId = $_SESSION['id'];

// Include remind_pre in the query
$sql = "SELECT email, remind_val, remind_pre, email_notif FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($userEmail, $remindVal, $remindPre, $emailNotif);
$stmt->fetch();
$stmt->close();

// Exit if any condition prevents reminders
if ($emailNotif == 0 || $remindPre == 0 || $remindVal == 0) {
    exit(); 
}

$sqlDoses = "SELECT id, medicine_name, scheduled_time, is_taken FROM medicine_doses 
             WHERE user_id = ? AND is_taken = 0";
$stmtDoses = $conn->prepare($sqlDoses);
$stmtDoses->bind_param("i", $userId);
$stmtDoses->execute();
$stmtDoses->store_result();
$stmtDoses->bind_result($doseId, $medicineName, $scheduledTime, $isTaken);

while ($stmtDoses->fetch()) {
    if ($isTaken == 1) continue;

    $currentTime = new DateTime();
    $scheduledTimeObj = new DateTime($scheduledTime);
    $interval = $scheduledTimeObj->diff($currentTime);
    $diffInMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    $isPast = $scheduledTimeObj < $currentTime;

    $mail = new PHPMailer(true);
    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'pharmasync.ph@gmail.com';
        $mail->Password   = 'msnh jdhw efvw xnwb'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'PharmaSync');
        $mail->addAddress($userEmail);
        $mail->isHTML(true);

        if (!$isPast && $diffInMinutes <= 2) {
            $mail->Subject = "Reminder: Time to Take Your Medicine ($medicineName)";
            $mail->Body = "
                Hello! This is a reminder to take your medicine <b>$medicineName</b> at <b>$scheduledTime</b>.<br><br>
                Once you've taken it, please mark it on your tracking website.<br>
                Alternatively, you may email us at <a href='mailto:pharmasync.ph@gmail.com'>pharmasync.ph@gmail.com</a> with the subject line: <b>MARK TAKEN #$doseId</b>.
            ";
                        $mail->send();
        } elseif ($isPast) {
            $sqlReminder = "SELECT last_reminder_sent FROM medicine_doses WHERE id = ?";
            $stmtReminder = $conn->prepare($sqlReminder);
            $stmtReminder->bind_param("i", $doseId);
            $stmtReminder->execute();
            $stmtReminder->store_result();
            $stmtReminder->bind_result($lastReminderSent);
            $stmtReminder->fetch();
            $stmtReminder->close();

            if (!$lastReminderSent || (strtotime($lastReminderSent) <= strtotime("-$remindVal minutes"))) {
                $mail->Subject = "Missed Dose Alert: $medicineName";
                $mail->Body = "⚠️ You missed your medicine <b>$medicineName</b> scheduled at <b>$scheduledTime</b>.<br>
                               Please take it as soon as possible and mark it as taken.";
                $mail->send();

                $updateReminderSql = "UPDATE medicine_doses SET last_reminder_sent = NOW() WHERE id = ?";
                $updateReminderStmt = $conn->prepare($updateReminderSql);
                $updateReminderStmt->bind_param("i", $doseId);
                $updateReminderStmt->execute();
                $updateReminderStmt->close();
            }
        }

    } catch (Exception $e) {
        error_log("Email error for dose $doseId: " . $mail->ErrorInfo);
    }
}

$stmtDoses->close();
$conn->close();
?>
