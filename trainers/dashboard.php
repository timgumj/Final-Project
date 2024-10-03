<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'trainer' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Safely access session variables
$trainer_firstname = isset($_SESSION['firstname']) ? htmlspecialchars($_SESSION['firstname']) : 'Unknown';
$trainer_lastname = isset($_SESSION['lastname']) ? htmlspecialchars($_SESSION['lastname']) : 'Unknown';
$trainer_email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Unknown';
$user_id = $_SESSION['user_id'];

// Fetch the trainer's profile picture
$query = "SELECT picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($trainer_picture);
$stmt->fetch();
$stmt->close();

// Set a default picture if no picture is found
if (empty($trainer_picture)) {
  $trainer_picture = 'default_profile.png'; // Make sure you have a default profile image available
}

// Fetch the bookings made by students for this trainer
$query = "
  SELECT u.firstname AS student_firstname, u.lastname AS student_lastname, 
         s.name AS subject_name, un.name AS university_name
  FROM bookings b
  JOIN users u ON b.student_id = u.id
  JOIN tutoring_services ts ON b.service_id = ts.id
  JOIN subjects s ON ts.subject_id = s.id
  JOIN universities un ON ts.university_id = un.id
  WHERE ts.trainer_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($student_firstname, $student_lastname, $subject_name, $university_name);
$student_bookings = [];

while ($stmt->fetch()) {
  $student_bookings[] = [
    'student_name' => $student_firstname . ' ' . $student_lastname,
    'subject_name' => $subject_name,
    'university_name' => $university_name,
  ];
}

$stmt->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Trainer Dashboard</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto Mono', monospace;
      margin: 0;
      padding: 0;
    }

    .container {
      margin-top: 20px;
      margin-bottom: 60px;
      /* Ensure there's space for the footer */
    }

    .box {
      border: 1px solid #ccc;
      padding: 20px;
      border-radius: 5px;
      background-color: #f9f9f9;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      /* Added box shadow */
    }

    .profile-box img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
    }

    .links-box a,
    .profile-box a {
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
      display: inline-block;
      margin-bottom: 10px;
    }

    .links-box a:hover,
    .profile-box a:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    h1 {
      font-size: 20px;
      text-align: center;
      font-weight: 400;
      text-decoration: underline;
      margin-bottom: 30px;
    }

    .box h3 {
      font-size: 18px;
      text-align: right;
      font-weight: 300;
    }

    .trainer-info,
    .booking-info {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .trainer-info img,
    .booking-info img {
      margin-right: 15px;
    }

    .trainer-details,
    .booking-details {
      margin-left: 15px;
    }

    .trainer-details p,
    .booking-details p {
      margin: 0;
    }

    .booking-details ul {
      list-style: none;
      padding-left: 0;
    }

    .booking-details li {
      margin-bottom: 10px;
    }

    .btn-view-bookings {
      background-color: #000;
      color: #fff;
      padding: 6px 12px;
      border: none;
      border-radius: 3px;
      margin-top: 10px;
      margin-left: 15px;
      font-weight: 400;
      text-align: center;
      display: inline-block;
    }

    .btn-view-bookings:hover {
      background-color: #333;
    }

    @media (max-width: 768px) {

      .row>.col-md-4,
      .row>.col-md-8 {
        margin-bottom: 20px;
      }
    }
  </style>
</head>

<body>
  <br>
  <br>

  <h1>Welcome to Your Dashboard, <?php echo $trainer_firstname; ?>!</h1>

  <div class="container">
    <div class="row">
      <!-- Profile Box -->
      <div class="col-md-4">
        <div class="box profile-box">
          <img src="../uploads/<?php echo htmlspecialchars($trainer_picture); ?>" alt="Profile Picture">
          <h3><?php echo htmlspecialchars($trainer_firstname . ' ' . $trainer_lastname); ?></h3>
          <p>Trainer</p>
          <a href="edit_profile.php">Edit Profile</a>
          <hr>
          <div class="links-box">
            <h3>Useful Links</h3>
            <ul class="list-unstyled">
              <li><a href="manage_services.php">Manage Services</a></li>
              <li><a href="view_bookings.php">View Bookings</a></li>
              <li><a href="set_availability.php">Set Availability</a></li>
              <li><a href="../pages/logout.php">Logout</a></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Additional Information Boxes -->
      <div class="col-md-8">
        <div class="box trainer-info-box">
          <h3>Your Details</h3>
          <ul>
            <li><strong>Name:</strong> <?php echo $trainer_firstname . ' ' . $trainer_lastname; ?></li>
            <li><strong>Email:</strong> <?php echo $trainer_email; ?></li>
            <li><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></li>
          </ul>
        </div>

        <div class="box booking-info-box">
          <h3>Student's Bookings</h3>
          <ul>
            <?php if (count($student_bookings) > 0): ?>
              <?php foreach ($student_bookings as $booking): ?>
                <li>
                  <strong><?php echo htmlspecialchars($booking['university_name']); ?></strong> - <?php echo htmlspecialchars($booking['subject_name']); ?>
                  <br>
                  <small>Student: <?php echo htmlspecialchars($booking['student_name']); ?></small>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <p>No bookings made by students yet.</p>
            <?php endif; ?>
          </ul>
          <a href="view_bookings.php" class="btn-view-bookings">View Bookings</a>
        </div>
      </div>
    </div>
  </div>

  <?php include '../components/footer.php'; ?>
</body>

</html>