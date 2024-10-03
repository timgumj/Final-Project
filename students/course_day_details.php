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
$day_id = isset($_GET['day_id']) ? intval($_GET['day_id']) : 0;
$course_day = [];
$errors = [];

// Fetch the details of the selected course day
$stmt = $conn->prepare("
    SELECT cd.course_date, cd.title, cd.description, cd.file_type, cd.file_path, ts.subject_id, s.name AS subject_name
    FROM course_days cd
    JOIN tutoring_services ts ON cd.tutoring_service_id = ts.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE cd.id = ?
");
$stmt->bind_param("i", $day_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  $course_day = $row;
} else {
  $errors[] = "No details found for this course day.";
}

$stmt->close();
$conn->close();

// Function to convert video URLs to embed code
function embedVideo($description)
{
  // Regex pattern to detect YouTube or Vimeo URLs
  $patterns = [
    '/(https?:\/\/(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+))/',
    '/(https?:\/\/(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]+))/',
    '/(https?:\/\/(?:www\.)?vimeo\.com\/(\d+))/'
  ];

  $replacements = [
    '<iframe width="560" height="315" src="https://www.youtube.com/embed/$2" frameborder="0" allowfullscreen style="margin-bottom: 20px;"></iframe>',
    '<iframe width="560" height="315" src="https://www.youtube.com/embed/$2" frameborder="0" allowfullscreen style="margin-bottom: 20px;"></iframe>',
    '<iframe width="560" height="315" src="https://player.vimeo.com/video/$3" frameborder="0" allowfullscreen style="margin-bottom: 20px;"></iframe>'
  ];

  return preg_replace($patterns, $replacements, $description);
}

$course_day['description'] = embedVideo($course_day['description']);
?>
<?php

include('../components/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Course Day Details</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .content-container {
      text-align: center;
      padding: 0 5%;
      max-width: 1200px;
      margin: 0 auto;
    }

    .content-container h1,
    .content-container h2,
    .content-container h3 {
      font-size: 1.5em;
      margin-bottom: 10px;
    }

    .content-container h1 {
      font-size: 2em;
    }

    .content-container h2 {
      font-size: 1.75em;
    }

    .content-container p {
      font-size: 1em;
      margin-bottom: 10px;
      text-align: center;
    }

    .content-container iframe,
    .content-container video {
      display: block;
      margin: 0 auto 20px auto;
    }

    .content-container .materials-link {
      color: #000;
      text-decoration: none;
      border-bottom: 2px solid red;
    }

    .content-container .materials-link:hover {
      color: red;
      border-bottom: 2px solid red;
    }
  </style>
</head>

<body>
  <br>
  <div class="content-container">
    <h1>Details for Course Date: <?php echo htmlspecialchars($course_day['course_date']); ?></h1>

    <?php
    if (!empty($errors)) {
      foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
      }
    } else {
    ?>
      <h2><?php echo htmlspecialchars($course_day['title']); ?></h2>
      <p><strong>Subject:</strong> <?php echo htmlspecialchars($course_day['subject_name']); ?></p>
      <p><strong>Description:</strong> <?php echo $course_day['description']; ?></p>

      <?php if ($course_day['file_path']): ?>
        <h3>Materials</h3>
        <?php if ($course_day['file_type'] === 'pdf'): ?>
          <a class="materials-link" href="../uploads/<?php echo htmlspecialchars($course_day['file_path']); ?>" target="_blank">View PDF</a>
        <?php elseif ($course_day['file_type'] === 'video'): ?>
          <video width="600" controls style="margin-bottom: 20px;">
            <source src="../uploads/<?php echo htmlspecialchars($course_day['file_path']); ?>" type="video/mp4">
            Your browser does not support the video tag.
          </video>
        <?php endif; ?>
      <?php endif; ?>
    <?php } ?>

    <p><a href="course_schedule.php">Back to Schedule</a></p>
  </div>
  <br>
  <br>
  <?php
  include '../components/footer.php';
  ?>
</body>

</html>