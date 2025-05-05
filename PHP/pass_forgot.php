<?php
include("connect.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $_SESSION['error'] = "Please enter your email address.";
        $_SESSION['error_heading'] = "Missing Field";
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];

            $temp_pass = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
            $hashed_pass = password_hash($temp_pass, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password = ?, temp_password = 1 WHERE id = ?");
            $update->bind_param("si", $hashed_pass, $user_id);
            $update->execute();

            if ($update->affected_rows > 0) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'pharmasync.ph@gmail.com';
                    $mail->Password = 'msnh jdhw efvw xnwb'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('pharmasync.ph@gmail.com', 'PharmaSync');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'PharmaSync Password Reset';
                    $mail->Body = "<p>Hello,</p><p>Your temporary password is: <strong>$temp_pass</strong></p><p>Please log in and change your password immediately.</p>";
                    $mail->send();

                    $_SESSION['success'] = "Temporary password sent to your email.";
                    $_SESSION['success_heading'] = "Email Sent!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Email sending failed: {$mail->ErrorInfo}";
                    $_SESSION['error_heading'] = "Mailer Error";
                }
            } else {
                $_SESSION['error'] = "Failed to update password. Please try again.";
                $_SESSION['error_heading'] = "Database Error";
            }
        } else {
            $_SESSION['error'] = "Email not found in our system.";
            $_SESSION['error_heading'] = "Account Not Found";
        }
    }

    header("Location: pass_forgot.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

  </head>   

<body>
    <div class="container-pass">
        <h1>Forgot Password</h1><br>
        <p>We'll send a temporary password to the email address linked to your account.</p>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button class="button" type="submit" name="forgot_password">Send</button>
        </form>
    </div>


<div id="errorModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h2 id="modalHeading"></h2><br>
        <p id="errorMessage"></p><br>
    </div>
</div>

<div id="successModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h2 id="modalHeadingSuccess"></h2><br>
        <p id="successMessage"></p><br>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['error'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['error']; ?>', '<?php echo $_SESSION['error_heading']; ?>', 'pass_forgot.php');
            <?php unset($_SESSION['error'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['errorEmail'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['errorEmail']; ?>', '<?php echo $_SESSION['error_heading']; ?>', 'pass_forgot.php');
            <?php unset($_SESSION['errorEmail'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['errorPhone'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['errorPhone']; ?>', '<?php echo $_SESSION['error_heading']; ?>', 'pass_forgot.php');
            <?php unset($_SESSION['errorPhone'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['success'])): ?>
            showModal('successModal', '<?php echo $_SESSION['success']; ?>', '<?php echo $_SESSION['success_heading']; ?>', 'http://localhost/pharmasync/login.php');
            <?php unset($_SESSION['success'], $_SESSION['success_heading']); ?>
        <?php endif; ?>
    });

    function showModal(modalId, message, heading, redirectUrl) {
    const modal = document.getElementById(modalId);
    const messageElement = modal.querySelector('#errorMessage') || modal.querySelector('#successMessage');
    const headingElement = modal.querySelector('#modalHeading') || modal.querySelector('#modalHeadingSuccess');

    if (messageElement) messageElement.textContent = message;
    if (headingElement) headingElement.textContent = heading;

    modal.style.display = 'block';

    modal.onclick = () => {
        window.location.href = redirectUrl;
    };

    setTimeout(() => {
        modal.style.display = 'none';
        window.location.href = redirectUrl;
    }, 3000);
}

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
</script>

</body>
</html>