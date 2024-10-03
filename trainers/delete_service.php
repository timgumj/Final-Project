<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'trainer' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Get the service ID from the URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$trainer_id = $_SESSION['user_id'];
$errors = [];

// Check if the service belongs to the logged-in trainer
$stmt = $conn->prepare("SELECT id FROM tutoring_services WHERE id = ? AND trainer_id = ?");
$stmt->bind_param("ii", $service_id, $trainer_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  // The service belongs to the trainer, so proceed with deletion
  $stmt->close();

  // Manually delete related records from the bookings table
  $stmt = $conn->prepare("DELETE FROM bookings WHERE service_id = ?");
  $stmt->bind_param("i", $service_id);
  $stmt->execute();
  $stmt->close();

  // Manually delete related records from the availability table
  $stmt = $conn->prepare("DELETE FROM availability WHERE service_id = ?");
  $stmt->bind_param("i", $service_id);
  $stmt->execute();
  $stmt->close();

  // Now delete the service
  $stmt = $conn->prepare("DELETE FROM tutoring_services WHERE id = ?");
  $stmt->bind_param("i", $service_id);

  if ($stmt->execute()) {
    $success_message = "Service deleted successfully.";
  } else {
    $errors[] = "An error occurred while deleting the service. Please try again.";
  }
  $stmt->close();
} else {
  $errors[] = "Service not found or you don't have permission to delete this service.";
  $stmt->close();
}

$conn->close();

// Redirect to manage services page after deletion
if (empty($errors)) {
  header("Location: manage_services.php?success=" . urlencode($success_message));
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Delete Service</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Delete Service</h1>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <p><a href="manage_services.php">Back to Manage Services</a></p>
</body>

</html>