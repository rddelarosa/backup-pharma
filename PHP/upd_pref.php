<?php
session_start();
include("connect.php");
$userId = $_SESSION['id'];


$darkMode = isset($_POST['dark_mode']) ? 1 : 0;
$emailNotif = isset($_POST['notify_email']) ? 1 : 0;
$reminderInterval = isset($_POST['reminder_interval']) ? $_POST['reminder_interval'] : null;

// Set remind_pre based on emailNotif
$remindPre = ($emailNotif && isset($_POST['remindPre'])) ? 1 : 0;

// Set remind_val based on reminder_interval, only if remind_pre is 1
$remindVal = $remindPre ? $reminderInterval : null;

// Prepare the SQL update statement
$updateStmt = $conn->prepare("UPDATE users SET dark_mode = ?, email_notif = ?, remind_pre = ?, remind_val = ? WHERE id = ?");
$updateStmt->bind_param("iiiis", $darkMode, $emailNotif, $remindPre, $remindVal, $userId);
$updateStmt->execute();
$updateStmt->close();

$_SESSION['redirect_to_settings'] = true; 
$_SESSION['change_done'] = "Your Preferences is sucessfully updated";
$_SESSION['change_done_heading'] = "Success!";

header("Location: ../dashboard.php");
exit;
?>
