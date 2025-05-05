<?php
session_start();  
require 'php/connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username']);  // Could be email or username
    $password = trim($_POST['password']);

    // Check users table by email or username
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // If your database stores hashed passwords, use password_verify
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            if (isset($user['temp_password']) && $user['temp_password'] == 1) {
              $_SESSION['require_password_change'] = true;
          }
          header("Location: http://localhost/pharmasync/dashboard.php");
            exit();
        } else {
          echo "<script>
          document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('errroModal').style.display = 'block';
          });
        </script>";
            }
      echo "<script>
      document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('errroModal').style.display = 'block';
      });
    </script>";
      }
    $stmt->close();

  }

// Redirect back to login page with error
if (!empty($error)) {
    header("Location: ../login.html?error=" . urlencode($error));
    exit();
}
?>


<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" type="text/css" href="style_PS.css">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet"
  href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>


<style>
  header{
        background: #158576 ;
        height: 80px;
  } 

  body {
    background-color: var(--shadeblue); 
  }
</style>

<!-- Navagation Bar codes -->
<header class="header">
  <div class="bx bx-menu" id="menu-icon"></div>
  <a href="index.html" class="logo"> <img src="PS_img/PS_logo.png" alt="Logo" class="logo-img" /> <span class="logo-text"> PharmaSync</span> </a>
  <ul class="navbar">
    <li>
      <a class="active" href="index.html">Home</a>
    </li>
    <li>
      <a class="active" href="AboutUs.html">About Us</a>
    </li>
    <li>
      <a class="active" href="Services.html">Services</a>
    </li>
  </ul>
  <div class="main">
    </div>
</header>
<br>

<!-- Sign In -->
<section class="auth-section">
  <div class="form-container">
      <h1>Hello, ready to Sync!</h1><br>
      <?php if (!empty($error)): ?>
      <p class="error-message"><?= htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form id="authForm" action="login.php" method="post">
          <div class="form-group">
              <label for="username">Username or Email:</label>
              <input type="text" id="username" name="username" placeholder="Enter your Username or Email" required>
          </div>
          <div class="form-group">
              <label for="password">Password:</label>
              <input type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>
          <div class="form-actions">
              <button type="submit" class="btn-login">Log In</button>
          </div>
          <div>
            <p class="alternate-action">
              <a href="register.php">Create an Account</a>
          </p>
          </div>
      </form>
      <p class="alternate-action">
          <a href="php/pass_forgot.php">Forgot Password?</a>
      </p>
  </div>
</section>
<script type="text/javascript" src="script_PS.js"></script>

<div id="errroModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h2>Login Failed!</h2><br>
    <p>Incorrect password or username/email. Please try again.</p><br>
  </div>
</div>

<script>
  // Show modal
  function showModal() {
    document.getElementById("errroModal").style.display = "block";
  }

  // Redirect to the login page when modal content is clicked
  document.querySelector('.modal-content').addEventListener('click', function() {
    window.location.href = "http://localhost/PharmaSync/login.php"; // Redirect to login.php
  });

  // Optionally: Close the modal when clicking on the background
  window.onclick = function(event) {
    const modal = document.getElementById("errroModal");
    if (event.target === modal) {
      modal.style.display = "none"; // Hide the modal
    }
  };
</script>


</body>
</html>