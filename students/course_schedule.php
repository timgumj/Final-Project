<?php
session_start();

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$user_id = $_SESSION['user_id'];
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$course_days = [];
$course_name = '';
$trainer_name = '';
$trainer_info = '';
$trainer_picture = '';

// Fetch the course name, trainer's info, and profile picture
$trainer_query = "
    SELECT s.name, u.firstname, u.lastname, u.profile_info, u.picture
    FROM users u
    JOIN tutoring_services ts ON ts.trainer_id = u.id
    JOIN bookings b ON b.service_id = ts.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE b.student_id = ? AND ts.id = ?
    LIMIT 1
";
$stmt = $conn->prepare($trainer_query);
$stmt->bind_param("ii", $user_id, $service_id);
$stmt->execute();
$stmt->bind_result($course_name, $trainer_firstname, $trainer_lastname, $trainer_info, $trainer_picture);
$stmt->fetch();
$stmt->close();

$trainer_name = $trainer_firstname . ' ' . $trainer_lastname;

// Fetch the course days for the specific course the student has booked
$query = "
    SELECT cd.id, cd.course_date, cd.title, s.name AS subject_name
    FROM course_days cd
    JOIN tutoring_services ts ON cd.tutoring_service_id = ts.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.id = ?
    ORDER BY cd.course_date
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $course_days[] = $row;
}

$stmt->close();
$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Course Schedule</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .course-title {
      text-align: center;
      font-size: 22px;
      margin-top: 20px;
      margin-bottom: 20px;
      font-weight: 500;
    }

    .course-schedule {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      padding: 20px;
      justify-content: flex-start;
    }

    .course-box {
      flex: 1 1 calc(33.333% - 40px);
      background-color: #f9f9f9;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
      box-sizing: border-box;
      transition: transform 0.2s;
    }

    .course-box:hover {
      transform: translateY(-5px);
    }

    .course-box h2 {
      font-size: 20px;
      margin-bottom: 10px;
    }

    .course-box p {
      margin: 5px 0;
    }

    .course-box a {
      display: inline-block;
      margin-top: 10px;
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
      transition: color 0.3s, border-color 0.3s;
    }

    .course-box a:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    .trainer-box {
      flex: 1 1 100%;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
      box-sizing: border-box;
      margin-bottom: 20px;
      text-align: center;
    }

    .trainer-box img {
      width: 150px;
      height: 150px;
      margin-top: 10px;
      margin-bottom: 20px;
      border-radius: 50%;
      object-fit: cover;
    }

    .trainer-box h2 {
      font-size: 24px;
      margin-bottom: 10px;
    }

    .trainer-box p {
      font-size: 16px;
      margin: 5px 0;
    }

    @media (max-width: 768px) {
      .course-box {
        flex: 1 1 calc(50% - 20px);
      }
    }

    @media (max-width: 480px) {
      .course-box {
        flex: 1 1 100%;
      }

      .trainer-box img {
        width: 120px;
        height: 120px;
      }
    }

    .course-box .date,
    .course-box .time {
      font-weight: bold;
      font-size: 14px;
    }
  </style>
</head>

<body>

  <div class="course-title">
    <?php echo htmlspecialchars($course_name); ?>
  </div>

  <div class="course-schedule">
    <?php if ($trainer_info): ?>
      <div class="trainer-box">
        <h2>Your Trainer: <?php echo htmlspecialchars($trainer_name); ?></h2>
        <img src="../uploads/<?php echo htmlspecialchars($trainer_picture); ?>" alt="Trainer Picture">
        <p><?php echo htmlspecialchars($trainer_info); ?></p>
      </div>
    <?php endif; ?>

    <?php if (count($course_days) > 0): ?>
      <?php foreach ($course_days as $day): ?>
        <div class="course-box">
          <h2><?php echo htmlspecialchars($day['title']); ?></h2>
          <?php
          $date = new DateTime($day['course_date']);
          $formatted_date = $date->format('d-m-Y'); // Day-Month-Year format
          $formatted_time = $date->format('H:i'); // Hour:Minute format
          ?>
          <p class="date"><strong>Date:</strong> <?php echo htmlspecialchars($formatted_date); ?></p>
          <p class="time"><strong>Time:</strong> <?php echo htmlspecialchars($formatted_time); ?></p>
          <p><strong>Subject:</strong> <?php echo htmlspecialchars($day['subject_name']); ?></p>
          <a href="course_day_details.php?day_id=<?php echo $day['id']; ?>">View Details</a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No courses scheduled.</p>
    <?php endif; ?>
  </div>

  <p><a href="dashboard.php">Back to Dashboard</a></p>

  <?php include '../components/footer.php'; ?>
</body>

</html>