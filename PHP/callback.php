<?php
session_start();
include("connect.php");


define('CLIENT_ID', '440041566001-chtjorklib2t4rdvs1hrkr7pq9tm3994.apps.googleusercontent.com');
define('CLIENT_SECRET', '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');
define('REDIRECT_URI', 'http://localhost/PharmaSync/PHP/callback.php');

$authUrl = 'https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=' . CLIENT_ID . '&redirect_uri=' . REDIRECT_URI . '&scope=https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile&access_type=offline';

if (!isset($_GET['code'])) {
    header('Location: ' . $authUrl);
    exit();
} else {
    $code = $_GET['code'];

    $tokenData = [
        'code' => $code,
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'redirect_uri' => REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($tokenData),
        ]
    ]));

    $token = json_decode($tokenResponse, true);

    if (isset($token['access_token'])) {
        $_SESSION['access_token'] = $token['access_token'];
        $accessToken = $token['access_token'];

        $userInfo = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $accessToken);
        $user = json_decode($userInfo, true);

        if (isset($user['email'])) {
            $email = $user['email'];
            $firstName = $user['given_name'] ?? '';

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() === 0) {
                $insertStmt = $pdo->prepare("INSERT INTO users (email, first_name, goog_id) VALUES (?, ?, ?)");
                $insertStmt->execute([$email, $firstName, $accessToken]);
                echo "✅ Account successfully created with Google!";
            } else {
                $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE email = ?");
                $updateStmt->execute([$accessToken, $email]);
                $_SESSION['redirect_to_account'] = true; 

                }
        } else {
            echo '⚠️ Failed to retrieve user email.';
        }
    } else {
        echo '❌ Error: Failed to obtain access token.';
    }
}

header("Location: http://localhost/PharmaSync/dashboard.php");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div id="successModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h2>Account successfully linked to email! </h2>
  </div>
</div>

<script>
  function goToLogin() {
    setTimeout(function() {
        window.location.href = "http://localhost/PharmaSync/dashboard.php"; 
    }, 5000); 
  }

  window.onclick = function(event) {
    const modal = document.getElementById('successModal');
    if (event.target == modal || event.target == window) {
        window.location.href = "http://localhost/PharmaSync/dashboard.php";
    }
  }
</script>

</body>
</html>