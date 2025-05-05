<?php
session_start();
include("connect.php");
date_default_timezone_set('Asia/Manila');
$userId = $_SESSION['id'];

$timeframe = isset($_POST['timeframe']) ? $_POST['timeframe'] : 'today'; 

$startOfPeriod = isset($_GET['start']) ? $_GET['start'] . " 00:00:00" : date('Y-m-d 00:00:00');
$endOfPeriod = isset($_GET['end']) ? $_GET['end'] . " 23:59:59" : date('Y-m-d 23:59:59');

$sql = "
SELECT 
    m.medicine_id, 
    m.medicine_name, 
    m.frequency, 
    d.scheduled_time, 
    d.is_taken,
    d.is_skip

FROM 
    current_medicines m
LEFT JOIN 
    medicine_doses d ON m.medicine_id = d.medicine_id 
WHERE 
    m.user_id = ? 
    AND d.scheduled_time >= ? 
    AND d.scheduled_time < ?
ORDER BY 
    m.medicine_name, d.scheduled_time;";

$medQuery = $conn->prepare($sql);
$medQuery->bind_param("iss", $userId, $startOfPeriod, $endOfPeriod); 

if (!$medQuery->execute()) {
    echo "Error executing query: " . $medQuery->error . "<br>";
}

$result = $medQuery->get_result();
$adherenceData = [
    'taken' => 0,
    'missed' => 0,
    'skipped' => 0
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        if ($row['is_taken'] == 1) {
            $adherenceData['taken']++;
        } elseif ($row['is_taken'] == 0 && $row['scheduled_time'] <= date('Y-m-d H:i:s') && $row['is_skip'] == 0 ) {
            $adherenceData['missed']++;
        } elseif ($row['is_skip'] == 1) {
            $adherenceData['skipped']++;
        }
    }
} 
$medQuery->close();

echo json_encode($adherenceData);
?>
