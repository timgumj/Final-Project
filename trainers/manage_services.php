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
$services = [];
$errors = [];
$success_message = "";

// Handle "Make Available" action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_available'])) {
  $service_id = intval($_POST['service_id']);
  $stmt = $conn->prepare("UPDATE tutoring_services SET is_available = 1 WHERE id = ? AND trainer_id = ?");
  $stmt->bind_param("ii", $service_id, $user_id);
  if ($stmt->execute()) {
    $success_message = "Service made available for booking!";
  } else {
    $errors[] = "An error occurred while making the service available. Please try again.";
  }
  $stmt->close();
}

// Handle status update for bookings
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
  $booking_id = intval($_POST['booking_id']);
  $new_status = $_POST['status'];

  // Ensure the status is either 'complete' or 'canceled'
  if ($new_status === 'complete' || $new_status === 'canceled') {
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    if ($stmt->execute()) {
      $success_message = "Booking status updated successfully!";
    } else {
      $errors[] = "An error occurred while updating the booking status. Please try again.";
    }
    $stmt->close();
  } else {
    $errors[] = "Invalid status selected.";
  }
}

// Fetch existing services for the trainer to manage
$query = "
    SELECT ts.id, s.name AS subject_name, u.name AS university_name, ts.price, ts.available_slots, ts.start_date, ts.end_date, ts.is_available
    FROM tutoring_services ts
    JOIN subjects s ON ts.subject_id = s.id
    JOIN universities u ON ts.university_id = u.id
    WHERE ts.trainer_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $services[] = $row;
}
$stmt->close();
$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Your Services</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .service-box {
      border: 1px solid #ccc;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 5px;
      background-color: #f9f9f9;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .service-box h3 {
      margin-top: 0;
      margin-bottom: 10px;
    }

    .service-box p {
      margin: 5px 0;
    }

    .service-box a {
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
    }

    .service-box a:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    .service-box .available {
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
      font-weight: bold;
    }

    .service-box .available:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    .service-box form {
      display: inline;
    }

    .service-box input[type="submit"] {
      margin-top: 10px;
      padding: 5px 10px;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      background-color: #000;
      color: #fff;
      cursor: pointer;
    }

    .service-box input[type="submit"]:hover {
      background-color: #333;
    }

    .status-update {
      margin-top: 20px;
    }

    h1 {
      text-align: center;
      font-size: 24px;
      margin-bottom: 20px;
    }

    h2 {
      text-align: center;
      font-size: 20px;
      margin-bottom: 20px;
    }

    .back-to-dashboard {
      text-align: center;
      margin-top: 30px;
    }

    .back-to-dashboard a {
      color: black;
      text-decoration: none;
      border-bottom: 1px solid red;
      font-weight: bold;
      padding-bottom: 2px;
    }

    .back-to-dashboard a:hover {
      color: red;
      border-bottom: 1px solid black;
    }
  </style>
</head>

<body>
  <br>

  <?php
  if (!empty($success_message)) {
    echo "<p style='color:green;text-align:center;'>$success_message</p>";
  }

  if (!empty($errors)) {
    foreach ($errors as $error) {
      echo "<p style='color:red;text-align:center;'>$error</p>";
    }
  }
  ?>

  <h2>Your Services</h2>
  <?php if (count($services) > 0): ?>
    <?php foreach ($services as $service): ?>
      <div class="service-box">
        <h3><?php echo htmlspecialchars($service['subject_name']); ?> at <?php echo htmlspecialchars($service['university_name']); ?></h3>
        <p>Price: $<?php echo htmlspecialchars($service['price']); ?></p>
        <p>Slots: <?php echo htmlspecialchars($service['available_slots']); ?></p>
        <p>Start Date: <?php echo htmlspecialchars($service['start_date']); ?></p>
        <p>End Date: <?php echo htmlspecialchars($service['end_date']); ?></p>
        <a href="manage_schedule.php?id=<?php echo $service['id']; ?>">Manage Schedule</a><br>

        <div class="status-update">
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="status">Update Status:</label>
            <select name="status" id="status" required>
              <option value="">Select status</option>
              <option value="complete">Complete</option>
              <option value="canceled">Cancel</option>
            </select>
            <input type="hidden" name="booking_id" value="<?php echo $service['id']; ?>">
            <input type="submit" name="update_status" value="Update">
          </form>
        </div>

        <?php if ($service['is_available']): ?>
          <span class="available">Service is available for booking.</span>
        <?php else: ?>
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
            <input type="submit" name="make_available" value="Make Available">
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p style="text-align: center;">No services found.</p>
  <?php endif; ?>

  <div class="back-to-dashboard">
    <p><a href="dashboard.php">Back to Dashboard</a></p>
  </div>
  <br>
  <?php include '../components/footer.php'; ?>

</body>

</html>