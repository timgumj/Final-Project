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
$courses = [];
$search_university = isset($_GET['university']) ? intval($_GET['university']) : '';
$search_subject = isset($_GET['subject']) ? intval($_GET['subject']) : '';

// Fetch all universities for the dropdown
$universities = [];
$uni_query = "SELECT id, name FROM universities";
$uni_result = $conn->query($uni_query);
while ($uni_row = $uni_result->fetch_assoc()) {
  $universities[] = $uni_row;
}

// Fetch all subjects for the dropdown
$subjects = [];
$subject_query = "SELECT id, name FROM subjects";
$subject_result = $conn->query($subject_query);
while ($subject_row = $subject_result->fetch_assoc()) {
  $subjects[] = $subject_row;
}

// Fetch all available courses with optional search filters
$query = "
    SELECT 
        ts.id, 
        ts.subject_id, 
        s.name AS subject_name, 
        ts.short_description,
        ts.start_date, 
        ts.end_date, 
        ts.available_slots, 
        u.name AS university_name, 
        t.firstname AS trainer_firstname, 
        t.lastname AS trainer_lastname, 
        t.picture AS trainer_picture
    FROM 
        tutoring_services ts
    JOIN 
        subjects s ON ts.subject_id = s.id
    JOIN 
        universities u ON ts.university_id = u.id
    JOIN 
        users t ON ts.trainer_id = t.id
    WHERE 
        ts.is_available = 1
";

// Append conditions for search filters
$params = [];
$types = '';
if (!empty($search_university)) {
  $query .= " AND u.id = ?";
  $params[] = $search_university;
  $types .= 'i';
}
if (!empty($search_subject)) {
  $query .= " AND s.id = ?";
  $params[] = $search_subject;
  $types .= 'i';
}

$stmt = $conn->prepare($query);

if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $courses[] = $row;
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
  <title>Available Courses</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    /* Center the available courses section */
    .available-courses {
      max-width: 100%;
      margin: 0 auto;
      padding: 20px;
    }

    /* Styling for the course cards */
    .course-card {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-bottom: 20px;
      margin-left: 2px;
      margin-right: 2px;
      display: flex;
      align-items: flex-start;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .course-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .course-card img {
      max-width: 120px;
      max-height: 120px;
      margin-right: 20px;
      border-radius: 50%;
    }

    .course-card h2 {
      margin: 0;
      font-size: 22px;
      color: #333;
    }

    .course-card p {
      margin: 5px 0;
      font-size: 14px;
      color: #555;
    }

    .course-card .short-description {
      font-style: italic;
      color: #777;
    }

    .course-card a.book-button {
      margin-top: 10px;
      padding: 8px 12px;
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
      transition: color 0.3s, border-color 0.3s;
    }

    .course-card a.book-button:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    .full-capacity {
      color: red;
      font-weight: bold;
      margin-top: 10px;
    }

    .search-form {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }

    .search-form select,
    .search-form input[type="submit"] {
      padding: 10px;
      margin-right: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .search-form input[type="submit"] {
      background-color: #333;
      color: #fff;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .search-form input[type="submit"]:hover {
      background-color: #555;
    }

    .back-to-dashboard {
      display: inline-block;
      margin-top: 30px;
      padding: 12px 25px;
      background-color: #333;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-size: 16px;
      text-align: center;
      transition: background-color 0.3s;
    }

    .back-to-dashboard:hover {
      background-color: #555;
    }
  </style>
</head>

<body>
  <div class="available-courses">
    <h1>Available Courses</h1>

    <form class="search-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get">
      <select id="university" name="university">
        <option value="">-- Select a University --</option>
        <?php foreach ($universities as $university): ?>
          <option value="<?php echo $university['id']; ?>" <?php echo ($university['id'] == $search_university) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($university['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select id="subject" name="subject">
        <option value="">-- Select a Subject --</option>
        <?php foreach ($subjects as $subject): ?>
          <option value="<?php echo $subject['id']; ?>" <?php echo ($subject['id'] == $search_subject) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($subject['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="submit" value="Search">
    </form>

    <?php if (count($courses) > 0): ?>
      <?php foreach ($courses as $course): ?>
        <div class="course-card">
          <img src="../uploads/<?php echo htmlspecialchars($course['trainer_picture']); ?>" alt="Trainer Picture">
          <div>
            <h2><?php echo htmlspecialchars($course['subject_name']); ?></h2>
            <p><strong>Tutor:</strong> <?php echo htmlspecialchars($course['trainer_firstname'] . ' ' . $course['trainer_lastname']); ?></p>
            <p><strong>University:</strong> <?php echo htmlspecialchars($course['university_name']); ?></p>
            <p><strong>Start Date:</strong> <?php echo htmlspecialchars($course['start_date']); ?></p>
            <p><strong>End Date:</strong> <?php echo htmlspecialchars($course['end_date']); ?></p>
            <p class="short-description"><?php echo htmlspecialchars($course['short_description']); ?></p>
            <?php if ($course['available_slots'] > 0): ?>
              <a href="book_service.php?service_id=<?php echo $course['id']; ?>" class="book-button">Book this Course</a>
            <?php else: ?>
              <p class="full-capacity">Course capacity is full</p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No courses available at the moment.</p>
    <?php endif; ?>

    <p><a href="dashboard.php" class="back-to-dashboard">Back to Dashboard</a></p>
  </div>

  <?php
  include '../components/footer.php';  // Adjust the path if necessary
  ?>
</body>

</html>