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
$availability_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$availability = null;
$errors = [];
$success_message = "";

// Fetch the availability details
if ($availability_id > 0) {
  $stmt = $conn->prepare("SELECT id, service_id, available_date, start_time, end_time, available_slots FROM availability WHERE id = ? AND trainer_id = ?");
  $stmt->bind_param("ii", $availability_id, $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $availability = $result->fetch_assoc();
  $stmt->close();

  if (!$availability) {
    $errors[] = "Availability not found. Please check the availability ID and try again.";
  }
} else {
  $errors[] = "No availability ID provided.";
}

// Handle form submission for updating the availability
if ($_SERVER["REQUEST_METHOD"] == "POST" && $availability) {
  $service_id = intval($_POST["service_id"]);
  $available_date = htmlspecialchars(trim($_POST["available_date"]));
  $start_time = htmlspecialchars(trim($_POST["start_time"]));
  $end_time = htmlspecialchars(trim($_POST["end_time"]));
  $available_slots = intval($_POST["available_slots"]);

  if (empty($service_id) || empty($available_date) || empty($start_time) || empty($end_time) || $available_slots <= 0) {
    $errors[] = "All fields are required, and slots must be greater than 0.";
  } else {
    // Update the availability in the database
    $stmt = $conn->prepare("UPDATE availability SET service_id = ?, available_date = ?, start_time = ?, end_time = ?, available_slots = ? WHERE id = ? AND trainer_id = ?");
    $stmt->bind_param("isssiii", $service_id, $available_date, $start_time, $end_time, $available_slots, $availability_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
      $success_message = "Availability updated successfully!";
      // Refresh the availability data after update
      $availability['service_id'] = $service_id;
      $availability['available_date'] = $available_date;
      $availability['start_time'] = $start_time;
      $availability['end_time'] = $end_time;
      $availability['available_slots'] = $available_slots;
    } else {
      $errors[] = "An error occurred while updating the availability. Please try again.";
    }
    $stmt->close();
  }
}

// Fetch the services offered by the trainer for the dropdown
$services = [];
$stmt = $conn->prepare("SELECT id, description FROM tutoring_services WHERE trainer_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $services[] = $row;
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
  <title>Edit Availability</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Edit Availability</h1>

  <?php if ($success_message): ?>
    <p style="color:green;"><?php echo $success_message; ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($availability): ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $availability_id; ?>" method="post">
      <label for="service_id">Select Service:</label><br>
      <select id="service_id" name="service_id" required>
        <option value="">-- Select a Service --</option>
        <?php foreach ($services as $service): ?>
          <option value="<?php echo $service['id']; ?>" <?php echo (isset($availability['service_id']) && $availability['service_id'] == $service['id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($service['description']); ?>
          </option>
        <?php endforeach; ?>
      </select><br><br>

      <label for="available_date">Select Date:</label><br>
      <input type="date" id="available_date" name="available_date" value="<?php echo isset($availability['available_date']) ? $availability['available_date'] : ''; ?>" required><br><br>

      <label for="start_time">Start Time:</label><br>
      <input type="time" id="start_time" name="start_time" value="<?php echo isset($availability['start_time']) ? $availability['start_time'] : ''; ?>" required><br><br>

      <label for="end_time">End Time:</label><br>
      <input type="time" id="end_time" name="end_time" value="<?php echo isset($availability['end_time']) ? $availability['end_time'] : ''; ?>" required><br><br>

      <label for="available_slots">Available Slots:</label><br>
      <input type="number" id="available_slots" name="available_slots" value="<?php echo isset($availability['available_slots']) ? $availability['available_slots'] : ''; ?>" required><br><br>

      <input type="submit" name="update_availability" value="Update Availability">
    </form>
  <?php else: ?>
    <p>No availability to edit. Please ensure you selected a valid availability.</p>
  <?php endif; ?>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>

</html>