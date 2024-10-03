<?php
session_start();

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'trainer'])) {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$entry_id = isset($_GET['entry_id']) ? intval($_GET['entry_id']) : 0;
$tutoring_service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$entry = [];
$errors = [];
$success_message = "";

// Fetch the service details and verify trainer ownership
$stmt = $conn->prepare("SELECT start_date, end_date FROM tutoring_services WHERE id = ? AND (trainer_id = ? OR ? = 'admin')");
$stmt->bind_param("iis", $tutoring_service_id, $user_id, $role);
$stmt->execute();
$stmt->bind_result($start_date, $end_date);
$stmt->fetch();
$stmt->close();

if (!$start_date || !$end_date) {
  echo "No valid start or end date found for this service, or you do not have permission to edit this entry.";
  exit();
}

// Fetch the existing entry details if it exists
if ($entry_id > 0) {
  $stmt = $conn->prepare("
        SELECT id, course_date, title, file_type, file_path, description
        FROM course_days
        WHERE id = ? AND tutoring_service_id = ? AND course_date = ?
    ");
  $stmt->bind_param("iis", $entry_id, $tutoring_service_id, $date);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    $entry = $row;
  } else {
    echo "No entry found with this ID.";
    exit();
  }
  $stmt->close();
} else {
  echo "Invalid request. Entry ID is missing.";
  exit();
}

// Handle update submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_entry'])) {
  $title = $_POST['title'];
  $material_type = $_POST['material_type'];
  $description = $_POST['description'];
  $time = $_POST['time']; // Only the time part

  // Combine the fixed date with the new time
  $course_date = (new DateTime($entry['course_date']))->format('Y-m-d') . ' ' . $time;

  // Handle multiple file uploads
  $uploaded_files = [];
  if (!empty($_FILES['material_files']['name'][0])) {
    $target_dir = "../uploads/";
    foreach ($_FILES['material_files']['name'] as $key => $file_name) {
      $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
      $target_file = $target_dir . uniqid() . "." . $file_type;

      if (move_uploaded_file($_FILES['material_files']['tmp_name'][$key], $target_file)) {
        $uploaded_files[] = [
          'type' => $material_type,
          'path' => basename($target_file)
        ];
      } else {
        $errors[] = "There was an error uploading the file: " . htmlspecialchars($file_name);
      }
    }
  }

  // If there are uploaded files, update the database
  if (!empty($uploaded_files)) {
    foreach ($uploaded_files as $file) {
      $stmt = $conn->prepare("INSERT INTO course_days (tutoring_service_id, course_date, title, file_type, file_path, description) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("isssss", $tutoring_service_id, $course_date, $title, $file['type'], $file['path'], $description);
      $stmt->execute();
    }
    $success_message = "Schedule entry updated successfully with new files.";
  } else {
    // Update the existing entry in the course_days table if no new files
    $stmt = $conn->prepare("UPDATE course_days SET course_date = ?, title = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sssi", $course_date, $title, $description, $entry_id);
    if ($stmt->execute()) {
      $success_message = "Schedule entry updated successfully.";
    } else {
      $errors[] = "Failed to update the schedule entry.";
    }
  }

  $stmt->close();
}

$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Schedule Entry</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    #description {
      width: 100%;
      height: 200px;
      font-size: 14px;
      padding: 10px;
      margin-bottom: 20px;
    }

    .description-title {
      font-size: 18px;
      font-weight: bold;
    }

    .description-text {
      font-size: 14px;
    }
  </style>
</head>

<body>
  <h1>Edit Schedule Entry for Date: <?php echo htmlspecialchars($entry['course_date']); ?></h1>

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

  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?entry_id=<?php echo $entry_id; ?>&service_id=<?php echo $tutoring_service_id; ?>&date=<?php echo $date; ?>" method="post" enctype="multipart/form-data">
    <label for="course_date">Date:</label><br>
    <input type="text" id="course_date" name="course_date" value="<?php echo htmlspecialchars((new DateTime($entry['course_date']))->format('Y-m-d')); ?>" readonly><br><br>

    <label for="time">Time:</label><br>
    <input type="time" id="time" name="time" value="<?php echo htmlspecialchars((new DateTime($entry['course_date']))->format('H:i')); ?>" required><br><br>

    <label for="title">Title:</label><br>
    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($entry['title']); ?>" required><br><br>

    <label for="material_type">Material Type:</label><br>
    <select id="material_type" name="material_type" required>
      <option value="pdf" <?php echo ($entry['file_type'] === 'pdf') ? 'selected' : ''; ?>>PDF</option>
      <option value="video" <?php echo ($entry['file_type'] === 'video') ? 'selected' : ''; ?>>Video</option>
    </select><br><br>

    <div id="file_upload_section">
      <label for="material_files">Upload Files (You can select multiple):</label><br>
      <input type="file" id="material_files" name="material_files[]" multiple><br><br>
    </div>

    <label for="description">Description:</label><br>
    <textarea id="description" name="description"><?php echo htmlspecialchars($entry['description']); ?></textarea><br><br>

    <input type="submit" name="save_entry" value="Save">

    <?php if ($role === 'admin'): ?>
      <input type="submit" name="delete_entry" value="Delete" onclick="return confirm('Are you sure you want to delete this entry?');">
    <?php endif; ?>
  </form>

  <p><a href="manage_schedule.php?id=<?php echo htmlspecialchars($tutoring_service_id); ?>">Back to Full Schedule</a></p>
</body>

</html>