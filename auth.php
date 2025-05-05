<?php
session_start();  // Start session to handle user login state

// Database configuration
$host = "localhost";
$dbname = "pharmasync";
$dbuser = "root";
$dbpass = "";

// Create a database connection
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message
$error = '';

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

            // Redirect to dashboard
            header("Location: http://localhost/pharmasync/dashboard.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No user found with that email or username.";
    }
    $stmt->close();
}

// Redirect back to login page with error
if (!empty($error)) {
    header("Location: ../login.html?error=" . urlencode($error));
    exit();
}


session_start();

// Database connection
$host = "localhost";
$dbname = "pharmasync";
$dbuser = "root";
$dbpass = "";

// Create database connection
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // User details from the form
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Check if passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Your passwords did not match!";
        echo '
        <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
        
        <div id="successModal" class="modal" style="display:none;">
          <div class="modal-content">
            <h2>Password Does Not Match</h2><br>
            <p>The passwords you entered do not match. Please try again, ensuring both passwords are the same.</p><br>
          </div>
        </div>
      
        <script>
          document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("successModal").style.display = "block";
      
            setTimeout(function() {
                window.history.back()
             }, 5000); // 5000 milliseconds = 5 seconds
          });
      
          window.onclick = function(event) {
            const modal = document.getElementById("successModal");
            if (event.target == modal) {
                window.history.back() 
            }
          };
        </script>
      ';
      exit();
    }

    // Check if email already exists
    $stmt_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_email->bind_param("s", $email);
    $stmt_email->execute();
    $stmt_email->store_result();
    if ($stmt_email->num_rows > 0) {
        echo '
        <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
        
        <div id="successModal" class="modal" style="display:none;">
          <div class="modal-content">
            <h2>Email Address Already Exists</h2><br>
            <p>The email address you provided is already associated with an existing account. Please log in or use a different email to create a new account.</p><br>
          </div>
        </div>
      
        <script>
          document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("successModal").style.display = "block";
      
            setTimeout(function() {
                window.history.back()
             }, 5000); // 5000 milliseconds = 5 seconds
          });
      
          window.onclick = function(event) {
            const modal = document.getElementById("successModal");
            if (event.target == modal) {
                window.history.back() 
            }
          };
        </script>
      ';
      exit();
    }
    $stmt_email->close();

    // Check if phone number already exists
    $stmt_phone = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt_phone->bind_param("s", $phone_number);
    $stmt_phone->execute();
    $stmt_phone->store_result();
    if ($stmt_phone->num_rows > 0) {
        echo '        
        <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
        
        <div id="successModal" class="modal" style="display:none;">
          <div class="modal-content">
            <h2>Phone Number Already Exists</h2><br>
            <p>The phone number you provided is already associated with an existing account. Please log in or use a different phone number to create a new account.</p><br>
          </div>
        </div>
      
        <script>
          document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("successModal").style.display = "block";
      
            setTimeout(function() {
                window.history.back()
             }, 5000); // 5000 milliseconds = 5 seconds
          });
      
          window.onclick = function(event) {
            const modal = document.getElementById("successModal");
            if (event.target == modal) {
                window.history.back() 
            }
          };
        </script>
';
        exit();
    }
    $stmt_phone->close();

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, username, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone_number, $username, $password_hash);
    
    if ($stmt->execute()) {
        header('Location: http://localhost/pharmasync/PHP/callback.php');
        exit();     
    } else {
        echo 'Error creating account';
    }
    $stmt->close();
    $conn->close();
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
    <li>
      <a class="active" href="Health_FAQ.html">Resources</a>
    </li>
  </ul>
  <div class="main">
  </div>
</header>
<br>



<section id="registration-form-section">
    <br><br><br><br>

    <!-- Step 1: Personal Details -->
    <div id="step-1" class="reg-form-container">
        <div style="display: flex; align-items: center;">
            <img src="PS_img/PS_logo.png" alt="Logo" class="logo-img" />
            <span>PharmaSync</span>
        </div>
        
        <h3>Create an Account</h3>
        <p>By clicking "continue" or "sign-in" below, you agree to PharmaSync's Terms of Service and Privacy Policy</p>

        <form id="registration-form" method="POST">
            <div class="reg-form-section">
                <label for="first-name">First Name*</label>
                <input type="text" id="first-name" name="first_name" placeholder="Enter your first name" required>
            </div>
            <div class="reg-form-section">
                <label for="last-name">Last Name*</label>
                <input type="text" id="last-name" name="last_name" placeholder="Enter your last name" required>
            </div>
            <div class="reg-form-section">
                <label for="email">Email Address*</label>
                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
            </div>
            <div class="reg-form-section">
                <label for="phone-number">Phone Number*</label>
                <input type="text" id="phone-number" name="phone_number" required pattern="^09\d{9}$" title="Phone number must be 11 digits long and start with 09" placeholder="09XXXXXXXXX">
            </div>
            <button type="button" id="next-1" class="btn">Next</button>
    </div>

    <!-- Step 2: Username and Password -->
    <div id="step-2" class="reg-form-container" style="display: none;">
        <h2>Username and Password</h2>
        <div class="reg-form-section">
            <label for="username">Username*</label>
            <input type="text" id="username" name="username" readonly>
        </div>
        <div id="password-rules">
            <p>Password must include:</p>
            <ul>
                <li id="length" class="invalid">At least 8 characters</li>
                <li id="uppercase" class="invalid">At least one uppercase letter</li>
                <li id="lowercase" class="invalid">At least one lowercase letter</li>
                <li id="number" class="invalid">At least one number</li>
                <li id="special" class="invalid">At least one special character</li>
            </ul>
        </div>

        <div class="reg-form-section">
            <label for="password">Password*</label>
            <input type="password" id="password" name="password" placeholder="Create your password" required>
        </div>
        <div class="reg-form-section">
            <label for="confirm_password">Confirm Password*</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
        </div>
        <button type="button" id="back-3" class="btn">Back</button>
        <button type="submit" class="btn">Submit</button>
    </div>
</form>

</section>

<script>
// Step 1 to Step 2 (Next Button)
document.getElementById('next-1').addEventListener('click', function () {
    if (document.getElementById('first-name').value && document.getElementById('last-name').value &&
        document.getElementById('email').value && document.getElementById('phone-number').value) {
        document.getElementById('step-1').style.display = 'none';
        document.getElementById('step-2').style.display = 'block';

        // Generate the username dynamically
        var firstName = document.getElementById('first-name').value.trim();
        var randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0'); // Ensure 4 digits
        document.getElementById('username').value = 'PS' + firstName.substr(0, 2).toUpperCase() + randomNum;
    } else {
        alert('Please fill all required fields.');
    }
});

// Back Buttons: Step 2 to Step 1
document.getElementById('back-3').addEventListener('click', function () {
    document.getElementById('step-2').style.display = 'none';
    document.getElementById('step-1').style.display = 'block';
});

// Password validation
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');
const submitButton = document.getElementById('submit-button');
const rules = {
    length: document.getElementById('length'),
    uppercase: document.getElementById('uppercase'),
    lowercase: document.getElementById('lowercase'),
    number: document.getElementById('number'),
    special: document.getElementById('special')
};

passwordInput.addEventListener('input', () => {
    const value = passwordInput.value;

    // Check password length
    rules.length.classList.toggle('valid', value.length >= 8);
    rules.length.classList.toggle('invalid', value.length < 8);

    // Check for uppercase letter
    rules.uppercase.classList.toggle('valid', /[A-Z]/.test(value));
    rules.uppercase.classList.toggle('invalid', !/[A-Z]/.test(value));

    // Check for lowercase letter
    rules.lowercase.classList.toggle('valid', /[a-z]/.test(value));
    rules.lowercase.classList.toggle('invalid', !/[a-z]/.test(value));

    // Check for number
    rules.number.classList.toggle('valid', /[0-9]/.test(value));
    rules.number.classList.toggle('invalid', !/[0-9]/.test(value));

    // Check for special character
    rules.special.classList.toggle('valid', /[!@#$%^&*(),.?":{}|<>]/.test(value));
    rules.special.classList.toggle('invalid', !/[!@#$%^&*(),.?":{}|<>]/.test(value));

    // Enable submit button if password is valid
    submitButton.disabled = !isPasswordValid();
});

confirmPasswordInput.addEventListener('input', () => {
    submitButton.disabled = !isPasswordValid();
});

function isPasswordValid() {
    return passwordInput.value === confirmPasswordInput.value &&
        passwordInput.value.length >= 8 &&
        /[A-Z]/.test(passwordInput.value) &&
        /[a-z]/.test(passwordInput.value) &&
        /[0-9]/.test(passwordInput.value) &&
        /[!@#$%^&*(),.?":{}|<>]/.test(passwordInput.value);
}

// Form validation before submission
function validateForm() {
    if (!isPasswordValid()) {
        alert('Please ensure your passwords match and meet the requirements.');
        return false;
    }
    return true;
}
</script>

</body>
</html>
