<?php
session_start();
include("connect.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['id'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $is_temp_password = isset($_SESSION['require_password_change']) && $_SESSION['require_password_change'];

    if (!$user_id) {
        $_SESSION['error'] = "User not logged in. Please log in to change your password.";
        $_SESSION['error_heading'] = "Authentication Error";
    }

    if (!$is_temp_password) {
        $current_password = $_POST['current_password'];

        $query = "SELECT password FROM users WHERE id = '$user_id'";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            $_SESSION['error'] = "Database query failed: " . mysqli_error($conn);
            $_SESSION['error_heading'] = "Database Error";
        }

        $user = mysqli_fetch_assoc($result);

        if (!password_verify($current_password, $user['password'])) {
            $_SESSION['error'] = "Current password is incorrect.";
            $_SESSION['error_heading'] = "Authentication Failed";
            header("Location: pass_change.php");
            exit;
        }
    }

    if ($new_password === $confirm_password) {
        if (strlen($new_password) >= 8) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = '$user_id'";

            if (mysqli_query($conn, $update_query)) {
                $_SESSION['success'] = "Your password has been changed successfully.";
                $_SESSION['success_heading'] = "Success!";
                $_SESSION['redirect_to_account'] = true;

                if ($is_temp_password) {
                    $clear_temp_flag_query = "UPDATE users SET temp_password = 0 WHERE id = '$user_id'";
                    mysqli_query($conn, $clear_temp_flag_query);
                    unset($_SESSION['require_password_change']); 
                }
            }
             else {
                $_SESSION['error'] = "Error updating password. Please try again.";
                $_SESSION['error_heading'] = "Update Failed";
            }
        } else {
            $_SESSION['error'] = "Password must be at least 8 characters long.";
            $_SESSION['error_heading'] = "Weak Password";
        }
    } else {
        $_SESSION['error'] = "New password and confirmation do not match.";
        $_SESSION['error_heading'] = "Mismatch Error";
    }
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password</title>
  <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

  </head>   

<body><br><br><br>
    <div class="container-pass">
    <?php $_SESSION['redirect_to_account'] = true; ?>
<div>
    <i class="ri-close-line x-button" onclick="window.location.href='http://localhost/pharmasync/dashboard.php';"></i>
</div>
        <h1>Change Password</h1><br>

<form method="POST" action="">
<?php if (!isset($_SESSION['require_password_change']) || !$_SESSION['require_password_change']): ?>
    <div class="form-group">
        <label for="current_password">Current Password:</label>
        <input type="password" id="current_password" name="current_password" required>
    </div>
<?php endif; ?>

    <div class="form-group">
        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required>
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div><br>
    <button type="submit" class="button" name="change_password">Done</button>
</form>
    </div>


<!-- Success & Error Modals -->
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
            showModal('errorModal', '<?php echo $_SESSION['error']; ?>', '<?php echo $_SESSION['error_heading']; ?>', 'pass_change.php');
            <?php unset($_SESSION['error'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['errorEmail'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['errorEmail']; ?>', '<?php echo $_SESSION['error_heading']; ?>', 'pass_change.php');
            <?php unset($_SESSION['errorEmail'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['errorPhone'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['errorPhone']; ?>', '<?php echo $_SESSION['error_heading']; ?>', 'pass_change.php');
            <?php unset($_SESSION['errorPhone'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['success'])): ?>
            showModal('successModal', '<?php echo $_SESSION['success']; ?>', '<?php echo $_SESSION['success_heading']; ?>', 'http://localhost/pharmasync/dashboard.php');
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
