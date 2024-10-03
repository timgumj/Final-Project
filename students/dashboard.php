<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection if needed for additional features
require_once '../config/dbconnection.php';

// Initialize variables and check if session keys are set
$firstname = isset($_SESSION['firstname']) ? $_SESSION['firstname'] : '';
$lastname = isset($_SESSION['lastname']) ? $_SESSION['lastname'] : '';
$email = isset($_SESSION['email']) ? $_SESSION['email'] : ''; // Ensuring email is retrieved from session
$user_id = $_SESSION['user_id'];

// Fetch the student's profile picture
$query = "SELECT picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($picture);
$stmt->fetch();
$stmt->close(); // Close the statement after fetching the picture

// Set a default picture if no picture is found
if (empty($picture)) {
  $picture = 'avatar.png'; // Default profile picture
}

// Initialize the $courses_completed array
$courses_completed = [];

// Fetch student photo and services booked, excluding canceled bookings
$query = "
  SELECT u.picture, 
         ts.short_description, ts.start_date, ts.end_date, 
         s.name AS subject_name, 
         un.name AS university_name,
         t.firstname AS tutor_firstname, t.lastname AS tutor_lastname, t.picture AS tutor_picture,
         b.status AS booking_status, ts.id AS service_id
  FROM users u 
  LEFT JOIN bookings b ON b.student_id = u.id 
  LEFT JOIN tutoring_services ts ON b.service_id = ts.id 
  LEFT JOIN subjects s ON ts.subject_id = s.id
  LEFT JOIN universities un ON ts.university_id = un.id
  LEFT JOIN users t ON ts.trainer_id = t.id
  WHERE u.id = ? AND b.status != 'canceled'
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($picture, $short_description, $start_date, $end_date, $subject_name, $university_name, $tutor_firstname, $tutor_lastname, $tutor_picture, $booking_status, $service_id);
$booked_services = [];

while ($stmt->fetch()) {
  if ($service_id) {
    $booked_services[] = [
      'subject_name' => $subject_name,
      'university_name' => $university_name,
      'start_date' => $start_date,
      'end_date' => $end_date,
      'tutor_firstname' => $tutor_firstname,
      'tutor_lastname' => $tutor_lastname,
      'tutor_picture' => $tutor_picture,
      'booking_status' => $booking_status,
      'service_id' => $service_id,
    ];

    // Assuming "completed" means booking status is "completed"
    if ($booking_status === 'completed') {
      $courses_completed[] = [
        'subject_name' => $subject_name,
        'university_name' => $university_name,
        'start_date' => $start_date,
        'end_date' => $end_date,
      ];
    }
  }
}

$stmt->close(); // Close the statement after using it
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
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

    .profile-box img,
    .services-box img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
    }

    .links-box a,
    .services-box a,
    .profile-box a {
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
      display: inline-block;
      margin-bottom: 10px;
    }

    .links-box a:hover,
    .services-box a:hover,
    .profile-box a:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    h1 {
      font-size: 24px;
      text-align: center;
      text-decoration: underline;
      margin-bottom: 30px;
    }

    .box.reviews-box h3,
    .box.services-box h3 {
      text-align: right;
    }

    .tutor-info {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .tutor-info img {
      margin-right: 15px;
    }

    .service-details {
      margin-left: 15px;
    }

    .service-details p {
      margin: 0;
    }

    .service-details h4 {
      font-weight: 300;
      /* Thin font weight for tutor name */
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

  <h1>Welcome to Your Dashboard, <?php echo htmlspecialchars($firstname); ?>!</h1>

  <div class="container">
    <div class="row">
      <!-- Combined Profile and Useful Links Box -->
      <div class="col-md-4">
        <div class="box profile-box">
          <img src="../uploads/<?php echo htmlspecialchars($picture); ?>" alt="Profile Picture">
          <h3><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></h3>
          <p>Student</p>
          <a href="profile.php">Edit Profile</a>
          <hr>
          <div class="links-box">
            <h3>Useful Links</h3>
            <ul class="list-unstyled">

              <li><a href="book_service.php">Book a Service</a></li>
              <li><a href="courses.php">View Available Courses</a></li>
              <li><a href="my_bookings.php">My Bookings</a></li>
              <li><a href="my_reviews.php">My Reviews</a></li>
              <li><a href="../pages/logout.php">Logout</a></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Courses Completed Box -->
      <div class="col-md-8">
        <div class="box reviews-box">
          <h3>Courses Completed</h3>
          <?php if (count($courses_completed) > 0): ?>
            <ul>
              <?php foreach ($courses_completed as $course): ?>
                <li>
                  <strong><?php echo htmlspecialchars($course['subject_name']); ?></strong> - <?php echo htmlspecialchars($course['university_name']); ?>
                  <br>
                  <small>From <?php echo htmlspecialchars($course['start_date']); ?> to <?php echo htmlspecialchars($course['end_date']); ?></small>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No courses completed yet.</p>
          <?php endif; ?>
        </div>

        <div class="box services-box">
          <h3>Services Booked</h3>
          <?php if (count($booked_services) > 0): ?>
            <ul>
              <?php foreach ($booked_services as $service): ?>
                <li>
                  <div class="tutor-info">
                    <img src="../uploads/<?php echo htmlspecialchars($service['tutor_picture']); ?>" alt="Tutor Picture">
                    <div class="service-details">
                      <h4><?php echo htmlspecialchars($service['tutor_firstname'] . ' ' . $service['tutor_lastname']); ?></h4>
                      <p><strong>Course:</strong> <?php echo htmlspecialchars($service['subject_name']); ?></p>
                      <p><strong>University:</strong> <?php echo htmlspecialchars($service['university_name']); ?></p>
                      <p><small><?php echo htmlspecialchars($service['start_date']); ?> to <?php echo htmlspecialchars($service['end_date']); ?></small></p>
                      <?php if ($service['booking_status'] === 'confirmed'): ?>
                        <a href="course_schedule.php?service_id=<?php echo $service['service_id']; ?>">View Schedule</a>
                      <?php else: ?>
                        <p><em>Pending</em></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No services booked.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php include '../components/footer.php'; ?>
</body>

</html>