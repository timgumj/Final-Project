<?php
session_start();

// Check if the user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'trainer', 'student'])) {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$course_day_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$errors = [];
$course_day = [];

// Fetch the course day details
$stmt = $conn->prepare("
    SELECT cd.id, cd.course_date, cd.title, cd.description, cd.file_type, cd.file_path, ts.trainer_id
    FROM course_days cd
    JOIN tutoring_services ts ON cd.tutoring_service_id = ts.id
    WHERE cd.id = ?
");
$stmt->bind_param("i", $course_day_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $course_day = $result->fetch_assoc();
} else {
  echo "No valid course day found with this ID.";
  exit();
}
$stmt->close();

// Additional check for student access based on booking confirmation
if ($role === 'student') {
  $stmt = $conn->prepare("
        SELECT b.status
        FROM bookings b
        JOIN tutoring_services ts ON b.service_id = ts.id
        WHERE b.student_id = ? AND ts.trainer_id = ? AND b.status = 'confirmed'
    ");
  $stmt->bind_param("ii", $user_id, $course_day['trainer_id']);
  $stmt->execute();
  $stmt->bind_result($booking_status);
  $stmt->fetch();
  $stmt->close();

  if ($booking_status !== 'confirmed') {
    echo "You do not have permission to view this course detail.";
    exit();
  }
}

// Handle delete request (admin only)
if ($role === 'admin' && isset($_POST['delete_course_day'])) {
  $stmt = $conn->prepare("DELETE FROM course_days WHERE id = ?");
  $stmt->bind_param("i", $course_day_id);
  if ($stmt->execute()) {
    header("Location: manage_schedule.php?service_id=" . $course_day['tutoring_service_id']);
    exit();
  } else {
    $errors[] = "Failed to delete the course day entry.";
  }
  $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Course Day Detail</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Course Day Detail for Date: <?php echo htmlspecialchars($course_day['course_date']); ?></h1>

  <?php
  if (!empty($errors)) {
    foreach ($errors as $error) {
      echo "<p style='color:red;'>$error</p>";
    }
  }
  ?>

  <h2>Title: <?php echo htmlspecialchars($course_day['title']); ?></h2>
  <p>Description: <?php echo nl2br(htmlspecialchars($course_day['description'])); ?></p>

  <?php if ($course_day['file_path']): ?>
    <h3>Material:</h3>
    <?php if ($course_day['file_type'] === 'video'): ?>
      <video controls>
        <source src="../uploads/<?php echo htmlspecialchars($course_day['file_path']); ?>" type="video/mp4">
        Your browser does not support the video tag.
      </video>
    <?php elseif ($course_day['file_type'] === 'pdf'): ?>
      <a href="../uploads/<?php echo htmlspecialchars($course_day['file_path']); ?>" target="_blank">View PDF</a>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($role === 'admin'): ?>
    <form method="post">
      <input type="submit" name="delete_course_day" value="Delete Course Day" onclick="return confirm('Are you sure you want to delete this course day?');">
    </form>
  <?php endif; ?>

  <p><a href="manage_schedule.php?service_id=<?php echo htmlspecialchars($course_day['tutoring_service_id']); ?>">Back to Schedule</a></p>
</body>

</html>