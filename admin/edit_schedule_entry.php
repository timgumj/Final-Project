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

// Fetch the service details
$stmt = $conn->prepare("SELECT start_date, end_date FROM tutoring_services WHERE id = ?");
$stmt->bind_param("i", $tutoring_service_id);
$stmt->execute();
$stmt->bind_result($start_date, $end_date);
$stmt->fetch();
$stmt->close();

if (!$start_date || !$end_date) {
  echo "No valid start or end date found for this service.";
  exit();
}

// Fetch the existing entry details if it exists
if ($entry_id > 0) {
  $stmt = $conn->prepare("
        SELECT cd.id, cd.course_date, cd.title, cd.file_type, cd.file_path, cd.description
        FROM course_days cd
        WHERE cd.id = ?
    ");
  $stmt->bind_param("i", $entry_id);
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
  $entry = [
    'course_date' => $date,
    'title' => '',
    'file_type' => '',
    'file_path' => '',
    'description' => '',
    'id' => 0
  ];
}

// Handle delete request
if ($role === 'admin' && isset($_POST['delete_entry'])) {
  $stmt = $conn->prepare("DELETE FROM course_days WHERE id = ?");
  $stmt->bind_param("i", $entry_id);
  if ($stmt->execute()) {
    header("Location: manage_schedule.php?id=$tutoring_service_id");
    exit();
  } else {
    $errors[] = "Failed to delete the schedule entry.";
  }
}

// Handle update submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_entry'])) {
  $title = $_POST['title'];
  $material_type = $_POST['material_type'];
  $description = $_POST['description'];

  $file_path = $entry['file_path']; // Use existing file path by default

  // Handle file uploads if any
  if (!empty($_FILES['material_file']['name'])) {
    $target_dir = "../uploads/";
    $file_name = basename($_FILES['material_file']['name']);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $target_file = $target_dir . uniqid() . "." . $file_type;

    if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
      $file_path = basename($target_file);
    } else {
      $errors[] = "There was an error uploading the file.";
    }
  }

  if ($entry_id > 0) {
    // Update existing entry in course_days table
    $stmt = $conn->prepare("UPDATE course_days SET title = ?, file_type = ?, file_path = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $material_type, $file_path, $description, $entry_id);
    $stmt->execute();
  } else {
    // Insert new entry in course_days table
    $stmt = $conn->prepare("INSERT INTO course_days (tutoring_service_id, course_date, title, file_type, file_path, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $tutoring_service_id, $date, $title, $material_type, $file_path, $description);
    $stmt->execute();
  }

  $stmt->close();
  $success_message = "Schedule entry updated successfully.";
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

  // Display YouTube video if the description contains a link
  if (!empty($entry['description'])) {
    $pattern = '/(https?\:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+/';
    if (preg_match($pattern, $entry['description'], $matches)) {
      // Get the video ID from the YouTube URL
      $parts = explode('v=', $matches[0]);
      $video_id = end($parts);
      echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
    }
  }
  ?>

  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?entry_id=<?php echo $entry_id; ?>&service_id=<?php echo $tutoring_service_id; ?>&date=<?php echo $date; ?>" method="post" enctype="multipart/form-data">
    <label for="title">Title:</label><br>
    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($entry['title']); ?>" required><br><br>

    <label for="material_type">Material Type:</label><br>
    <select id="material_type" name="material_type" required>
      <option value="pdf" <?php echo ($entry['file_type'] === 'pdf') ? 'selected' : ''; ?>>PDF</option>
      <option value="video" <?php echo ($entry['file_type'] === 'video') ? 'selected' : ''; ?>>Video</option>
    </select><br><br>

    <div id="file_upload_section">
      <label for="material_file">Upload File (Leave empty to keep existing):</label><br>
      <input type="file" id="material_file" name="material_file"><br><br>
    </div>

    <label for="description">Description:</label><br>
    <textarea id="description" name="description"><?php echo htmlspecialchars($entry['description']); ?></textarea><br><br>

    <input type="submit" name="save_entry" value="Save">

    <?php if ($role === 'admin'): ?>
      <input type="submit" name="delete_entry" value="Delete" onclick="return confirm('Are you sure you want to delete this entry?');">
    <?php endif; ?>
  </form>

  <p><a href="manage_schedule.php?id=<?php echo htmlspecialchars($tutoring_service_id); ?>">Back to Full Schedule</a></p>
  <?php include '../components/footer.php'; ?>
</body>

</html>