<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'trainer' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
  // If not logged in or not a trainer, redirect to the login page
  header("Location: ../login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$trainer_id = $_SESSION['user_id'];
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id === 0) {
  // Redirect to the dashboard if no student ID is provided
  header("Location: dashboard.php");
  exit();
}

// Fetch student details
$stmt = $conn->prepare("SELECT firstname, lastname, email FROM users WHERE id = ? AND role = 'student'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
  // If no student found, redirect to the dashboard
  header("Location: dashboard.php");
  exit();
}

// Fetch the student's booking history with the trainer
$bookings = [];
$stmt = $conn->prepare("
  SELECT b.id, b.booking_date, s.name AS service_name
  FROM bookings b
  JOIN services s ON b.service_id = s.id
  WHERE b.user_id = ? AND s.provider_id = ?
  ORDER BY b.booking_date DESC
");
$stmt->bind_param("ii", $student_id, $trainer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $bookings[] = $row;
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
  <title>Student Information</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Student Information: <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h1>

  <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>

  <h2>Booking History</h2>
  <ul>
    <?php if (count($bookings) > 0): ?>
      <?php foreach ($bookings as $booking): ?>
        <li>
          <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong> on <?php echo htmlspecialchars($booking['booking_date']); ?>
        </li>
      <?php endforeach; ?>
    <?php else: ?>
      <li>No bookings found for this student.</li>
    <?php endif; ?>
  </ul>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
  <br>
  <?php include '../components/footer.php'; ?>
</body>

</html>