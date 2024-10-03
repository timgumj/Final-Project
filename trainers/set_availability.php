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
$trainer_id = $_SESSION['user_id'];
$service_id = $available_date = $start_time = $end_time = $available_slots = "";
$errors = [];
$success_message = "";

// Handle form submission for setting availability
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $availability_id = isset($_POST["availability_id"]) ? intval($_POST["availability_id"]) : 0;
  $service_id = intval($_POST["service_id"]);
  $available_date = htmlspecialchars(trim($_POST["available_date"]));
  $start_time = htmlspecialchars(trim($_POST["start_time"]));
  $end_time = htmlspecialchars(trim($_POST["end_time"]));
  $available_slots = intval($_POST["available_slots"]);

  if (empty($service_id) || empty($available_date) || empty($start_time) || empty($end_time) || $available_slots <= 0) {
    $errors[] = "All fields are required, and slots must be greater than 0.";
  } else {
    if ($availability_id > 0) {
      // Update existing availability
      $stmt = $conn->prepare("UPDATE availability SET service_id = ?, available_date = ?, start_time = ?, end_time = ?, available_slots = ? WHERE id = ? AND trainer_id = ?");
      $stmt->bind_param("isssiii", $service_id, $available_date, $start_time, $end_time, $available_slots, $availability_id, $trainer_id);
    } else {
      // Insert new availability
      $stmt = $conn->prepare("INSERT INTO availability (service_id, trainer_id, available_date, start_time, end_time, available_slots) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("iisssi", $service_id, $trainer_id, $available_date, $start_time, $end_time, $available_slots);
    }

    if ($stmt->execute()) {
      $success_message = "Availability " . ($availability_id > 0 ? "updated" : "set") . " successfully!";
    } else {
      $errors[] = "An error occurred while saving your availability. Please try again.";
    }
    $stmt->close();
  }
}

// Fetch the services offered by the trainer
$services = [];
$stmt = $conn->prepare("SELECT id, description FROM tutoring_services WHERE trainer_id = ?");
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $services[] = $row;
}
$stmt->close();

// Fetch existing availability if editing
$availability_to_edit = [];
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
  $availability_id = intval($_GET['edit']);
  $stmt = $conn->prepare("SELECT * FROM availability WHERE id = ? AND trainer_id = ?");
  $stmt->bind_param("ii", $availability_id, $trainer_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $availability_to_edit = $result->fetch_assoc();
  $stmt->close();
}

$conn->close();
?>
<?php

include('../components/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Set Availability</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <h1><?php echo isset($availability_to_edit['id']) ? "Edit" : "Set"; ?> Availability</h1>

  <?php if ($success_message): ?>
    <p style="color:green;"><?php echo $success_message; ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . (isset($availability_to_edit['id']) ? "?edit=" . $availability_to_edit['id'] : ""); ?>" method="post">
    <input type="hidden" name="availability_id" value="<?php echo isset($availability_to_edit['id']) ? $availability_to_edit['id'] : 0; ?>">

    <label for="service_id">Select Service:</label><br>
    <select id="service_id" name="service_id" required>
      <option value="">-- Select a Service --</option>
      <?php foreach ($services as $service): ?>
        <option value="<?php echo $service['id']; ?>" <?php echo (isset($availability_to_edit['service_id']) && $availability_to_edit['service_id'] == $service['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($service['description']); ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

    <label for="available_date">Select Date:</label><br>
    <input type="date" id="available_date" name="available_date" value="<?php echo isset($availability_to_edit['available_date']) ? $availability_to_edit['available_date'] : ''; ?>" required><br><br>

    <label for="start_time">Start Time:</label><br>
    <input type="time" id="start_time" name="start_time" value="<?php echo isset($availability_to_edit['start_time']) ? $availability_to_edit['start_time'] : ''; ?>" required><br><br>

    <label for="end_time">End Time:</label><br>
    <input type="time" id="end_time" name="end_time" value="<?php echo isset($availability_to_edit['end_time']) ? $availability_to_edit['end_time'] : ''; ?>" required><br><br>

    <label for="available_slots">Available Slots:</label><br>
    <input type="number" id="available_slots" name="available_slots" value="<?php echo isset($availability_to_edit['available_slots']) ? $availability_to_edit['available_slots'] : ''; ?>" required><br><br>

    <input type="submit" value="<?php echo isset($availability_to_edit['id']) ? "Update Availability" : "Set Availability"; ?>">
  </form>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
  <br>
  <?php include '../components/footer.php'; ?>
</body>

</html>