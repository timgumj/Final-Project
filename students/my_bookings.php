<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  // If not logged in or not a student, redirect to the login page
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$student_id = $_SESSION['user_id'];
$services = [];
$errors = [];

// Fetch the services the student is booked into
$sql = "
    SELECT 
        b.id AS booking_id, 
        b.status, 
        CONCAT(t.firstname, ' ', t.lastname, ' - ', u.name, ' - ', s.name) AS service_name, 
        ts.start_date,
        ts.end_date,
        t.firstname AS trainer_firstname, 
        t.lastname AS trainer_lastname,
        ts.id AS service_id
    FROM bookings b
    JOIN tutoring_services ts ON b.service_id = ts.id
    JOIN users t ON ts.trainer_id = t.id
    JOIN universities u ON ts.university_id = u.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE b.student_id = ?
    GROUP BY ts.id
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
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
  <title>My Services</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <h1>My Services</h1>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (count($services) > 0): ?>
    <table border="1" cellpadding="10">
      <thead>
        <tr>
          <th>Service Name</th>
          <th>Trainer Name</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Status</th>
          <th>Courses</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $service): ?>
          <tr>
            <td><?php echo htmlspecialchars($service['service_name']); ?></td>
            <td><?php echo htmlspecialchars($service['trainer_firstname'] . ' ' . $service['trainer_lastname']); ?></td>
            <td><?php echo htmlspecialchars($service['start_date']); ?></td>
            <td><?php echo htmlspecialchars($service['end_date']); ?></td>
            <td><?php echo htmlspecialchars($service['status']); ?></td>
            <td>
              <?php if ($service['status'] === 'confirmed'): ?>
                <a href="course_schedule.php?service_id=<?php echo $service['service_id']; ?>">Go to Courses</a>
              <?php else: ?>
                Not Available
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No services found.</p>
  <?php endif; ?>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
  <?php
  include '../components/footer.php';  // Adjust the path if necessary
  ?>
</body>

</html>