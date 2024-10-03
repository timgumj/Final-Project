<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'trainer' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$user_id = $_SESSION['user_id'];
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$schedule = [];
$errors = [];
$success_message = "";

// Verify that the trainer is assigned to the service
$stmt = $conn->prepare("SELECT start_date, end_date FROM tutoring_services WHERE id = ? AND trainer_id = ?");
$stmt->bind_param("ii", $service_id, $user_id);
$stmt->execute();
$stmt->bind_result($start_date, $end_date);
$stmt->fetch();
$stmt->close();

if (!$start_date || !$end_date) {
  echo "No valid start or end date found for this service or you do not have permission to manage this service.";
  exit();
}

// Fetch existing schedule entries from the course_days table
$stmt = $conn->prepare("
    SELECT cd.id, cd.course_date, cd.title, cd.file_type, cd.file_path
    FROM course_days cd
    WHERE cd.tutoring_service_id = ?
    ORDER BY cd.course_date
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

$schedule = []; // To hold schedule entries
while ($row = $result->fetch_assoc()) {
  $schedule[] = $row; // Store each row
}

$stmt->close();
$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Full Schedule</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      margin: 0;
      padding: 0;
    }

    h1 {
      text-align: center;
      font-size: 1.8em;
      font-weight: 400;
      margin-bottom: 20px;
    }

    .schedule-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
      gap: 15px;
      max-width: 100%;
      margin: 10px 2px;
      padding: 0;
    }

    .schedule-card {
      border: 1px solid #ccc;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
      background-color: #fff;
    }

    .schedule-card p {
      margin: 5px 0;
      font-size: 0.9em;
      /* Smaller font size for other details */
    }

    .schedule-card .title {
      font-size: 1.5em;
      /* Larger font size for title */
      font-weight: bold;
      margin-bottom: 10px;
    }

    .schedule-card .date-time {
      font-size: 1em;
      /* Normal font size for date and time */
      font-weight: normal;
      margin-bottom: 10px;
    }

    .schedule-card a {
      color: black;
      text-decoration: none;
      border-bottom: 1px solid red;
    }

    .schedule-card a:hover {
      color: red;
      border-bottom: 1px solid red;
    }
  </style>
</head>

<body>
  <h1>Manage Full Schedule for Service ID: <?php echo htmlspecialchars($service_id); ?></h1>

  <div class="schedule-container">
    <?php if (!empty($schedule)): ?>
      <?php foreach ($schedule as $entry): ?>
        <div class="schedule-card">
          <p class="title"><?php echo htmlspecialchars($entry['title']); ?></p>
          <?php
          $date = new DateTime($entry['course_date']);
          $formatted_date = $date->format('d-m-Y'); // Day-Month-Year format
          $formatted_time = $date->format('H:i'); // Hour:Minute format
          ?>
          <p class="date-time"><strong>Date:</strong> <?php echo htmlspecialchars($formatted_date); ?></p>
          <p class="date-time"><strong>Time:</strong> <?php echo htmlspecialchars($formatted_time); ?></p>
          <p><strong>Materials:</strong><br>
            <?php if (!empty($entry['file_path'])): ?>
              <a href="../uploads/<?php echo htmlspecialchars($entry['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($entry['file_type']); ?></a><br>
            <?php else: ?>
              No materials uploaded.
            <?php endif; ?>
          </p>
          <p>
            <a href="edit_schedule_entry.php?entry_id=<?php echo $entry['id']; ?>&service_id=<?php echo $service_id; ?>&date=<?php echo htmlspecialchars($entry['course_date']); ?>">Edit</a> |
            <a href="details.php?day_id=<?php echo $entry['id']; ?>">Details</a>
          </p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No schedule entries found for this service.</p>
    <?php endif; ?>
  </div>

  <p><a href="manage_services.php">Back to Manage Services</a></p>

  <?php include '../components/footer.php'; ?>
</body>

</html>