<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$schedule = [];
$errors = [];
$success_message = "";

// Fetch the start_date and end_date for the service
$stmt = $conn->prepare("SELECT start_date, end_date FROM tutoring_services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($start_date, $end_date);
$stmt->fetch();
$stmt->close();

if (!$start_date || !$end_date) {
  echo "No valid start or end date found for this service.";
  exit();
}

// Generate all weekdays between start_date and end_date
$start_date_obj = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);
$interval = new DateInterval('P1D');
$date_range = new DatePeriod($start_date_obj, $interval, $end_date_obj->modify('+1 day'));

$weekdays = [];
foreach ($date_range as $date) {
  if (!in_array($date->format('N'), [6, 7])) { // Skip Saturday and Sunday
    $weekdays[$date->format('Y-m-d')] = [
      'date' => $date->format('Y-m-d'),
      'title' => '',
      'description' => '',
      'materials' => [],
      'entry_id' => 0,
    ];
  }
}

// Fetch existing schedule entries
$stmt = $conn->prepare("
    SELECT cd.id, cd.course_date, cd.title, cd.description, cd.file_type, cd.file_path
    FROM course_days cd
    WHERE cd.tutoring_service_id = ?
    ORDER BY cd.course_date
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

// Map the existing entries to the weekdays array
while ($row = $result->fetch_assoc()) {
  $date = $row['course_date'];
  if (isset($weekdays[$date])) {
    $weekdays[$date]['title'] = $row['title'];
    $weekdays[$date]['description'] = $row['description'];
    if ($row['file_path']) {
      $weekdays[$date]['materials'][] = [
        'type' => $row['file_type'],
        'path' => $row['file_path']
      ];
    }
    $weekdays[$date]['entry_id'] = $row['id'];
  }
}

$stmt->close();
$conn->close();
?>
<?php
include('../components/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Full Schedule</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <br>
  <h1>Manage Full Schedule for Service ID: <?php echo htmlspecialchars($service_id); ?></h1>

  <?php
  if (!empty($success_message)) {
    echo "<p style='color:green;'>$success_message</p>";
  }

  if (!empty($errors)) {
    foreach ($errors as $error) {
      echo "<p style='color:red;'>$error</p>";
    }
  }
  ?>


  <table border="1" cellpadding="10">
    <thead>
      <tr>
        <th>Date</th>
        <th>Title</th>
        <th>Description</th>
        <th>Materials</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($weekdays as $date => $entry): ?>
        <tr>
          <td><?php echo htmlspecialchars($date); ?></td>
          <td><?php echo htmlspecialchars($entry['title']); ?></td>
          <td><?php echo htmlspecialchars($entry['description']); ?></td>
          <td>
            <?php
            if (!empty($entry['materials'])) {
              foreach ($entry['materials'] as $material) {
                echo "<a href='../uploads/" . htmlspecialchars($material['path']) . "' target='_blank'>" . htmlspecialchars($material['type']) . "</a><br>";
              }
            } else {
              echo "No materials uploaded.";
            }
            ?>
          </td>
          <td>
            <a href="edit_schedule_entry.php?entry_id=<?php echo $entry['entry_id']; ?>&service_id=<?php echo $service_id; ?>&date=<?php echo $date; ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p><a href="manage_services.php">Back to Manage Services</a></p>
  <br>
  <br>
  <?php include '../components/footer.php'; ?>
</body>

</html>