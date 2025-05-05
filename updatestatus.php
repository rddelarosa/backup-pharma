<?php
// Assuming you already have a valid database connection in $conn

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doseId = (int) $_POST['dose_id'];  // Dose ID to identify the specific dose
    $newStatus = $_POST['new_status'];  // New status (e.g., taken, missed, skipped)

    // Prepare and execute the update query
    $sql = "UPDATE medicine_doses SET status = ? WHERE dose_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $newStatus, $doseId);

    if ($stmt->execute()) {
        echo "Status updated successfully.";
    } else {
        echo "Error updating status.";
    }

    $stmt->close();
}
?>
