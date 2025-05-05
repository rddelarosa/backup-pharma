<?php
session_start();
include("connect.php");

if (!isset($_SESSION['username'])) {
    die("Please log in first.");
}

$username = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $input_password = $_POST['password'];

    $sql = "SELECT id, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($input_password, $user['password'])) {
        $user_id = $user['id'];

        $conn->query("DELETE FROM medicine_doses WHERE user_id = $user_id");
        $conn->query("DELETE FROM current_medicines WHERE user_id = $user_id");
        $conn->query("DELETE FROM previous_medicines WHERE user_id = $user_id");

        $deleteUser = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteUser->bind_param("i", $user_id);
        $deleteUser->execute();

        session_destroy();
        echo "<script>
                localStorage.setItem('modal_success_heading', 'Success!');
                localStorage.setItem('modal_success_message', 'Your account has been deleted successfully.');
                window.location.href = 'http://localhost/pharmasync/login.php';
              </script>";
        exit;

    } else {
        $_SESSION['error'] = "Your password is incorrect.";
        $_SESSION['error_heading'] = "Authentication Failed";
        header("Location: delete.php");
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Delete Account</title>
  <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

</head>
<body><br><br><br>

  <div class="container-pass">
    <h1>Delete Account</h1><br>
    <p>Please confirm your password to delete <br> your account.</p><br>

    <form id="deleteForm" method="POST">
      <div class="form-group">
        <label for="password">Enter Password:</label>
        <input type="password" id="password" name="password" required>
      </div><br>
      <button type="button" <?php $_SESSION['redirect_to_account'] = true; ?> 
 onclick="window.location.href='http://localhost/pharmasync/dashboard.php'" class="button">Cancel</button>
      <button type="button" onclick="showModal()" class="button delete">Delete</button>
      <input type="hidden" name="delete_account" value="1">
    </form>
  </div>

  <!-- Deletion Confirmation Modal -->
  <div id="confirmationModal" class="modal">
    <div class="del-modal-content">
      <h1>Confirm Deletion</h1><br>
      <p>This action cannot be undone. Are you sure you want to delete your account?</p><br>
      <button class="button" onclick="hideModal()">Cancel</button>
      <button class="button delete" onclick="submitForm()">Yes, Delete</button>
    </div>
  </div>

  <!-- Success Modal -->
  <div id="successModal" class="modal">
    <div class="del-modal-content-2">
      <h2 id="modalHeadingSuccess"></h2><br>
      <p id="successMessage"></p><br>
    </div>
  </div>

  <!-- Error Modal -->
  <div id="errorModal" class="modal">
    <div class="del-modal-content-2">
      <h2 id="modalHeading"></h2><br>
      <p id="errorMessage"></p><br>
    </div>
  </div>

  <script>
    function showModal() {
      document.getElementById('confirmationModal').style.display = 'flex';
    }

    function hideModal() {
      document.getElementById('confirmationModal').style.display = 'none';
    }

    function submitForm() {
      document.getElementById('deleteForm').submit();
    }

    document.addEventListener('DOMContentLoaded', () => {
      <?php if (isset($_SESSION['error'])): ?>
        showFeedbackModal('errorModal', '<?php echo $_SESSION['error_heading']; ?>', '<?php echo $_SESSION['error']; ?>', 'delete.php');
        <?php unset($_SESSION['error'], $_SESSION['error_heading']); ?>
      <?php endif; ?>

      if (localStorage.getItem('modal_success_message')) {
        showFeedbackModal('successModal',
          localStorage.getItem('modal_success_heading'),
          localStorage.getItem('modal_success_message'),
          'http://localhost/pharmasync/login.php'
        );
        localStorage.removeItem('modal_success_message');
        localStorage.removeItem('modal_success_heading');
      }
    });

    function showFeedbackModal(modalId, heading, message, redirectUrl) {
      const modal = document.getElementById(modalId);
      const headingElement = modal.querySelector('h2');
      const messageElement = modal.querySelector('p');

      headingElement.textContent = heading;
      messageElement.textContent = message;
      modal.style.display = 'flex';

      setTimeout(() => {
        modal.style.display = 'none';
        if (redirectUrl) window.location.href = redirectUrl;
      }, 3000);
    }
  </script>

</body>
</html>
