<?php
session_start();
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // User details from the form
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Start validation checks
    if ($password !== $confirm_password) {
        // Password mismatch
        $_SESSION['error_heading'] = "Password Mismatch";
        $_SESSION['error'] = "The passwords you entered do not match. Please try again, ensuring both passwords are the same.";
        header("Location: register.php");
        exit();
    } elseif (checkIfExists($conn, 'email', $email)) {
        $_SESSION['error_heading'] = "Email Address Already Exists";
        $_SESSION['errorEmail'] = "The email address ($email) you provided is already associated with an existing account. Please log in or use a different email to create a new account.";
        header("Location: register.php");
        exit();
    } elseif (checkIfExists($conn, 'phone', $phone_number)) {
        // Phone number already exists
        $_SESSION['error_heading'] = "Phone Number Already Exists";
        $_SESSION['errorPhone'] = "The phone number you provided is already associated with an existing account. Please use a different phone number to create a new account.";
        header("Location: register.php");
        exit();
    } else {
        // Hash the password and insert the user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, username, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone_number, $username, $password_hash);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Your account has been created successfully.";
            $_SESSION['success_heading'] = "Registration Successful";
            header("Location: register.php");
            exit();
        } else {
            echo 'Error creating account';
        }
        $stmt->close();
    }
    $conn->close();
}

// Function to check if email or phone number exists
function checkIfExists($conn, $field, $value) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE $field = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>

<!-- Navigation Bar -->
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

</header>
<br>

<section id="registration-form-section">
    <br><br><br><br>

    <!-- Step 1: Personal Details -->
    <div id="step-1" class="reg-form-container">
        <div style="display: flex; justify-content: center; align-items: center; text-align: center;">
            <img src="PS_img/PS_logo.png" alt="Logo"/>
            <span style="margin-left: 10px;">PharmaSync</span>
        </div>

        <h3>Create an Account</h3>
        <p>By creating an account, you agree to PharmaSync's 
            <a href="TOS.php" target="_blank">Terms of Service and Privacy Policy</a></p>
        <p class="alternate-action">
            Already have an account? <a href="Login.php">Sign In</a>
        </p>   
        <form id="registration-form" method="POST">
        <table>
        <tr>
            <td style="padding-bottom: 0;"><label for="first-name">First Name <label style="color: red; display: inline;">*</label></label></td>
            <td style="padding-bottom: 0;"><label for="last-name">Last Name <label style="color: red; display: inline;">*</label></label></td>
        </tr>
        <tr>
            <td style="padding-top: 0;"><input type="text" id="first-name" name="first_name" placeholder="Enter your first name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required></td>
            <td style="padding-top: 0;"><input type="text" id="last-name" name="last_name" placeholder="Enter your last name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required></td>
        </tr>

        <tr>
            <td style="padding-bottom: 0;"><label for="email">Email Address <label style="color: red; display: inline;">*</label></label></td>
        </tr>
        <tr>
            <td colspan="3" style="padding-top: 0;"><input type="email" id="email" name="email" placeholder="Enter your email address" required></td>
        </tr>
        <tr>
            <td style="padding-bottom: 0;"><label for="phone-number">Phone Number <label style="color: red; display: inline;">*</label></label></td>
        </tr>
        <tr>
            <td colspan="3" style="padding-top: 0;"><input type="text" id="phone-number" name="phone_number" required pattern="^09\d{9}$" title="Phone number must be 11 digits long and start with 09" placeholder="09XXXXXXXXX"></td>
        </tr>
        </table>
            <center><button type="button" id="next-1" class="btn">Next</button></center>
    </div>

    <!-- Step 2: Username and Password -->
    <div id="step-2" class="reg-form-container" style="display: none;">
        <div><i class="ri-arrow-left-line back-button" id="back-3"></i></div>
    <br><br>
        <div class="reg-form-section">
            <label for="username">Username <label style="color: red; display: inline;">*</label></label>
            <input type="text" id="username" name="username" readonly>
        </div>
        <div class="reg-form-section" id="password-rules">
            <label>Password must include:</label>
            <ul>
                <li id="length" class="invalid">At least 8 characters</li>
                <li id="uppercase" class="invalid">At least one uppercase letter</li>
                <li id="lowercase" class="invalid">At least one lowercase letter</li>
                <li id="number" class="invalid">At least one number</li>
                <li id="special" class="invalid">At least one special character</li>
            </ul>
        </div>

        <div class="reg-form-section">
            <label for="password">Password <label style="color: red; display: inline;">*</label></label>
            <input type="password" id="password" name="password" placeholder="Create your password" required>
        </div>
        <div class="reg-form-section">
            <label for="confirm_password">Confirm Password <label style="color: red; display: inline;">*</label></label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
        </div>
        <center><button onclick="return validateForm()" type="submit" class="btn">Submit</button></center>
    </div>
    </form>

</section>

<script>
// Next Button

document.getElementById('next-1').addEventListener('click', function () {
    const phoneNumber = document.getElementById('phone-number').value; // Get the phone number value

    // Validate if all required fields are filled
    if (document.getElementById('first-name').value && document.getElementById('last-name').value &&
        document.getElementById('email').value && phoneNumber) {

        // Phone number validation (must be 11 digits starting with 09)
        const phonePattern = /^09\d{9}$/;
        if (!phonePattern.test(phoneNumber)) {
            alert('Please enter a valid phone number starting with 09 and consisting of 11 digits.');
            return; // Prevent proceeding to the next step
        }

        // If all conditions are met, proceed to step 2
        document.getElementById('step-1').style.display = 'none';
        document.getElementById('step-2').style.display = 'block';

        var firstName = document.getElementById('first-name').value.trim();
        var randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0'); // Ensure 4 digits
        document.getElementById('username').value = 'PS' + firstName.substr(0, 2).toUpperCase() + randomNum;

    } else {
        alert('Please fill all required fields.');
    }
});


// Back Buttons
document.getElementById('back-3').addEventListener('click', function () {
    document.getElementById('step-2').style.display = 'none';
    document.getElementById('step-1').style.display = 'block';
});

// Password validation
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');
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
function showErrorModal(heading, message) {
    document.getElementById('modalHeading').textContent = heading;
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function validateForm() {
    if (!isPasswordValid()) {
        showErrorModal(
            'Password Error',
            'Please ensure your password match and meet all the required conditions (uppercase, lowercase, number, special character, and at least 8 characters).'
        );
        return false;
    }
    return true;
}


</script>

<script type="text/javascript" src="script_PS.js"></script>

<!-- Modals (hidden by default) -->
<div id="errorModal" class="modal">
    <div class="modal-content">
        <div><i class="ri-close-line x-button" onclick="closeModal('errorModal')"></i></div>
        <h2 id="modalHeading"></h2><br>
        <p id="errorMessage"></p><br>
    </div>
</div>

<div id="successModal" class="modal">
    <div class="modal-content">
        <h2 id="modalHeadingSuccess"></h2><br>
        <p id="successMessage"></p><br>
        <button class="button" onclick="window.location.href='Login.php';">Sign In</button><br>
        </div>
</div>

<!-- JavaScript to handle modal display and form submission -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if any session messages are set
        <?php if (isset($_SESSION['error'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['error']; ?>', '<?php echo $_SESSION['error_heading']; ?>');
            <?php unset($_SESSION['error'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['errorEmail'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['errorEmail']; ?>', '<?php echo $_SESSION['error_heading']; ?>');
            <?php unset($_SESSION['errorEmail'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['errorPhone'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['errorPhone']; ?>', '<?php echo $_SESSION['error_heading']; ?>');
            <?php unset($_SESSION['errorPhone'], $_SESSION['error_heading']); ?>
        <?php elseif (isset($_SESSION['success'])): ?>
            showModal('successModal', '<?php echo $_SESSION['success']; ?>', '<?php echo $_SESSION['success_heading']; ?>');
            <?php unset($_SESSION['success'], $_SESSION['success_heading']); ?>
        <?php endif; ?>
    });

    // Function to show the modal with a custom message
    function showModal(modalId, message, heading) {
        var modal = document.getElementById(modalId);
        var messageElement = modal.querySelector('#errorMessage') || modal.querySelector('#successMessage');
        var headingElement = modal.querySelector('#modalHeading') || modal.querySelector('#modalHeadingSuccess');
        
        if (messageElement) {
            messageElement.textContent = message;
        }

        if (headingElement) {
            headingElement.textContent = heading;
        }

        modal.style.display = 'block';
    }

    // Function to close the modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
</script>




</body>
</html>
