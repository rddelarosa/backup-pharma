<?php
session_start(); 
include("connect.php");


if (!isset($_SESSION['id'])) {
    echo "User not logged in. Redirecting to login page...<br>";
    header("Location: login.php");
}

$userId = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $birthday = $_POST['birthday'];
    $emergencyContact = $_POST['emergency_contact'];
    $username = $_POST['username'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $updateStmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, birthday = ?, emergency_contact = ?, username = ?, gender = ?, email = ?, phone = ? WHERE id = ?");
    $updateStmt->bind_param("ssssssssi", $firstName, $lastName, $birthday, $emergencyContact, $username, $gender, $email, $phone, $userId);

    if ($updateStmt->execute()) {
        $_SESSION['redirect_to_account'] = true;
        $_SESSION['change_done'] = "Your Account Information is sucessfully updated";
        $_SESSION['change_done_heading'] = "Success!";
    
        header("Location: http://localhost/pharmasync/dashboard.php"); 
        exit();
    } else {
        echo "Error updating account details: " . $updateStmt->error;
    }

    $updateStmt->close();
} else {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, birthday, emergency_contact, username, gender, email, phone, profile_pic_name, google_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $firstName, $lastName, $birthday, $emergencyContact, $username, $gender, $email, $phone, $profilePicName, $accessToken);
    
    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (!empty($profilePicName)) {
            $imageUrl = "picture.php?image=" . urlencode($profilePicName);
        } else {
            $imageUrl = ''; 
        }
    } else {
        exit("User not found.");
    }
    $stmt->close();
}

$prescriptionStmt = $conn->prepare("SELECT medicine_id, medicine_name, dose, frequency, start_date, end_date, stock, unit, hours_interval, stock_notif, unit, stock_remind_value FROM current_medicines WHERE user_id = ?");
$prescriptionStmt->bind_param("i", $userId);
$prescriptionStmt->execute();
$prescriptionStmt->store_result();
$prescriptionStmt->bind_result($medicineId, $medicineName, $dosespres, $frequency, $startDate, $endDate, $stock, $unit, $hours_interval, $stocknotif, $unit, $stock_remind_value);

$prescriptions = [];

while ($prescriptionStmt->fetch()) {
    $doseSql = "SELECT id, scheduled_time, is_taken, is_skip
                FROM medicine_doses 
                WHERE medicine_id = ? AND user_id = ? AND is_taken = 0 
                AND DATE(scheduled_time) = CURDATE() 
                ORDER BY scheduled_time ASC";
    $doseStmt = $conn->prepare($doseSql);
    $doseStmt->bind_param("ii", $medicineId, $userId);
    $doseStmt->execute();
    $doseResult = $doseStmt->get_result();

    $todayDoses = [];
    $isNextDue = false;
    $isNextTaken = false;

    if ($doseResult->num_rows > 0) {
        while ($dose = $doseResult->fetch_assoc()) {
            $scheduledTime = strtotime($dose['scheduled_time']);
            $now = time();
            $isDue = $scheduledTime <= $now;
            $taken = $dose['is_taken'];
            $skip = $dose['is_skip'];


            $todayDoses[] = [
                'dose_id' => $dose['id'],
                'scheduled_time' => date("g:i A", $scheduledTime),
                'is_due' => $isDue,
                'is_taken' => $taken,
                'is_skip' => $skip

            ];

       
        }

        $prescriptions[] = [
            'medicine_id' => $medicineId,
            'medicine_name' => $medicineName,
            'dose' => $dosespres,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'stock' => $stock,
            'is_due' => $isNextDue,
            'taken' => $isNextTaken,
            'hours_interval' => $hours_interval, 
            'today_doses' => $todayDoses,
            'stock_notif' => $stocknotif,
            'unit' => $unit,
            'stock_remind_value' => $stock_remind_value

        ];

    } else {
        $prescriptions[] = [
            'medicine_id' => $medicineId,
            'medicine_name' => $medicineName,
            'dose' => $dosespres,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'stock' => $stock,
            'next_dose' => 'No dose today',
            'is_due' => false,
            'taken' => false,
            'today_doses' => [],
            'stock_notif' => $stocknotif,
            'unit' => $unit,
            'stock_remind_value' => $stock_remind_value


        ];
    }

    $doseStmt->close();
}


$prescriptionStmt->close();


$query = "
    SELECT m.medicine_name, m.dose, m.frequency, m.start_date, m.end_date, d.scheduled_time, d.is_taken, d.is_skip
    FROM previous_medicines m
    LEFT JOIN done_medicine_doses d ON m.medicine_id = d.medicine_id
    WHERE m.user_id = ?
    ORDER BY m.medicine_name, d.scheduled_time
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId); 
$stmt->execute();
$result = $stmt->get_result();

$previous_medicines = $result->fetch_all(MYSQLI_ASSOC);

$medicines = [];
foreach ($previous_medicines as $row) {
    $medicine_name = $row['medicine_name'];
    if (!isset($medicines[$medicine_name])) {
        $medicines[$medicine_name] = [
            'medicine_name' => $row['medicine_name'],
            'dose' => $row['dose'],
            'frequency' => $row['frequency'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'doses' => []
        ];
    }
    $medicines[$medicine_name]['doses'][] = [
        'time' => $row['scheduled_time'],
        'is_taken' => $row['is_taken'],
        'is_skip' => $row['is_skip'],
    ];
}
$medicines = array_values($medicines);



$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));

$sql = "
SELECT 
    m.medicine_id, 
    m.medicine_name, 
    m.frequency,
    m.hours_interval,  
    m.end_date,
    m.unit,
    DATE(d.scheduled_time) AS date_taken, 
    d.is_taken,
    d.is_skip,
    d.scheduled_time
FROM current_medicines m
LEFT JOIN medicine_doses d ON m.medicine_id = d.medicine_id 
WHERE m.user_id = ? 
AND DATE(d.scheduled_time) BETWEEN ? AND ?
ORDER BY m.medicine_name, d.scheduled_time
";

$weeklyQuery = $conn->prepare($sql);
$weeklyQuery->bind_param("iss", $userId, $startOfWeek, $endOfWeek);
$weeklyQuery->execute();
$result = $weeklyQuery->get_result();

$weeklyMedicineData = [];

while ($row = $result->fetch_assoc()) {
    $medicineId = $row['medicine_id'];
    $medicineName = $row['medicine_name'];
    $frequency = $row['frequency'];
    $hours_interval = isset($row['hours_interval']) ? $row['hours_interval'] : 24;  
    $date = $row['date_taken'];
    $isTaken = isset($row['is_taken']) ? $row['is_taken'] : 0; 
    $isSkip = isset($row['is_skip']) ? $row['is_skip'] : 0; 
    $scheduledTime = $row['scheduled_time']; 
    $dayOfWeek = date('D', strtotime($date)); 
    $endDate =  $row['end_date'];
    $unit= $row['unit'];

    if (!isset($weeklyMedicineData[$medicineId])) {
        $weeklyMedicineData[$medicineId] = [
            'dose' => $dosespres,
            'medicine_id' => $medicineId,
            'medicine_name' => $medicineName,
            'frequency' => $frequency, 
            'hours_interval' => $hours_interval, 
            'end_date' => $endDate,
            'unit' => $unit,
            'days' => [
                'Mon' => [],
                'Tue' => [],
                'Wed' => [],
                'Thu' => [],
                'Fri' => [],
                'Sat' => [],
                'Sun' => []
            ]
        ];
    }

    if ($isSkip == 1) {
        $status = "status_3_skipped";
    } elseif ($isTaken == 1) {
        $status = "status_1_taken";
    } elseif ($scheduledTime <= date('Y-m-d H:i:s')) {
        $status = "status_2_missed";
    } else {
        $status = "status_4_upcoming";
    }

    $weeklyMedicineData[$medicineId]['days'][$dayOfWeek][] = $status;
}

$weeklyQuery->close(); 



$darkMode = false; 
$remindPre = false;
$remindVal = null;  

$settingsStmt = $conn->prepare("SELECT dark_mode, email_notif, remind_pre, remind_val FROM users WHERE id = ?");
$settingsStmt->bind_param("i", $userId);
$settingsStmt->execute();
$settingsStmt->bind_result($darkModeSetting, $emailNotifSetting, $remindPreSetting, $remindValSetting);

if ($settingsStmt->fetch()) {
    $darkMode = (bool)$darkModeSetting;
    $emailNotif = (bool)$emailNotifSetting;
    $remindPre = (bool)$remindPreSetting;
    $remindVal = $remindValSetting;
}

$settingsStmt->close();




$weeklyData = [];

$sql = "SELECT 
            d.id,
            m.medicine_id,
            m.medicine_name,
            d.scheduled_time,
            d.dosage,
            d.is_taken,
            d.is_skip,
            d.taken_time
        FROM 
            current_medicines m
        JOIN 
            medicine_doses d ON m.medicine_id = d.medicine_id
        WHERE 
            m.user_id = ?
            AND d.scheduled_time >= CURDATE() - INTERVAL 7 DAY
        ORDER BY 
            d.scheduled_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        if ($row['is_skip'] == 1) {
            $status = "Skipped";
        } elseif ($row['is_taken'] == 1) {
            $status = "Taken";
        } elseif ($row['scheduled_time'] <= date('Y-m-d H:i:s')) {
            $status = "Missed";
        } else {
            $status = "Upcoming";
        }

        $weeklyData[] = [
            'id' => $row['id'],
            'medicine_id' => $row['medicine_id'],
            'medicine_name' => $row['medicine_name'],
            'date' => date('Y-m-d', strtotime($row['scheduled_time'])),
            'time_taken' => $row['taken_time'],
            'dosage' => $row['dosage'],
            'status' => $status
        ];
    }
}

$stmt->close();




?>


