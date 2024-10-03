<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  // If not logged in or not a student, redirect to the login page
  header("Location: ../login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$service_id = $booking_date = "";
$errors = [];
$success_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitize inputs
  $service_id = intval($_POST["service_id"]);
  $booking_date = htmlspecialchars(trim($_POST["booking_date"]));

  if (empty($service_id) || empty($booking_date)) {
    $errors[] = "All fields are required.";
  } else {
    // Insert booking into the database
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, booking_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $_SESSION['user_id'], $service_id, $booking_date);

    if ($stmt->execute()) {
      $success_message = "Booking successful!";
    } else {
      $errors[] = "An error occurred while booking. Please try again.";
    }
    $stmt->close();
  }
}

// Fetch available services with additional information (e.g., provider, subject)
$services = [];
$stmt = $conn->prepare("
  SELECT s.id, s.name, s.description, u.firstname AS provider_firstname, u.lastname AS provider_lastname, sub.name AS subject_name 
  FROM services s
  LEFT JOIN users u ON s.provider_id = u.id
  LEFT JOIN subjects sub ON s.subject_id = sub.id
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $services[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Book a Service</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h2>Book a Service</h2>

  <?php
  // Display success message
  if (!empty($success_message)) {
    echo "<p style='color:green;'>$success_message</p>";
  }

  // Display errors
  if (!empty($errors)) {
    foreach ($errors as $error) {
      echo "<p style='color:red;'>$error</p>";
    }
  }
  ?>

  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <label for="service_id">Select Service:</label><br>
    <select id="service_id" name="service_id" required>
      <option value="">-- Select a Service --</option>
      <?php foreach ($services as $service): ?>
        <option value="<?php echo $service['id']; ?>">
          <?php echo htmlspecialchars($service['name']) . " - " . htmlspecialchars($service['subject_name']) . " (by " . htmlspecialchars($service['provider_firstname'] . " " . $service['provider_lastname']) . ")"; ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

    <label for="booking_date">Select Date:</label><br>
    <input type="date" id="booking_date" name="booking_date" required><br><br>

    <input type="submit" value="Book Now">
  </form>

  <p><a href="../students/dashboard.php">Back to Dashboard</a></p>
</body>

</html>