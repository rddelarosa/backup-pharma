<?php
include 'php/fetch.php';
include 'php/upd_history.php'; 

$redirectToAccount = isset($_SESSION['redirect_to_account']);
unset($_SESSION['redirect_to_account']); 

$ShowChangeModal = isset($_SESSION['change_done']);

$redirectToPrescription = isset($_SESSION['redirect_to_prescription']);
unset($_SESSION['redirect_to_prescription']);

$redirectToSettings = isset($_SESSION['redirect_to_settings']);
unset($_SESSION['redirect_to_settings']); 

if (isset($_SESSION['require_password_change']) && $_SESSION['require_password_change']) {
  $_SESSION['require_password_change'] = "Change Password Required.";
  $_SESSION['require_password_change_heading'] = "You are using a temporary password. Please set a new password to continue.";
  
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - PharmaSync</title>
  <link href="style_PS.css" rel="stylesheet" type="text/css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@latest/css/boxicons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

</head>

<body <?= $darkMode ? 'class="dark-mode"' : '' ?>>

<!-- Sidebar -->
 <div class="sidebar" id="sidebar">
  <i class="burger-icon fas fa-bars" onclick="toggleFreeze()" id="burgerIcon"></i>
  
  <div class="logo">
    <img src="PS_img/PS_logo.png" alt="Logo"><span>PharmaSync</span>
  </div>

  <div class="user-icon">
    <i class="bi-person-gear"></i>
  </div>

  <div class="profile-card" onclick="showContent('account')" style="position: relative;">
    <img src="PHP/upd_picture.php?image=<?php echo urlencode($profilePicName); ?>" alt="Profile Picture" />
    <?php $isIncomplete = empty($birthday) || empty($emergencyContact) || empty($gender) || empty($accessToken); if ($isIncomplete): ?>
      <div class="blink-dot"></div>
    <?php endif; ?>

    <div class="profile-info">
      <span class="name" id="first-name"><?php echo "$firstName $lastName"; ?></span>
      <span class="email" id="email"><?php echo $username; ?></span>
    </div>
  </div>

  <ul>
    <li onclick="showContent('overview')">
      <i class="bi-kanban"></i><span>Overview</span>
    </li>

    <li onclick="showContent('prescriptions')">
      <i class="bi bi-capsule-pill"></i><span>Medicine Cabinet</span>
    </li>

    <li onclick="showContent('settings')">
      <i class="bi bi-gear"></i><span>Settings</span>
    </li>

    <li>
      <a href="PHP/logout.php">
        <i class="bi bi-arrow-right-square"></i><span>Logout</span>
      </a>
    </li>
  </ul>
 </div>

 <script>
 // Sidebar Functions
 function toggleFreeze() {
    document.getElementById('sidebar').classList.toggle('frozen');
 }

 function showContent(id) {
    document.querySelectorAll('.content-container').forEach(section => {
        section.classList.add('hidden');
    });
    document.getElementById(id).classList.remove('hidden');
 }

</script>

<!-- Main Content -->
 <div class="wrapper">

<!-- Overview -->
    <div id="overview" class="content-container hidden">
      <div class="overview-grid">

<!--Today's Task -->
 <div class="overview-card large">
    <h2><center><span id="datetime"></span></center></h2>

    <div class="med-container">
        <?php
        $hasDueMeds = false;
        $hasTodayDoses = false;
        $allTodayDoses = [];

        if (!empty($prescriptions)) {
            foreach ($prescriptions as $prescription) {
                if (!empty($prescription['today_doses'])) {
                    foreach ($prescription['today_doses'] as $dose) {
                      if ($dose['is_skip'] == 0) {

                        $hasTodayDoses = true;
                        $allTodayDoses[] = [
                            'medicine_id' => $prescription['medicine_id'],
                            'medicine_name' => $prescription['medicine_name'],
                            'dose' => $prescription['dose'],
                            'frequency' => $prescription['frequency'],
                            'stock' => $prescription['stock'],
                            'scheduled_time' => $dose['scheduled_time'],
                            'is_due' => $dose['is_due'],
                            'is_taken' => $dose['is_taken'],
                            'dose_id' => $dose['dose_id'] ?? 'unknown'
                        ];
                    }
                  }
                }
            }

            usort($allTodayDoses, function ($a, $b) {
                return strtotime($a['scheduled_time']) - strtotime($b['scheduled_time']);
            });

            foreach ($allTodayDoses as $dose) {
                $scheduledTime = strtotime($dose['scheduled_time']);
                $now = time();
                $missedByAnHour = !$dose['is_taken'] && $dose['is_due'] && ($now - $scheduledTime) > 3600;
        ?>
                <div class="med-card <?= $missedByAnHour ? 'missed-dose' : '' ?>">
                    <div class="med-info" id="card-<?= $dose['medicine_id'] ?>-<?= $dose['dose_id'] ?>">
                        <img src="PS_img/PS_logo.png" alt="Medication Image" />
                        <div class="med-card-details">
                            <h3><?= htmlspecialchars($dose['medicine_name']) ?></h3>
                            <p>
                                <?= htmlspecialchars($dose['dose']) . ' ' . htmlspecialchars($unit) ?>, <?= htmlspecialchars($dose['frequency']) ?><br>
                                Schedule: <?= htmlspecialchars($dose['scheduled_time']) ?><br>
                                Stock: <?= htmlspecialchars($dose['stock']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="actions">
                        <?php
                        if ($dose['is_due'] && !$dose['is_taken']) {
                            $hasDueMeds = true;
                            echo "<button class='taken-btn skip skip-btn' data-medicine-id='" . $dose['medicine_id'] . "' data-dose-id='" . $dose['dose_id'] . "'>Skip</button>";
                            echo "<button class='taken-btn due' data-medicine-id='" . $dose['medicine_id'] . "' data-dose-id='" . $dose['dose_id'] . "'>Taken</button>";
                        } elseif ($dose['is_taken']) {
                            echo "<button class='taken-btn taken' disabled>Taken</button>";
                        } else {
                            echo "<button class='taken-btn' disabled>' '</button>";
                        }
                        ?>
                    </div>
                </div>
        <?php
            }

            if ($hasTodayDoses && !$hasDueMeds) {
            } elseif (!$hasTodayDoses) {
                echo "<h2>Congratulations! <br> All Tasks are done.</h2>";
            }
        } else {
            echo "<h2>Sync your Medicines <a href=php/add_meds.php> now.</a></h2>";
        }
        ?>
    </div>
 </div>

 <script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".taken-btn.due").forEach(function (button) {
            button.addEventListener("click", function () {
                const medicineId = this.dataset.medicineId;
                const doseId = this.dataset.doseId;

                fetch('php/upd_taken.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'medicine_id=' + encodeURIComponent(medicineId) +
                        '&dose_id=' + encodeURIComponent(doseId) +
                        '&action=taken'
                })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === 'success') {
                            button.textContent = "Taken";
                            button.classList.remove('due');
                            button.classList.add('taken');
                            button.disabled = true;

                            const card = button.closest('.med-card');
                            if (card) card.remove();
                        } else {
                            alert("Failed to mark as taken:\n" + data);
                        }
                    })
                    .catch(error => {
                        console.error('Request failed', error);
                        alert("Request failed: " + error.message);
                    });
            });
        });

        document.querySelectorAll(".skip-btn").forEach(function (button) {
            button.addEventListener("click", function () {
                const medicineId = this.dataset.medicineId;
                const doseId = this.dataset.doseId;

                fetch('php/upd_taken.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'medicine_id=' + encodeURIComponent(medicineId) +
                        '&dose_id=' + encodeURIComponent(doseId) +
                        '&action=skip'
                })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === 'success') {
                            button.textContent = "Skipped";
                            button.classList.remove('due');
                            button.classList.add('skipped');
                            button.disabled = true;

                            const card = button.closest('.med-card');
                            if (card) card.remove();
                        } else {
                            alert("Failed to skip dose:\n" + data);
                        }
                    })
                    .catch(error => {
                        console.error('Request failed', error);
                        alert("Request failed: " + error.message);
                    });
            });
        });
    });
 </script>

 <script>
    function updateTime() {
        var now = new Date();
        var hours = now.getHours();
        var minutes = now.getMinutes();
        var seconds = now.getSeconds();
        var ampm = hours >= 12 ? 'PM' : 'AM';

        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        seconds = seconds < 10 ? '0' + seconds : seconds;

        var strTime = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
        var dateString = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        document.getElementById('datetime').innerHTML = dateString + ' <br> ' + strTime;
    }

    setInterval(updateTime, 1000); 
</script>



<!-- Adherence -->
 <div class="overview-card medium">
    <div class="adhere-container">
        <button class="adhere-button" data-period="today" onclick="updateChart('today')">Today</button>
        <button class="adhere-button" data-period="yesterday" onclick="updateChart('yesterday')">Yesterday</button>
        <button class="adhere-button" data-period="weekly" onclick="updateChart('weekly')" title="It start on Sunday">This Week</button>
        <button class="adhere-button" data-period="monthly" onclick="updateChart('monthly')">This Month</button>

        <div class="adhere-container-2">
    <!-- Pie Chart -->
    <canvas id="adherencePie"></canvas>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let pieChart;

        function createPieChart(taken, missed, skipped) {
    const ctx = document.getElementById('adherencePie').getContext('2d');

    if (pieChart) {
        pieChart.destroy();
    }

    const total = taken + missed + skipped;
    const takenPercentage = total ? ((taken / total) * 100).toFixed(2) : 0;

    const centerTextPlugin = {
        id: 'centerText',
        beforeDraw(chart) {
            const { width, height, ctx } = chart;
            ctx.save();
            ctx.font = 'bold 24px sans-serif';
            ctx.fillStyle = '#000';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(`${takenPercentage}%`, width / 2, height / 1.8);
            ctx.restore();
        }
    };

    pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Taken', 'Missed', 'Skipped'],
            datasets: [{
                data: [taken, missed, skipped],
                backgroundColor: ['#4CAF50', '#FF5722', '#9E9E9E'],
            }]
        },
        options: {
            responsive: true,
            cutout: '50%',
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 20,
                        padding: 15,
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const percentage = tooltipItem.raw ? ((tooltipItem.raw / total) * 100).toFixed(2) : 0;
                            return tooltipItem.label + ': ' + percentage + '%';
                        }
                    }
                }
            }
        },
        plugins: [centerTextPlugin] 
    });
 }

  function updateChart(period) {
    document.querySelectorAll('.adhere-button').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeButton = document.querySelector(`.adhere-button[data-period="${period}"]`);
    if (activeButton) activeButton.classList.add('active');

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1); 

    const startOfWeek = new Date(today);
    startOfWeek.setDate(today.getDate() - today.getDay()); 
    startOfWeek.setHours(0, 0, 0, 0); 

    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startOfMonth.setHours(0, 0, 0, 0);

    let startDate, endDate;
    if (period === 'today') {
        startDate = today;
        endDate = today;
    } else if (period === 'yesterday') {
        startDate = yesterday;
        endDate = yesterday;
    } else if (period === 'weekly') {
        startDate = startOfWeek;
        endDate = today;
    } else if (period === 'monthly') {
        startDate = startOfMonth;
        endDate = today;
    }

    const startFormatted = startDate.toLocaleDateString('en-CA'); 
    const endFormatted = endDate.toLocaleDateString('en-CA');


    fetch(`php/fetchAdherenceData.php?start=${startFormatted}&end=${endFormatted}`)
        .then(response => response.json())
        .then(data => {
            createPieChart(data.taken, data.missed, data.skipped);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
 }

 updateChart('today');
        
    </script>
        </div>
    </div>
</div>


    
<!-- Progress -->
 <div class="overview-card medium">
  <h2 style="text-align:center;">Progress by Week</h2>

  <div class="cal-container">
  <?php if (!empty($weeklyMedicineData)): ?>
    <div class="medicine-cards">
      <?php foreach ($weeklyMedicineData as $medicineId => $data): ?>
        <?php
          $endDate = isset($data['end_date']) ? strtotime($data['end_date']) : false;
          if ($endDate && $endDate < strtotime('today')) {
              continue; 
          }
      
        $frequency = $data['frequency'] ?? 'Once daily';
        $hours_interval = $data['hours_interval'] ?? 24;
        $hours_interval = (is_numeric($hours_interval) && $hours_interval > 0) ? $hours_interval : 24;

        $dosesPerDay = 1;
        if ($hours_interval != 24) {
          $dosesPerDay = (int)(24 / $hours_interval);
        } else {
          switch ($frequency) {
            case 'Twice daily':
              $dosesPerDay = 2;
              break;
            case 'Three times daily':
              $dosesPerDay = 3;
              break;
            case 'Four times daily':
              $dosesPerDay = 4;
              break;
            default:
              $dosesPerDay = 1;
          }
        }

        $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        ?>

        <!-- Medicine Card -->
        <div class="medicine-card" 
             onclick="openProgressModal('<?= $medicineId ?>')" 
             onmouseover="this.style.backgroundColor='#f0f0f0';" 
             onmouseout="this.style.backgroundColor='';">
          <div class="med-name-card">
            <h3><?= htmlspecialchars($data['medicine_name']) ?></h3>
            <h4><?= htmlspecialchars($data['frequency']) ?></h4>
          </div>

          <div class="cal-content">
            <?php foreach ($weekdays as $day): ?>
              <?php $isToday = (date('D') == $day); ?>
              <div class="day-column <?= $isToday ? 'highlight-column' : '' ?>">
                <div class="day-name"><?= $day ?></div>
                <?php for ($i = 0; $i < $dosesPerDay; $i++): ?>
                  <div class="dose-cell"><?= htmlspecialchars($data['days'][$day][$i] ?? '') ?></div>
                <?php endfor; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>Start adding your medicine to see progress.</p>
  <?php endif; ?>
 </div>

 </div>


 </div>
 </div>

 <!-- Modal -->
 <div id="progressModal" class="modal">
  <div class="pres-modal-content">
    <div><i class="ri-close-line x-button" onclick="closeProgressModal()"></i></div>
    <?php
    foreach ($weeklyMedicineData as $data) {
        $medicineId = (int) $data['medicine_id'];
    ?>

    <div id="modalContent_<?= $medicineId ?>" class="modal-details" style="display:none;">
      <div class="modal-page" id="page1_<?= $medicineId ?>">
        <h2><?= htmlspecialchars($data['medicine_name']) ?></h2><br>

        <table style="margin-bottom: 0;">
          <tr>
            <td><strong>Dosage:</strong></td>
            <td><?= htmlspecialchars($data['dose']). ' ' .htmlspecialchars($data['unit'])  ?></td>
            <td><strong>Frequency:</strong></td>
            <td><?= htmlspecialchars($data['frequency']) ?></td>
          </tr>
        </table>

        <table style="margin-top: 0;">
          <tr><td><strong> </strong></td></tr>

          <?php
 $prevDate = '';
 $currentTime = time(); 
 $currentDate = date('Y-m-d');  

 foreach ($weeklyData as $entry) {
    if ((int)$entry['medicine_id'] !== $medicineId) continue;

    $entryDate = date('Y-m-d', strtotime($entry['date']));  
    $scheduledTimestamp = strtotime($entry['time_taken']);  

    if (
        $entry['status'] === 'Taken' ||
        $entry['status'] === 'Skipped' ||
        $entry['status'] === 'Missed' ||
        (
            $entryDate === $currentDate &&  
            $scheduledTimestamp > $currentTime &&  
            ($scheduledTimestamp - $currentTime) <= 3600  
        )
    ) {
        $doseDate = date('l, m/d/y', strtotime($entry['date']));
        $pillCount = $entry['dosage'];
        $doseTime = date('h:i A', strtotime($entry['time_taken']));
        $status = $entry['status'];  
        $doseId = $entry['id'];

        if ($doseDate !== $prevDate) {
            echo "<tr><td><strong>$doseDate</strong></td></tr>";
            $prevDate = $doseDate;
        }

        echo "<tr><td colspan='2'>$pillCount pill(s)</td> <td>$status $doseTime</td></tr>";
    }
 }
 ?>
        </table><br><br><br>

        <script>
          function changeStatus(doseId, currentStatus) {
            let newStatus = prompt("Enter new status (taken, missed, skipped):", currentStatus);

            if (newStatus && (newStatus === "taken" || newStatus === "missed" || newStatus === "skipped")) {
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "updateStatus.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert("Status updated successfully!");
                        location.reload();  
                    } else {
                        alert("Error updating status.");
                    }
                };
                xhr.send("dose_id=" + doseId + "&new_status=" + newStatus);
            } else {
                alert("Invalid status.");
            }
          }
        </script>

     
      </div>
    </div> 
    <?php } ?>
  </div> 
 </div>


 <script>
  function openProgressModal(medicineId) {
    var details = document.querySelectorAll('.modal-details');
    details.forEach(function(detail) {
      detail.style.display = 'none';
    });

    var selectedContent = document.getElementById('modalContent_' + medicineId);
    if (selectedContent) {
      selectedContent.style.display = 'block';
    }

    showPage(1, medicineId);

    document.getElementById('progressModal').style.display = 'block';
  }

  function closeProgressModal() {
    document.getElementById('progressModal').style.display = 'none';
  }

  function showPage(pageNumber, medicineId) {
    document.getElementById('page1_' + medicineId).style.display = 'none';
    if (pageNumber === 1) {
      document.getElementById('page1_' + medicineId).style.display = 'block';
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
  const container = document.body; 

  container.querySelectorAll("*").forEach(el => {
    el.childNodes.forEach(node => {
      if (node.nodeType === 3 && node.textContent.trim() === "status_1_taken") {
        const icon = document.createElement("i");
        icon.className = "bx bxs-check-circle";
        icon.style.color = "rgb(19, 192, 105)";
        icon.style.fontSize = "31px";
        el.replaceChild(icon, node);
      }
      if (node.nodeType === 3 && node.textContent.trim() === "status_3_skipped") {
        const icon = document.createElement("i");
        icon.className = "fa fa-times-circle";
        icon.style.color = "rgb(192,192,192)";
        icon.style.fontSize = "31px";
        el.replaceChild(icon, node);
      }
      if (node.nodeType === 3 && node.textContent.trim() === "status_2_missed") {
        const icon = document.createElement("i");
        icon.className = "fa fa-question-circle";
        icon.style.color = "rgb(192,192,192)";
        icon.style.fontSize = "31px";
        el.replaceChild(icon, node);
      }

      if (node.nodeType === 3 && node.textContent.trim() === "status_4_upcoming") {
        const icon = document.createElement("i");
        icon.className = "bi bi-clock-history";
        icon.style.color = "rgba(0, 0, 0, 0.76)";
        icon.style.fontSize = "15px";
        el.replaceChild(icon, node);
      }

    });
  });
 });
</script>




    
<!-- Account Details Table -->
 <div id="account" class="content-container hidden">
  <div class="acc-container">

    <!-- Profile Picture -->
    <div id="profile-container" style="position: relative; display: inline-block; margin-top: 20px;">
      <img src="PHP/upd_picture.php?image=<?php echo urlencode($profilePicName); ?>" alt="Profile Picture" id="profile-pic" style="width: 150px; height: 150px; border-radius: 50%;"/>
      <i class="fas fa-pencil-alt" id="edit-icon" onclick="openProfilePicPopup()" ></i>
    </div>

    <!-- Picture Change Popup -->
    <div id="change-profile-pic-popup" class="popup">
      <div class="popup-content">
      <div><i class="ri-close-line x-button" onclick="closeProfilePicPopup();"></i></div>

        <form action="PHP/upd_picture.php" method="POST" enctype="multipart/form-data">
          <div class="image-gallery" style="display: flex; overflow-x: auto; gap: 10px; margin-bottom: 20px;">

            <?php 
              include 'PHP/fetch_img.php'; 
              foreach ($images as $image): ?>
                <div class="image-item" onclick="selectImage('<?php echo $image['id']; ?>', this)" 
                  data-name="<?php echo htmlspecialchars($image['image_name']); ?>" 
                  style="flex-shrink: 0; width: 150px; height: 150px; border-radius: 10px; overflow: hidden; background: #eee; cursor: pointer; transition: 0.3s;">
                  <img src="data:image/jpeg;base64,<?php echo base64_encode($image['image_data']); ?>" 
                  alt="<?php echo htmlspecialchars($image['image_name']); ?>" 
                  style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            <?php endforeach; ?>
          </div>

          <input type="hidden" name="profilePicId" id="profilePicId" value="">
          <input type="file" name="profilePicFile" id="fileInput" style="display: none;" onchange="this.form.submit();">

          <div style="text-align: center; margin-top: 10px;">
            <button type="submit" name="setProfilePic" class="btn">Set as Profile Picture</button>
          </div>
        </form>
      </div>
    </div>

 <!-- Account Info Form -->
    <form id="account-form" method="POST" action="PHP/fetch.php" style="margin-top: 30px;">
      <table>
        <tr>
          <th>Name</th>
          <td>
            <span id="first-name-display"><?php echo !empty($firstName) ? $firstName : ""; ?></span>
            <input type="text" name="first_name" id="first-name" value="<?php echo !empty($firstName) ? $firstName : ""; ?>" style="display: none;" required>
            <span id="last-name-display"><?php echo !empty($lastName) ? $lastName : ""; ?></span>
            <input type="text" name="last_name" id="last-name" value="<?php echo !empty($lastName) ? $lastName : ""; ?>" style="display: none;" required>
          </td>
        </tr>

        <tr>
          <th>Username</th>
          <td>
            <span id="username-display"><?php echo !empty($username) ? $username : ""; ?></span>
            <input type="text" name="username" id="username" value="<?php echo !empty($username) ? $username : ""; ?>" style="display: none;" required>
          </td>
        </tr>

        <tr>
          <th>Email</th>
          <td>
            <span id="email-display"><?php echo !empty($email) ? $email : ""; ?></span>
            <input type="email" name="email" id="email" value="<?php echo !empty($email) ? $email : ""; ?>" style="display: none;" required>
            <?php if (empty($accessToken)): ?>
              <a href="php/callback.php" style="color: red; text-decoration: none; font-weight: bold;">Link your email</a>
            <?php endif; ?>
          </td>
        </tr>

        <tr>
          <th>Phone Number</th>
          <td>
            <span id="phone-display"><?php echo !empty($phone) ? $phone : ""; ?></span>
            <input type="text" name="phone" id="phone" value="<?php echo !empty($phone) ? $phone : ""; ?>" style="display: none;" required>
          </td>
        </tr>
      
        <tr>
          <th>Birthday</th>
          <td>
          <span id="birthday-display" class="<?php echo empty($birthday) ? 'empty-field' : ''; ?>"><?php echo !empty($birthday) ? date('F j, Y', strtotime($birthday)) : "Please fill out"; ?></span>
          <input type="date" name="birthday" id="birthday" value="<?php echo !empty($birthday) ? $birthday : ""; ?>" style="display: none;" required>
          </td>
        </tr>
       
        <tr>
          <th>Gender</th>
          <td>
            <span id="gender-display" class="<?php echo empty($gender) ? 'empty-field' : ''; ?>"><?php echo !empty($gender) ? $gender : "Please fill out"; ?></span>
            <select name="gender" id="gender" style="display: none;" required>
              <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
              <option value="Other" <?php echo ($gender == 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </td>
        </tr>
        </table>



      <!-- Save Button-->
      <button type="submit" id="save-btn" style="display: none;" class="btn-dashboard">Save</button>
    </form>

    <!-- Edit Button -->
    <button type="button" id="edit-btn" onclick="enableEditing()" class="btn-dashboard">Edit</button>
  </div><br>

  <div class="acc-container-2">
  <div class="acc-item" onclick="window.location.href='PHP/pass_change.php'">
  <p> Change Password </p>
  </div>


  <div class="acc-item" onclick="window.location.href='PHP/delete.php'">
  <p> Delete Account </p>
  </div>
 </div>

 </div>


 <!-- Account Info JS -->
 <script>
  function enableEditing() {
    document.querySelectorAll('span[id$="-display"]').forEach(span => {
      span.style.display = 'none'; 
    });

    document.querySelectorAll('input[type="text"], input[type="date"], input[type="email"], select').forEach(input => {
      input.style.display = 'inline-block';
    });

    document.getElementById('save-btn').style.display = 'inline-block'; 
    document.getElementById('edit-btn').style.display = 'none'; 
  }

  function saveDetails() {
    alert('Account details saved!');
    document.querySelectorAll('input[type="text"], input[type="date"], input[type="email"], select').forEach(input => {
      input.style.display = 'none'; 
    });

    document.querySelectorAll('span[id$="-display"]').forEach(span => {
      span.style.display = 'inline'; 
    });

    document.getElementById('save-btn').style.display = 'none'; 
    document.getElementById('edit-btn').style.display = 'inline-block';

    document.getElementById('first-name-display').innerText = document.getElementById('first-name').value;
    document.getElementById('last-name-display').innerText = document.getElementById('last-name').value;
    document.getElementById('birthday-display').innerText = document.getElementById('birthday').value;
    document.getElementById('emergency-contact-display').innerText = document.getElementById('emergency-contact').value;
    document.getElementById('username-display').innerText = document.getElementById('username').value;
    document.getElementById('gender-display').innerText = document.getElementById('gender').value; 
    document.getElementById('email-display').innerText = document.getElementById('email').value;
    document.getElementById('phone-display').innerText = document.getElementById('phone').value;
  }
 </script>

 <script>
  const imageGallery = document.querySelector('.image-gallery');
  imageGallery.addEventListener('wheel', (e) => {
    if (e.deltaY !== 0) {
      e.preventDefault(); 
      imageGallery.scrollLeft += e.deltaY;
    }
  });

  function selectImage(imageId, element) {
    const images = document.querySelectorAll('.image-item');
    images.forEach(img => img.classList.remove('highlighted'));

    element.classList.add('highlighted');
    document.getElementById('profilePicId').value = imageId;
  }

  function openProfilePicPopup() {
    document.getElementById("change-profile-pic-popup").classList.add("show"); 
  }
  function closeProfilePicPopup() {
    document.getElementById("change-profile-pic-popup").classList.remove("show");
  }
</script>



<!-- Prescriptions Table -->
 <div id="prescriptions" class="content-container">
  <div class="pres-container">
    <div class="top-container">
      <h2><?php echo "$firstName's Cabinet"; ?></h2>
      <table id="prescriptions-table">
        <thead>
          <tr>
            <th>Medicine Name</th>
            <th>Dose</th>
            <th>Frequency</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          <?php
          date_default_timezone_set('Asia/Manila');

          if (!empty($prescriptions)) {
              foreach ($prescriptions as $prescription) {
                $medicineId = (int) $prescription['medicine_id']; 
                echo "<tr onclick='openPresModal($medicineId)' 
                onmouseover=\"this.style.backgroundColor='#f0f0f0';\" 
                onmouseout=\"this.style.backgroundColor='';\">";
          
                  echo "<td>" . htmlspecialchars($prescription['medicine_name']) . "</td>";
                  echo "<td>" . htmlspecialchars($prescription['dose']) . " " . htmlspecialchars($prescription['unit']) ."</td>";
                  echo "<td>" . htmlspecialchars($prescription['frequency']) . "</td>";
                  echo "<td>" . htmlspecialchars(date('F j, Y', strtotime($prescription['start_date']))) . "</td>";
                  echo "<td>" . htmlspecialchars(date('F j, Y', strtotime($prescription['end_date']))) . "</td>";
                  echo "<td>" . htmlspecialchars($prescription['stock']) . "</td>";
                  echo "</tr>";
              }
          } else {
              echo "<tr><td colspan='8'><center>No prescriptions found.</center></td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <button onclick="window.location.href='php/add_meds.php';" class="btn-dashboard">Add Prescription</button>
  </div><br>


 <!-- Modal -->
 <div id="prescriptionModal" class="modal">
  <div class="pres-modal-content">
    <div><i class="ri-close-line x-button" onclick="closePresModal()"></i></div>

    <?php
    if (!empty($prescriptions)) {
        foreach ($prescriptions as $prescription) {
            $id = (int) $prescription['medicine_id'];
          
            $sqlTimes = "SELECT time1, time2, time3, time4 FROM current_medicines_times WHERE medicine_id = ?";
            $stmtTimes = $conn->prepare($sqlTimes);
            $stmtTimes->bind_param("i", $id);
            $stmtTimes->execute();
            $stmtTimes->bind_result($time1, $time2, $time3, $time4);
            $stmtTimes->fetch();
            $stmtTimes->close();
    
            echo "<div id='prescription-$id' class='prescription-detail' style='display:none;'>";
            
            echo "<form method='POST' id='presForm' action='php/upd_prescription.php'>";
            
            echo "
            <tr>
                <td>
                    <h2>
                        <!-- View Mode (Text) -->
                        <span class='view-text' style='display:block;'>" . htmlspecialchars($prescription['medicine_name']) . "</span>
            
                        <!-- Edit Mode (Label and Input) -->
                        <label class='edit-label' style='display:none;'>Medicine Name: </label>
                        <input class='edit-input' type='text' name='medicine_name' value='" . htmlspecialchars($prescription['medicine_name']) . "' style='display:none;'>
                    </h2>
                </td>
            </tr>";
            

                    

            echo "<input type='hidden' name='medicine_id' value='$id'>";
            echo "<table class='modal-table'>";
            echo "<tr>
                    <th>Dose</th>
                    <td>
                      <span class='view-text'>" . htmlspecialchars($prescription['dose']) . " " . htmlspecialchars($prescription['unit']) . "</span>
                      <input class='edit-input' type='text' name='dose' value='" . htmlspecialchars($prescription['dose']) . "' style='display:none;'>
                    </td>
                    
                    <th>Stock</th>
                    <td>
                      <span class='view-text'>" . htmlspecialchars($prescription['stock']) . " remaining" . "</span>
                      <input class='edit-input' type='number' name='stock' value='" . htmlspecialchars($prescription['stock']) . "' style='display:none;' min='0'>
                    </td>
                  </tr>";

            echo "<tr>
                    <th>Remind me when to refill </th>
                    <td style='margin: 0;'>
                      <div class='switch-row' style='margin-top: 15px; width: 61px;'>
                        <label class='switch'>
                        <input type='checkbox' id='stock_notif' name='stock_notif' value='1'
                        " . ((isset($prescription['stock_notif']) && $prescription['stock_notif']) ? 'checked' : '') . "
                        class='stock_notif_input' disabled>
                        <span class='slider'></span>
                        </label>
                      </div>
                    </td>

                  <th>Remind me at</th>
                  <td>
                    <span class='view-text'>" . htmlspecialchars($prescription['stock_remind_value']) . "</span>
                    <input class='edit-input' type='text' name='stock_remind_value' value='" . htmlspecialchars($prescription['stock_remind_value']) . "' style='display:none;'>
                  </td>
                  </tr>";
                  
            echo "<tr>
                    <th>Start Date</th>
                    <td>
                      <span class='view-text'>" . htmlspecialchars(date('F j, Y', strtotime($prescription['start_date']))) . "</span>
                      <input class='edit-input' type='date' name='start_date' value='" . $prescription['start_date'] . "' style='display:none;'>
                    </td>

                    <th>End Date</th>
                    <td>
                      <span class='view-text'>" . htmlspecialchars(date('F j, Y', strtotime($prescription['end_date']))) . "</span>
                      <input class='edit-input' type='date' name='end_date' value='" . $prescription['end_date'] . "' style='display:none;'>
                    </td>
                  </tr>";

            echo "<tr>
                  <th>Frequency</th>
                  <td>
                      <span class='view-text'>" . htmlspecialchars($prescription['frequency']) . "</span>
                      
 <select class='edit-input frequency' name='frequency' style='display:none;'>
                          <option value='Once daily'" . ($prescription['frequency'] == 'Once daily' ? ' selected' : '') . ">Once daily</option>
                          <option value='Twice daily'" . ($prescription['frequency'] == 'Twice daily' ? ' selected' : '') . ">Twice daily</option>
                          <option value='Three times daily'" . ($prescription['frequency'] == 'Three times daily' ? ' selected' : '') . ">Three times daily</option>
                          <option value='Four times daily'" . ($prescription['frequency'] == 'Four times daily' ? ' selected' : '') . ">Four times daily</option>
                          <option value='Before meals'" . ($prescription['frequency'] == 'Before meals' ? ' selected' : '') . ">Before meals</option>
                          <option value='After meals'" . ($prescription['frequency'] == 'After meals' ? ' selected' : '') . ">After meals</option>
                          <option value='At bedtime'" . ($prescription['frequency'] == 'At bedtime' ? ' selected' : '') . ">At bedtime</option>
                          <option value='Every X hours'" . ($prescription['frequency'] == 'Every X hours' ? ' selected' : '') . ">Every X hours</option>
                      </select>
                  </td>
                  </tr>";
                                            
                
              echo "<tr>
              <th>Scheduled Times</th>
              <td colspan='3'>";
              
              $times = [];
              if (!empty($time1)) $times[] = date("g:i A", strtotime($time1));
              if (!empty($time2)) $times[] = date("g:i A", strtotime($time2));
              if (!empty($time3)) $times[] = date("g:i A", strtotime($time3));
              if (!empty($time4)) $times[] = date("g:i A", strtotime($time4));
              
              echo "<span class='view-text'>" . (count($times) > 0 ? implode(" • ", $times) : "No times set") . "</span>";
              
              echo "<div class='edit-input' style='display: none;'>
                <input type='time' id='time1' class='time-input' name='time1' value='" . (!empty($time1) ? date("H:i", strtotime($time1)) : '') . "'>
                <input type='time' id='time2' class='time-input' name='time2' value='" . (!empty($time2) ? date("H:i", strtotime($time2)) : '') . "'>
                <input type='time' id='time3' class='time-input' name='time3' value='" . (!empty($time3) ? date("H:i", strtotime($time3)) : '') . "'>
                <input type='time' id='time4' class='time-input' name='time4' value='" . (!empty($time4) ? date("H:i", strtotime($time4)) : '') . "'>
              </div>";
              
              echo "</td></tr>";
                        

            echo "</table><br>";

            echo "<button type='button' class='button edit-btn' onclick='enableEdit(this)'>Edit</button> ";
            echo "<button type='button' style='display: none;' class='button delete' onclick='deletePrescription($id)'>Delete</button>";
            echo "<button type='submit' class='button save-btn' style='display:none;'>Save</button>";
            echo "</form>";
            echo "</div>";
        }
    }
    ?>
  </div>
  </div>

  <script>
 document.addEventListener("DOMContentLoaded", function () {
  function applyFrequencyHandler(block) {
    const frequencySelect = block.querySelector(".frequency");
    const timeInputs = block.querySelectorAll(".time-input");

    function updateTimeInputs() {
      let timesToShow = 0;
      switch (frequencySelect.value) {
        case 'Once daily': timesToShow = 1; break;
        case 'Twice daily': timesToShow = 2; break;
        case 'Three times daily': timesToShow = 3; break;
        case 'Four times daily': timesToShow = 4; break;
        default: timesToShow = 0;
      }

      timeInputs.forEach((input, index) => {
        input.style.display = index < timesToShow ? '' : 'none';
      });
    }

    frequencySelect.addEventListener("change", updateTimeInputs);
    updateTimeInputs(); 
  }

  document.querySelectorAll(".prescription-detail").forEach(applyFrequencyHandler);

  const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        if (node.nodeType === 1 && node.classList.contains("medicine-block")) {
          applyFrequencyHandler(node);
        }
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
 });

 function deletePrescription(medicineId) {
    if (!confirm("Are you sure you want to delete this prescription?")) return;

    fetch('php/upd_pres_del.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `medicine_id=${medicineId}`
    })
    .then(res => {
        if (!res.ok) throw new Error('Delete failed.');
        
        alert("Prescription deleted successfully.");
        document.getElementById(`prescription-${medicineId}`)?.remove();
        closePresModal();
        window.location.reload(); // 🔁 Refresh the page

    })
    .catch(err => {
        console.error(err);
        alert("An error occurred while deleting the prescription.");
    });
 }

 function openPresModal(medicineId) {
  const modal = document.getElementById("prescriptionModal");
  modal.style.display = "block";

  document.querySelectorAll(".prescription-detail").forEach(el => {
    el.style.display = "none";
  });

  const selected = document.getElementById("prescription-" + medicineId);
  if (selected) selected.style.display = "block";
  }

 function closePresModal() {
    const modal = document.getElementById("prescriptionModal");
    modal.style.display = "none";

    const form = modal.querySelector('form');
    if (!form) return;

    toggleEditMode(form, false);
 }

 function enableEdit(button) {
    const form = button.closest('form');
    toggleEditMode(form, true);
    button.style.display = 'none';
 }

 function toggleEditMode(form, isEditing) {
    form.querySelectorAll('.view-text').forEach(el => el.style.display = isEditing ? 'none' : 'inline');
    form.querySelectorAll('.edit-input').forEach(el => {
        el.style.display = isEditing ? 'block' : 'none';
        el.disabled = !isEditing;
    });
    form.querySelectorAll('.edit-label').forEach(el => el.style.display = isEditing ? 'inline-block' : 'none');

    const saveBtn = form.querySelector('.save-btn');
    const deleteBtn = form.querySelector('.delete');
    const editBtn = form.querySelector('.edit-btn');
    const checkbox = form.querySelector('.stock_notif_input');

    if (saveBtn) saveBtn.style.display = isEditing ? 'inline-block' : 'none';
    if (deleteBtn) deleteBtn.style.display = isEditing ? 'inline-block' : 'none';
    if (editBtn) editBtn.style.display = isEditing ? 'none' : 'inline-block';
    if (checkbox) checkbox.disabled = !isEditing;
 }

 window.onclick = function(event) {
    const modal = document.getElementById("prescriptionModal");
    if (event.target === modal) closePresModal();
 };


</script>




<!-- History -->

<div class="acc-container-2">
  <div class="acc-item" onclick="openHistoryModal()">
    <p>History</p>
  </div>
 </div>

<!-- History Modal -->
<div id="historyModal" class="modal">
  <div class="hist-modal-content">
    <div class="hist-content-container">
      <div><i class="ri-close-line x-button" onclick="openHistoryModal()"></i></div>
      <h2>History</h2>

      <div class="filter-section">
        <table>
          <tr>
            <th>From:</th> 
            <td><input type="date" id="historyStartDate"></td>
            <th>To:</th> 
            <td><input type="date" id="historyEndDate"></td>
            <th>Search:</th> 
            <td><input type="text" id="historySearch" placeholder="Medicine Name..."></td>
          </tr>
        </table>
      </div>

      <div class="hist-body">
        <table id="historyTable">
          <thead>
            <tr>
              <th>Medicine Name</th>
              <th>Dose</th>
              <th>Frequency</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Doses History</th> 
            </tr>
          </thead>
          <tbody id="historyTableBody">
          </tbody>
        </table>
      </div>
    </div>
    <div class="hist-item">
      <button class="button" onclick="filterHistory()">Apply</button>
      <button class="button" onclick="exportHistoryToPDF()">Export PDF</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script>
const historyData = <?php echo json_encode($medicines);?>;

function openHistoryModal() {
  const modal = document.getElementById('historyModal');
  if (modal.style.display === 'block') {
    modal.style.display = 'none'; 
  } else {
    modal.style.display = 'block'; 
    displayHistory(historyData);
  }
}

function closeHistoryModal() {
  document.getElementById('historyModal').style.display = 'none';
}

function displayHistory(data) {
  const tbody = document.getElementById('historyTableBody');
  tbody.innerHTML = ''; 
  data.forEach(p => {
    let doseHistoryRows = '';
    p.doses.forEach(dose => {
      let status = 'N/A'; 
      if (dose.is_taken) {
        status = 'Taken';
      } else if (dose.is_skipped) {
        status = 'Skipped';
      } else if (dose.is_missed) {
        status = 'Missed';
      }

      doseHistoryRows += `<tr>
        <td>${dose.time}</td>
        <td>${status}</td>
      </tr>`;
    });

    const row = `<tr>
      <td>${p.medicine_name}</td>
      <td>${p.dose}</td>
      <td>${p.frequency}</td>
      <td>${p.start_date}</td>
      <td>${p.end_date}</td>
      <td>
        <!-- Nested table for doses history -->
        <table class="nested-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${doseHistoryRows}
          </tbody>
        </table>
      </td>
    </tr>`;
    tbody.insertAdjacentHTML('beforeend', row);
  });
}

function filterHistory() {
  const search = document.getElementById('historySearch').value.toLowerCase();
  const start = new Date(document.getElementById('historyStartDate').value);
  const end = new Date(document.getElementById('historyEndDate').value);

  const filtered = historyData.filter(p => {
    const nameMatch = p.medicine_name.toLowerCase().includes(search);
    const startDate = new Date(p.start_date);
    const inRange = (!isNaN(start) && !isNaN(end)) ? (startDate >= start && startDate <= end) : true;
    return nameMatch && inRange;
  });

  displayHistory(filtered);
}

const firstName = "<?php echo htmlspecialchars($firstName); ?>";
const lastName = "<?php echo htmlspecialchars($lastName); ?>";
const fullName = `${firstName} ${lastName}`;
const userName = "<?php echo htmlspecialchars($username); ?>";
const email = "<?php echo htmlspecialchars($email); ?>";


const { jsPDF } = window.jspdf;
require('jspdf-autotable');

function exportHistoryToPDF() {
  const doc = new jsPDF();

  const patientName = fullName || "N/A";
  const patientUsername = userName || "N/A";
  const patientEmail = email || "N/A";
  const dateGenerated = new Date().toLocaleDateString();

  doc.setFontSize(14);
  doc.text("Prescription History", 75, 20);
  doc.setFontSize(11);
  doc.text(`Name: ${patientName}`, 14, 30);
  doc.text(`Username: ${patientUsername}`, 14, 36);
  doc.text(`Email: ${patientEmail}`, 14, 42);
  doc.text(`Date Generated: ${dateGenerated}`, 14, 48);

  const tableBody = [];

  document.querySelectorAll("#historyTable tbody tr").forEach((row) => {
    const cells = row.querySelectorAll("td");

    if (cells.length < 6 || cells[0].innerText.trim() === "") return;

    const rowData = [];
    for (let i = 0; i < 5; i++) {
      rowData.push(cells[i].innerText.trim());
    }

    const dosesCell = cells[5];
    let doseText = "";
    const innerRows = dosesCell.querySelectorAll("tr");

    innerRows.forEach((tr) => {
      const tds = tr.querySelectorAll("td");
      if (tds.length === 2) {
        const time = tds[0].innerText.trim();
        const status = tds[1].innerText.trim();
        doseText += `${time} - ${status}\n`;
      }
    });

    rowData.push(doseText.trim());
    tableBody.push(rowData);
  });

  doc.autoTable({
    startY: 55,
    head: [['Medicine Name', 'Dose', 'Frequency', 'Start Date', 'End Date', 'Doses History']],
    body: tableBody,
    styles: { fontSize: 9, cellPadding: 3 },
    columnStyles: {
      5: { cellWidth: 60 },
    },
    theme: 'grid'
  });

  doc.save('Prescription_History.pdf');
}


</script>





<!-- Settings -->
    <div id="settings" class="content-container hidden">
    <div class="set-container">
    <h2>Preferences</h2>

    <form action="php/upd_pref.php" method="POST">
    <table style="border: none;">
    <tr>
 </tr>
 <tr>
  <td>
    <div class="switch-row">
      <label for="notify_email">Reminder via Email</label>
      <label class="switch">
        <input type="checkbox" id="notify_email" name="notify_email" value="1" <?= $emailNotif ? 'checked' : '' ?>>
        <span class="slider"></span>
      </label>
    </div>

    <div class="switch-row" style="margin-top: 15px;">
      <label for="recurring_reminder">Recurring Reminders</label>
      <label class="switch">
      <input type="checkbox" id="recurring_reminder" name="remindPre" value="1" <?= isset($remindPre) && $remindPre == 1 ? 'checked' : '' ?>>
      <span class="slider"></span>
      </label>
    </div>

    <div id="reminder_interval_section" style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;   font-size: 16px;">
      <label for="reminder_interval" style="margin: 0;">Remind every</label>
      <select name="reminder_interval" id="reminder_interval" style="width: 130px;" <?= ($remindPre && $emailNotif) ? '' : 'disabled style="background-color: #e0e0e0; cursor: not-allowed;"' ?>>
        <option value="5" <?= ($remindVal == 5) ? 'selected' : '' ?>>5 mins</option>
        <option value="10" <?= ($remindVal == 10) ? 'selected' : '' ?>>10 mins</option>
        <option value="15" <?= ($remindVal == 15) ? 'selected' : '' ?>>15 mins</option>
        <option value="20" <?= ($remindVal == 20) ? 'selected' : '' ?>>20 mins</option>
        <option value="25" <?= ($remindVal == 25) ? 'selected' : '' ?>>25 mins</option>
      </select>
    </div>

    <div class="switch-row" style="margin-top: 15px;">
      <label for="dark_mode">Theme</label>
      <label class="switch">
        <input type="checkbox" id="dark_mode" name="dark_mode" value="on" <?= $darkMode ? 'checked' : '' ?>>
        <span class="slider"></span>
      </label>
    </div>

    <center>
      <button type="submit" class="btn-dashboard">Save Preferences</button>
    </center>
  </td>
 </tr>

    </table>
  </form>
 </div>
 </div>

 <script>
  function toggleDarkMode() {
    const isChecked = document.getElementById("dark_mode").checked;
    if (isChecked) {
      document.body.classList.add("dark-mode");
    } else {
      document.body.classList.remove("dark-mode");
    }
    localStorage.setItem("darkMode", isChecked); 
  }

  window.onload = function () {
    const darkModeEnabled = localStorage.getItem("darkMode") === "true";
    const darkModeCheckbox = document.getElementById("dark_mode");
    if (darkModeCheckbox && darkModeEnabled) {
      darkModeCheckbox.checked = true;
      document.body.classList.add("dark-mode");
    }
  };

  document.getElementById("dark_mode").addEventListener("change", toggleDarkMode);

  function updateReminderUI() {
    const emailNotif = document.getElementById("notify_email");
    const recurringReminder = document.getElementById("recurring_reminder");
    const reminderInterval = document.getElementById("reminder_interval");

    if (!emailNotif.checked) {
      recurringReminder.disabled = true;
      recurringReminder.closest('.switch').style.opacity = "0.5";
      recurringReminder.checked = false; 

      reminderInterval.disabled = true;
      reminderInterval.style.backgroundColor = "#e0e0e0";
      reminderInterval.style.cursor = "not-allowed";
    } else {
      recurringReminder.disabled = false;
      recurringReminder.closest('.switch').style.opacity = "1";

      if (recurringReminder.checked) {
        reminderInterval.disabled = false;
        reminderInterval.style.backgroundColor = "";
        reminderInterval.style.cursor = "";
      } else {
        reminderInterval.disabled = true;
        reminderInterval.style.backgroundColor = "#e0e0e0";
        reminderInterval.style.cursor = "not-allowed";
      }
    }
  }

  window.addEventListener("DOMContentLoaded", updateReminderUI);

  document.getElementById("notify_email").addEventListener("change", updateReminderUI);
  document.getElementById("recurring_reminder").addEventListener("change", updateReminderUI);
</script>


<script>
 document.addEventListener('DOMContentLoaded', function () {
    <?php if ($redirectToAccount): ?>
        document.querySelectorAll('.content-container').forEach(el => el.classList.add('hidden'));
        document.getElementById('account').classList.remove('hidden');
 
    <?php elseif ($redirectToPrescription): ?>
        document.querySelectorAll('.content-container').forEach(el => el.classList.add('hidden'));
        document.getElementById('prescriptions').classList.remove('hidden');

        <?php elseif ($redirectToSettings): ?>
        document.querySelectorAll('.content-container').forEach(el => el.classList.add('hidden'));
        document.getElementById('settings').classList.remove('hidden');

    <?php else: ?>
        var urlParams = new URLSearchParams(window.location.search);
        var show = urlParams.get('show') || 'overview';
        showContent(show);
    <?php endif; ?>
 });

 function showContent(id) {
    document.querySelectorAll('.content-container').forEach(el => el.classList.add('hidden'));
    const section = document.getElementById(id);
    if (section) section.classList.remove('hidden');
 }
</script>


<!-- Success & Error Modals -->
<div id="errorModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h2 id="modalHeading"></h2><br>
        <p id="errorMessage"></p><br>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['require_password_change'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['require_password_change']; ?>', '<?php echo $_SESSION['require_password_change_heading']; ?>', 'php/pass_change.php');
            
        <?php elseif (isset($_SESSION['change_done'])): ?>
            showModal('errorModal', '<?php echo $_SESSION['change_done']; ?>', '<?php echo $_SESSION['change_done_heading']; ?>');
            <?php unset($_SESSION['change_done']); ?>
      
        
        <?php endif; ?>        
    });

    function showModal(modalId, message, heading, redirectUrl) {
    const modal = document.getElementById(modalId);
    const messageElement = modal.querySelector('#errorMessage') || modal.querySelector('#successMessage');
    const headingElement = modal.querySelector('#modalHeading') || modal.querySelector('#modalHeadingSuccess');

    if (messageElement) messageElement.textContent = message;
    if (headingElement) headingElement.textContent = heading;

    modal.style.display = 'block';
    if (redirectUrl) {
        modal.onclick = () => {
            window.location.href = redirectUrl;
        };

        setTimeout(() => {
            modal.style.display = 'none';
            window.location.href = redirectUrl;
        }, 3000);
    } else {
        setTimeout(() => {
            modal.style.display = 'none';
        }, 3000);
    }
}

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
</script>


</div>
</body>
</html>
