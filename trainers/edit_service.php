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
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$service = null;
$errors = [];
$success_message = "";

// Fetch the service details
if ($service_id > 0) {
  $stmt = $conn->prepare("SELECT id, description, price, available_slots FROM tutoring_services WHERE id = ? AND trainer_id = ?");
  $stmt->bind_param("ii", $service_id, $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $service = $result->fetch_assoc();
  $stmt->close();

  if (!$service) {
    $errors[] = "Service not found. Please check the service ID and try again.";
  }
} else {
  $errors[] = "No service ID provided.";
}

// Handle form submission for updating the service
if ($_SERVER["REQUEST_METHOD"] == "POST" && $service) {
  $description = htmlspecialchars(trim($_POST["description"]));
  $price = htmlspecialchars(trim($_POST["price"]));
  $available_slots = intval($_POST["available_slots"]);

  if (empty($description) || empty($price) || $available_slots < 0) {
    $errors[] = "All fields are required, and slots must be non-negative.";
  } else {
    // Update the service in the database
    $stmt = $conn->prepare("UPDATE tutoring_services SET description = ?, price = ?, available_slots = ? WHERE id = ? AND trainer_id = ?");
    $stmt->bind_param("sdiii", $description, $price, $available_slots, $service_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
      $success_message = "Service updated successfully!";
      // Refresh the service data after update
      $service['description'] = $description;
      $service['price'] = $price;
      $service['available_slots'] = $available_slots;
    } else {
      $errors[] = "An error occurred while updating the service. Please try again.";
    }
    $stmt->close();
  }
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
  <title>Edit Service</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Edit Service</h1>

  <?php if ($success_message): ?>
    <p style="color:green;"><?php echo $success_message; ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($service): ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $service_id; ?>" method="post">
      <label for="description">Service Description:</label><br>
      <textarea id="description" name="description" required><?php echo htmlspecialchars($service['description']); ?></textarea><br><br>

      <label for="price">Price:</label><br>
      <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($service['price']); ?>" required><br><br>

      <label for="available_slots">Available Slots:</label><br>
      <input type="number" id="available_slots" name="available_slots" value="<?php echo htmlspecialchars($service['available_slots']); ?>" required><br><br>

      <input type="submit" value="Update Service">
    </form>
  <?php else: ?>
    <p>No service to edit. Please ensure you selected a valid service.</p>
  <?php endif; ?>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>

</html>