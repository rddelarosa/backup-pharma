<?php
session_start();
include("connect.php");

$userId = $_SESSION['id']; 

function fetchProfileImage($conn) {
    if (isset($_GET['image']) && !empty($_GET['image'])) {
        $imageName = $_GET['image'];
        $imageData = null;

        $stmtPic = $conn->prepare("SELECT image_data FROM default_pictures WHERE image_name = ?");
        $stmtPic->bind_param("s", $imageName);
        $stmtPic->execute();
        $stmtPic->store_result();
        $stmtPic->bind_result($imageData);

        if ($stmtPic->num_rows > 0) {
            $stmtPic->fetch();

            if (!empty($imageData)) {
                header("Content-Type: image/jpeg");
                echo $imageData;
                exit(); 
            }
        }

        $stmtPic->close();
    } 
}
fetchProfileImage($conn);

function fetchOtherUploadedImages($conn) {
    $sql = "SELECT id, image_name, image_data FROM default_pictures";  
    $result = $conn->query($sql);

    return $result;
}

$sql = "SELECT profile_pic_name FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($currentProfilePicName);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['profilePicId']) && !empty($_POST['profilePicId'])) {
        $profilePicId = $_POST['profilePicId'];

        $stmtImg = $conn->prepare("SELECT image_name FROM default_pictures WHERE id = ?");
        $stmtImg->bind_param("i", $profilePicId);
        $stmtImg->execute();
        $stmtImg->bind_result($imageName);
        $stmtImg->fetch();
        $stmtImg->close();

        if (!empty($imageName)) {
            $stmt = $conn->prepare("UPDATE users SET profile_pic_name = ? WHERE id = ?");
            $stmt->bind_param("si", $imageName, $userId);
            $stmt->execute();
            $stmt->close();
        }
    } 
    
    elseif (isset($_FILES['profilePicFile']) && $_FILES['profilePicFile']['error'] === UPLOAD_ERR_OK) {
        $imageName = $_FILES['profilePicFile']['name'];
        $imageData = file_get_contents($_FILES['profilePicFile']['tmp_name']);

        $stmt = $conn->prepare("INSERT INTO images (image_name, image_data) VALUES (?, ?)");
        $stmt->bind_param("ss", $imageName, $imageData);

        if ($stmt->execute()) {
            $newImageName = $imageName;

            $stmtUpdate = $conn->prepare("UPDATE users SET profile_pic_name = ? WHERE id = ?");
            $stmtUpdate->bind_param("si", $newImageName, $userId);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }

        $stmt->close();
    }

    $_SESSION['redirect_to_account'] = true; 
    $_SESSION['change_done'] = "Your Profile Picture is sucessfully updated";
    $_SESSION['change_done_heading'] = "Success!";

    header("Location: http://localhost/pharmasync/dashboard.php");
    exit();
}

$uploadedImages = fetchOtherUploadedImages($conn);
$conn->close();
?>
