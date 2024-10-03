<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize summary variables
$total_users = $total_services = $total_tutoring_services = $total_bookings = $total_reviews = 0;

// Fetch admin details
$admin_firstname = isset($_SESSION['firstname']) ? htmlspecialchars($_SESSION['firstname']) : 'Unknown';
$admin_lastname = isset($_SESSION['lastname']) ? htmlspecialchars($_SESSION['lastname']) : 'Unknown';
$admin_email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Unknown';
$user_id = $_SESSION['user_id'];

// Fetch the admin's profile picture
$query = "SELECT picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($admin_picture);
$stmt->fetch();
$stmt->close();

// Set a default picture if no picture is found
if (empty($admin_picture)) {
  $admin_picture = 'default_profile.png'; // Make sure you have a default profile image available
}

// Fetch summary data
$query = "SELECT COUNT(*) as total FROM users";
$result = $conn->query($query);
if ($row = $result->fetch_assoc()) {
  $total_users = $row['total'];
}



$query = "SELECT COUNT(*) as total FROM tutoring_services";
$result = $conn->query($query);
if ($row = $result->fetch_assoc()) {
  $total_tutoring_services = $row['total'];
}

$query = "SELECT COUNT(*) as total FROM bookings";
$result = $conn->query($query);
if ($row = $result->fetch_assoc()) {
  $total_bookings = $row['total'];
}

$query = "SELECT COUNT(*) as total FROM reviews";
$result = $conn->query($query);
if ($row = $result->fetch_assoc()) {
  $total_reviews = $row['total'];
}

// Fetch bookings and student details
$bookings_query = "
    SELECT 
        b.id AS booking_id, 
        CONCAT(s.firstname, ' ', s.lastname) AS student_name, 
        s.picture AS student_picture, 
        t.name AS tutoring_service, 
        b.booking_date 
    FROM bookings b
    JOIN users s ON b.student_id = s.id
    JOIN tutoring_services ts ON b.service_id = ts.id
    JOIN subjects t ON ts.subject_id = t.id
    ORDER BY b.booking_date DESC
";
$bookings_result = $conn->query($bookings_query);

$bookings = [];
if ($bookings_result->num_rows > 0) {
  while ($row = $bookings_result->fetch_assoc()) {
    $bookings[] = $row;
  }
}

$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
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

    .admin-info,
    .booking-info {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .admin-info img,
    .booking-info img {
      margin-right: 15px;
    }

    .admin-details,
    .booking-details {
      margin-left: 15px;
    }

    .admin-details p,
    .booking-details p {
      margin: 0;
    }

    .booking-details ul {
      list-style: none;
      padding-left: 0;
    }

    .booking-details li {
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      border-bottom: 1px solid #ccc;
      padding-bottom: 10px;
    }

    .booking-details li img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      margin-right: 15px;
      object-fit: cover;
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

    .view-all-bookings {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: black;
      text-decoration: none;
      font-weight: bold;
    }

    .view-all-bookings:hover {
      color: red;
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

  <h1>Welcome to Your Dashboard, <?php echo $admin_firstname; ?>!</h1>

  <div class="container">
    <div class="row">
      <!-- Profile Box -->
      <div class="col-md-4">
        <div class="box profile-box">
          <img src="../uploads/<?php echo htmlspecialchars($admin_picture); ?>" alt="Profile Picture">
          <h3><?php echo htmlspecialchars($admin_firstname . ' ' . $admin_lastname); ?></h3>
          <p>Admin</p>
          <!-- Link to the edit user page with admin ID -->
          <a href="edit_user.php?id=<?php echo $user_id; ?>">Edit Profile</a>
          <hr>
          <div class="links-box">
            <h3>Useful Links</h3>
            <ul class="list-unstyled">
              <li><a href="manage_users.php">Manage Users</a></li>
              <li><a href="manage_services.php">Manage General Services</a></li>
              <li><a href="manage_bookings.php">Manage Bookings</a></li>
              <li><a href="manage_reviews.php">Manage Reviews</a></li>
              <li><a href="manage_schedule.php">Manage Schedule</a></li>
              <li><a href="../pages/logout.php">Logout</a></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Additional Information Boxes -->
      <div class="col-md-8">
        <div class="box admin-info-box">
          <h3>Summary</h3>
          <ul>
            <li><strong>Total Users:</strong> <?php echo $total_users; ?></li>
            <li><strong>Total General Services:</strong> <?php echo $total_services; ?></li>
            <li><strong>Total Tutoring Services:</strong> <?php echo $total_tutoring_services; ?></li>
            <li><strong>Total Bookings:</strong> <?php echo $total_bookings; ?></li>
            <li><strong>Total Reviews:</strong> <?php echo $total_reviews; ?></li>
          </ul>
        </div>

        <div class="box booking-info-box">
          <h3>Manage Bookings</h3>
          <ul class="booking-details">
            <?php foreach ($bookings as $booking): ?>
              <li>
                <img src="../uploads/<?php echo htmlspecialchars($booking['student_picture'] ?? 'default_profile.png'); ?>" alt="Student Picture">
                <div>
                  <p><strong><?php echo htmlspecialchars($booking['student_name']); ?></strong></p>
                  <p><?php echo htmlspecialchars($booking['tutoring_service']); ?></p>
                  <p><?php echo htmlspecialchars($booking['booking_date']); ?></p>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
          <a href="manage_bookings.php" class="view-all-bookings">View All Bookings</a>
        </div>
      </div>
    </div>
  </div>

  <?php include '../components/footer.php'; ?>
</body>

</html>