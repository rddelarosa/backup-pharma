<?php
include("connect.php");

$images = [];
$profilePicName = isset($profilePicName) ? $profilePicName : "";

if (!empty($profilePicName)) {
    $stmt = $conn->prepare("SELECT id, image_name, image_data FROM default_pictures WHERE image_name != ?");
    $stmt->bind_param("s", $profilePicName);
} else {
    $stmt = $conn->prepare("SELECT id, image_name, image_data FROM default_pictures");
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}
$stmt->close();

$currentProfilePicData = null;
if (!empty($profilePicName)) {
    $stmt = $conn->prepare("SELECT image_data FROM default_pictures WHERE image_name = ?");
    $stmt->bind_param("s", $profilePicName);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($currentProfilePicData);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
    }
    $stmt->close();
}

if ($currentProfilePicData) {
    $currentProfilePic = [
        'id' => 'current',
        'image_name' => 'Current Profile Picture',
        'image_data' => $currentProfilePicData
    ];
    array_unshift($images, $currentProfilePic);
}

?>
