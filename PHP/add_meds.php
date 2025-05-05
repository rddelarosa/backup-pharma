<?php
session_start();
include("connect.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION["id"])) {
  die("User is not logged in. Please log in first.");
} 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["id"];
    $medicine_name = $_POST["medicine_name"];
    $dose = $_POST["dose"];
    $frequency = $_POST["frequency"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $hours_interval = $_POST["hours_interval"] ?? null;
    $stock = $_POST["stock"] ?? 0;
    $unit = $_POST["unit"];
    $remindStock = $_POST["stock_remind_value"] ?? 0;
    $stock_notif = $_POST["stock_notif"] ?? 0;
    $instructions = $_POST["instructions"] ?? '';

    $time1 = $_POST['time1'];
    $time2 = $_POST['time2'] ?? null;
    $time3 = $_POST['time3'] ?? null;
    $time4 = $_POST['time4'] ?? null;

    $dosage1 = $_POST['dosage1'] ?? null;
    $dosage2 = $_POST['dosage2'] ?? null;
    $dosage3 = $_POST['dosage3'] ?? null;
    $dosage4 = $_POST['dosage4'] ?? null;

    $stmt = $conn->prepare("INSERT INTO current_medicines 
        (user_id, medicine_name, dose, frequency, start_date, end_date, instructions, hours_interval, stock, unit, stock_remind_value, stock_notif)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssiisii", $user_id, $medicine_name, $dose, $frequency, $start_date, $end_date, $instructions, $hours_interval, $stock, $unit, $remindStock, $stock_notif);
    $stmt->execute();
    $medicine_id = $stmt->insert_id;
    $stmt->close();

    $user_query = $conn->prepare("SELECT email, email_notif, first_name FROM users WHERE id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();

    if ($user_result && $user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_email = $user_data['email'];
        $email_notif = $user_data['email_notif'];
        $firstName = $user_data['first_name'];

        if ((int)$email_notif === 1) {
            try {
                $mail = new PHPMailer(true);  
                $mail->isSMTP();                                  
                $mail->SMTPAuth   = true;  
                $mail->Host = 'smtp.gmail.com';
                $mail->Username = 'pharmasync.ph@gmail.com';
                $mail->Password   = 'msnh jdhw efvw xnwb';  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@pharmasync.com', 'PharmaSync');
                $mail->addAddress($user_email);  

                $mail->isHTML(false);                                 
                $mail->Subject = "Medicine Added Successfully";
                $mail->Body    = "Hello $firstName,\n\nYour medicine has been successfully added to your tracking system:\n\n".
                                 "Medicine Name: $medicine_name\n".
                                 "Dose: $dose $unit\n".
                                 "Frequency: $frequency\n".
                                 "Start Date: $start_date\n".
                                 "End Date: $end_date\n".
                                 "Thank you for using PharmaSync!";

                $mail->send(); 
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }
        }
    }

    $user_query->close();

    $scheduled_times = [];
    $fixed_frequencies = [
        "Before meals (AC)" => ["07:00:00", "11:00:00", "17:00:00"],
        "After meals (PC)" => ["09:00:00", "13:00:00", "19:00:00"],
        "At bedtime (HS)" => ["22:00:00"]
    ];

    if ($frequency == "Once daily") {
        $scheduled_times = [$time1];
    } elseif ($frequency == "Twice daily") {
        $scheduled_times = [$time1, $time2];
    } elseif ($frequency == "Three times daily") {
        $scheduled_times = [$time1, $time2, $time3];
    } elseif ($frequency == "Four times daily") {
        $scheduled_times = [$time1, $time2, $time3, $time4];
    } elseif ($frequency == "Every X hours" && $hours_interval) {
        $current = new DateTime($start_date . ' ' . $time1);
        $end = new DateTime($end_date . ' 23:59:59');
        $interval = new DateInterval("PT{$hours_interval}H");
        $scheduled_times = [];

        while ($current <= $end) {
            $scheduled_times[] = $current->format('Y-m-d H:i:s');
            $current->add($interval);
        }
    }

    $dose_stmt = $conn->prepare("INSERT INTO medicine_doses (medicine_id, user_id, scheduled_time, medicine_name, dosage) VALUES (?, ?, ?, ?, ?)");
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D');

    if (in_array($frequency, ["Once daily", "Twice daily", "Three times daily", "Four times daily"])) {
        while ($current <= $end) {
            foreach ($scheduled_times as $index => $time) {
                $scheduled_time_str = $current->format('Y-m-d') . ' ' . $time;
                $dosage = ${'dosage' . ($index + 1)} ?? null;
                $dose_stmt->bind_param("iisss", $medicine_id, $user_id, $scheduled_time_str, $medicine_name, $dosage);
                $dose_stmt->execute();
            }
            $current->add($interval);
        }
    } elseif ($frequency === "Every X hours" && $hours_interval) {
        foreach ($scheduled_times as $scheduled_time_str) {
            $dose_stmt->bind_param("iisss", $medicine_id, $user_id, $scheduled_time_str, $medicine_name, $dose);
            $dose_stmt->execute();
        }
    }

    $dose_stmt->close();

    // Insert times into the current_medicines_times table
    $isEveryXHours = $frequency === 'Every X hours';
    $time1_insert = $isEveryXHours ? null : ($scheduled_times[0] ?? null);
    $time2_insert = $isEveryXHours ? null : ($scheduled_times[1] ?? null);
    $time3_insert = $isEveryXHours ? null : ($scheduled_times[2] ?? null);
    $time4_insert = $isEveryXHours ? null : ($scheduled_times[3] ?? null);

    $stmtTimes = $conn->prepare("INSERT INTO current_medicines_times (medicine_id, time1, time2, time3, time4) VALUES (?, ?, ?, ?, ?)");
    $stmtTimes->bind_param("issss", $medicine_id, $time1_insert, $time2_insert, $time3_insert, $time4_insert);
    $stmtTimes->execute();
    $stmtTimes->close();

    // Update frequency and redirect
    if ($frequency === "Every X hours" && $hours_interval) {
        $frequency = "Every " . $hours_interval . " hours";
    }

    $update_stmt = $conn->prepare("UPDATE current_medicines SET frequency = ? WHERE medicine_id = ? AND user_id = ?");
    $update_stmt->bind_param("sii", $frequency, $medicine_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();

    $_SESSION['redirect_to_prescription'] = true;

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('successModal').style.display = 'block';
        });
    </script>";
}
?>





<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Medicine - PharmaSync</title>
  <link href="http://localhost/pharmasync/style_PS.css" rel="stylesheet" type="text/css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
</head>

<body>
<br><br>
<div id="medicine-form-container" class="med-content-container">
<div>
  <i class="ri-close-line x-button" onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'"></i>
</div>

  <br><h1>Add your Medicine</h1>
  <form id="medicine-form" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <table>
  <tr id="medicine-row">
    <th>Medicine Name</th>
  </tr>
  <tr id="medicine-input-row">  
  <td colspan="3">
      <input type="text" name="medicine_name" id="medicine-name" required placeholder="Enter medicine name">
  </td>
  </tr>
  <tr id="dos-row"> 
    <th>Dosage</th>
  </tr>
  <tr id="dos-input-row">
    <td>
    <input type="number" name="dose" id="dose" required min="1" placeholder="Enter dose per time">
    </td>
    <td>
      <select name="unit" id="unit" required>
        <option value="milligram">milligram(s)</option>
        <option value="pill">pill(s)</option>
        <option value="ampoule">ampoule(s)</option>
        <option value="drop">drop(s)</option>
        <option value="gram">gram(s)</option>
        <option value="milliliter">milliliter(s)</option>
        <option value="capsule">capsule(s)</option>
      </select>
    </td>
  </tr>

  <tr id="frequency-row">
  <th>Frequency</th>
  </tr>
  <tr id="frequency-input-row">
    <td colspan="3">
      <select name="frequency" id="frequency" required>
        <option value="">How often do you take this medicine?</option>
        <option value="Once daily">Once daily</option>
        <option value="Twice daily">Twice daily</option>
        <option value="Three times daily">Three times daily</option>
        <option value="Four times daily">Four times daily</option>
        <option value="Before meals">Before meals</option>
        <option value="After meals">After meals</option>
        <option value="At bedtime">At bedtime</option>
        <option value="Every X hours">Every X hours</option>
      </select>
    </td>
  </tr>

  <tr id="hours-row" style="display:none;">
    <th>Hours Interval</th>
    <td colspan="3">
      <input type="number" name="hours_interval" id="hours-interval" placeholder="e.g., 4 hours" min="1" step="1">
    </td>
  </tr>
  <tr class="spacer-row">
  <td colspan="4" style="height: 20px; border: none;"></td>
</tr>

<!-- Next Button Row -->
<tr id="next-row" style="border: none;">
  <td colspan="4" style="text-align:center; border: none;">
    <button type="button" id="next-button" class="button" onclick="showExtraFields()">Next</button>
  </td>
</tr>

<tr id="time-inputs-row" style="display: none;">
  <th colspan="4">Select Dose Times</th>
</tr>

<!-- First Dose Time -->
<tr id="dose-time-1" style="display: none;">
  <td>
    <label for="time1">First Take:</label>
  </td>
  <td colspan="2">
    <input type="time" name="time1" id="time1" value="08:00">
  </td>
  <td colspan="1">
    <input type="number" name="dosage1" id="dosage1" required min="1" placeholder="Enter dosage">
  </td>
</tr>

<!-- Second Dose Time -->
<tr id="dose-time-2" style="display: none;">
  <td>
    <label for="time2">Second Take:</label>
  </td>
  <td colspan="2">
    <input type="time" name="time2" id="time2" value="12:00">
  </td>
  <td colspan="1">
    <input type="number" name="dosage2" id="dosage2" required min="1" placeholder="Enter dosage">
  </td>
</tr>

<!-- Third Dose Time -->
<tr id="dose-time-3" style="display: none;">
  <td>
    <label for="time3">Third Take:</label>
  </td>
  <td colspan="2">
    <input type="time" name="time3" id="time3" value="16:00">
  </td>
  <td colspan="1">
    <input type="number" name="dosage3" id="dosage3" required min="1" placeholder="Enter dosage">
  </td>
</tr>

<!-- Fourth Dose Time -->
<tr id="dose-time-4" style="display: none;">
  <td>
    <label for="time4">Fourth Take:</label>
  </td>
  <td colspan="2">
    <input type="time" name="time4" id="time4" value="20:00">
  </td>
  <td colspan="1">
    <input type="number" name="dosage4" id="dosage4" required min="1" placeholder="Enter dosage">
  </td>
</tr>

<!-- Extra Fields Section (initially hidden) -->
<div id="button-container" style="display:none; text-align:center; margin-top: 20px;">
<i class="ri-arrow-left-line back-button" id="back-button" onclick="goBack();" ></i>
</div>

<tbody id="extra-fields" style="display:none;">
  <tr>
    <th colspan="2">Start Date</th>
    <td colspan="2">
      <input type="date" name="start_date" id="start-date" required>
    </td>
  </tr>
  <tr>
    <th colspan="2">End Date</th>
    <td colspan="2">
      <input type="date" name="end_date" id="end-date" required>
    </td>
  </tr>
  <tr style="height: 20px; "></tr>

  <tr style="margin-bottom: 0;">
    <th colspan="4">Do you want to get remiders to refill your inventory?</th>
  </tr>
  <tr>
    <th colspan="2">Remind me</th>
    <td></td>
    <td style="width: 20px;">
      <div class="switch-row" style="margin-top: 15px;">
        <label class="switch">
          <input type="checkbox" id="stock_notif" name="stock_notif" value="1">
          <span class="slider"></span>
        </label>
      </div>
    </td>
  </tr>
  <tr>
    <th colspan="2">Current Inventory</th>
    <td colspan="2">
      <input type="number" name="stock" id="stock" required min="0" placeholder="Enter the quantity of medicine on hand" title="Input the total number of medicine on hand">
    </td>
  </tr>
  <tr>
    <th colspan="2">Remind me when</th>
    <td colspan="2">
      <input type="number" name="stock_remind_value" id="remind-stock" required min="1" value="10" title="You'll receive a reminder when your inventory drops to this quantity.">
    </td>
  </tr>
</tbody>
</table>

<!-- Hidden Buttons Initially -->
<div id="button-container-2" style="display:none; text-align:center; margin-top: 20px;">
<button type="submit" class="button">Save</button>
</div>
</form>
</div>


<script>
document.addEventListener("DOMContentLoaded", function() {

  const stockNotifCheckbox = document.getElementById("stock_notif");
  const stockInput = document.getElementById("stock");
  const remindStockInput = document.getElementById("remind-stock");

  function toggleFields() {
    if (stockNotifCheckbox.checked) {
      stockInput.disabled = false;
      remindStockInput.disabled = false;
    } else {
      stockInput.disabled = true;
      remindStockInput.disabled = true;
    }
  }

  toggleFields();
  stockNotifCheckbox.addEventListener("change", toggleFields);
});


document.addEventListener("DOMContentLoaded", function() {
    checkFields();

    document.getElementById("medicine-name").addEventListener("input", checkFields);
    document.getElementById("dose").addEventListener("input", checkFields);
    document.getElementById("frequency").addEventListener("change", checkFields);
});




function checkFields() {
    const medicineName = document.getElementById("medicine-name").value.trim();
    const dose = document.getElementById("dose").value.trim();
    const frequency = document.getElementById("frequency").value.trim();

    const nextButton = document.getElementById("next-button");

    if (medicineName && dose && frequency) {
        nextButton.disabled = false;  
    } else {
        nextButton.disabled = true;   
    }
}

function goBack() {
  const rowsToShow = [
    "medicine-row",
    "medicine-input-row",
    "dos-row",
    "dos-input-row",
    "frequency-row",
    "frequency-input-row",
    "next-row"
  ];
  rowsToShow.forEach(id => {
    const row = document.getElementById(id);
    if (row) row.style.display = "table-row";
  });

  document.getElementById("extra-fields").style.display = "none";
  document.getElementById("button-container").style.display = "none";
  document.getElementById("button-container-2").style.display = "none";

  document.getElementById("hours-row").style.display = "none";
  document.getElementById("time-inputs-row").style.display = "none";
  const doseTimes = [
    document.getElementById("dose-time-1"),
    document.getElementById("dose-time-2"),
    document.getElementById("dose-time-3"),
    document.getElementById("dose-time-4")
  ];
  doseTimes.forEach(row => row.style.display = "none");

  checkFields();
}


function showExtraFields() {
  const medicineName = document.getElementById("medicine-name").value.trim();
  const dose = document.getElementById("dose").value.trim();
  const frequency = document.getElementById("frequency").value.trim();

  if (!medicineName || !dose || !frequency) {
    alert("Please fill out all required fields before proceeding.");
    return;
  }

  document.getElementById("extra-fields").style.display = "table-row-group";
  document.getElementById("next-row").style.display = "none";
  document.getElementById("button-container").style.display = "block";
  document.getElementById("button-container-2").style.display = "block";


  const rowsToHide = [
    "medicine-row",
    "medicine-input-row",
    "dos-row",
    "dos-input-row",
    "frequency-row",
    "frequency-input-row"
  ];
  rowsToHide.forEach(id => {
    const row = document.getElementById(id);
    if (row) row.style.display = "none";
  });

  const freq = document.getElementById("frequency").value;
  const hoursRow = document.getElementById("hours-row");
  const timeInputsRow = document.getElementById("time-inputs-row");
  const doseTimes = [
    document.getElementById("dose-time-1"), 
    document.getElementById("dose-time-2"),
    document.getElementById("dose-time-3"),
    document.getElementById("dose-time-4")
  ];

  const dosageInputs = [
    document.getElementById("dosage1"),
    document.getElementById("dosage2"),
    document.getElementById("dosage3"),
    document.getElementById("dosage4")
  ];

  hoursRow.style.display = "none";
  timeInputsRow.style.display = "none";
  doseTimes.forEach(row => {
    if (row) row.style.display = "none";
  });
  dosageInputs.forEach(input => {
    if (input) {
      input.parentElement.style.display = "none";
      input.required = false;
      input.value = "";
    }
  });

  if (freq === "Every X hours") {
    hoursRow.style.display = "table-row";

    timeInputsRow.style.display = "table-row"; 
    if (doseTimes[0]) doseTimes[0].style.display = "table-row";

    if (dosageInputs[0]) {
      dosageInputs[0].parentElement.style.display = "table-row";
      dosageInputs[0].required = true;
    }
  } else {
    let dosesPerDay = 0;
    if (freq.includes("Once")) dosesPerDay = 1;
    else if (freq.includes("Twice")) dosesPerDay = 2;
    else if (freq.includes("Three")) dosesPerDay = 3;
    else if (freq.includes("Four")) dosesPerDay = 4;

    if (dosesPerDay > 0) {
      timeInputsRow.style.display = "table-row";
      for (let i = 0; i < dosesPerDay; i++) {
        if (doseTimes[i]) doseTimes[i].style.display = "table-row";
        if (dosageInputs[i]) {
          dosageInputs[i].parentElement.style.display = "table-row";
          dosageInputs[i].required = true;
        }
      }
    }
  }
}
</script>


<div id="successModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h2>Medicine saved successfully!</h2><br>
    <p>Do you want to add more?</p><br>
    <button type="button" onclick="window.location.href='http://localhost/pharmasync/dashboard.php';" class="btn-dashboard">No</button>
    <button onclick="window.location.href = window.location.pathname;" class="btn-dashboard">Yes</button>
    </div>
</div>



</body>
</html>
