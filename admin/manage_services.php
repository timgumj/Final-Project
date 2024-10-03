<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$services = [];
$trainers = [];
$universities = [];
$subjects = [];
$errors = [];
$success_message = "";
$selected_trainer_id = isset($_GET['trainer_id']) ? intval($_GET['trainer_id']) : 0;

// Handle course assignment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_trainer'])) {
  $trainer_id = intval($_POST['trainer_id']);
  $university_id = intval($_POST['university_id']);
  $subject_id = intval($_POST['subject_id']);
  $price = htmlspecialchars(trim($_POST['price']));
  $available_slots = intval($_POST['available_slots']);
  $short_description = htmlspecialchars(trim($_POST['short_description']));
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];

  if (empty($trainer_id) || empty($university_id) || empty($subject_id) || empty($price) || $available_slots <= 0 || empty($start_date) || empty($end_date)) {
    $errors[] = "All fields including start and end date are required, and available slots must be positive.";
  } else {
    // Insert new tutoring service into the database
    $stmt = $conn->prepare("INSERT INTO tutoring_services (subject_id, trainer_id, university_id, price, available_slots, short_description, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssss", $subject_id, $trainer_id, $university_id, $price, $available_slots, $short_description, $start_date, $end_date);

    if ($stmt->execute()) {
      $service_id = $stmt->insert_id;

      // Generate daily entries for the course_days table
      $start_date_obj = new DateTime($start_date);
      $end_date_obj = new DateTime($end_date);
      $interval = new DateInterval('P1D');
      $date_range = new DatePeriod($start_date_obj, $interval, $end_date_obj->modify('+1 day'));

      foreach ($date_range as $date) {
        if (!in_array($date->format('N'), [6, 7])) { // Skip weekends
          $course_date = $date->format('Y-m-d');
          $stmt = $conn->prepare("INSERT INTO course_days (tutoring_service_id, course_date) VALUES (?, ?)");
          $stmt->bind_param("is", $service_id, $course_date);
          $stmt->execute();
        }
      }

      $success_message = "Trainer successfully assigned to the course and schedule generated!";
    } else {
      $errors[] = "An error occurred while assigning the trainer. Please try again.";
    }
    $stmt->close();
  }
}

// Handle delete service request
if (isset($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);

  // Start transaction
  $conn->begin_transaction();

  try {
    // Delete related entries in the reviews table
    $stmt = $conn->prepare("DELETE FROM reviews WHERE service_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // Delete related entries in the bookings table
    $stmt = $conn->prepare("DELETE FROM bookings WHERE service_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // Delete related entries in the course_materials table
    $stmt = $conn->prepare("DELETE FROM course_materials WHERE course_day_id IN (SELECT id FROM course_days WHERE tutoring_service_id = ?)");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // Delete related entries in the course_days table
    $stmt = $conn->prepare("DELETE FROM course_days WHERE tutoring_service_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // Finally, delete the service itself
    $stmt = $conn->prepare("DELETE FROM tutoring_services WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
      $conn->commit();
      $success_message = "Service and all related data deleted successfully.";
    } else {
      $conn->rollback();
      $errors[] = "Failed to delete the service. Please try again.";
    }
    $stmt->close();
  } catch (Exception $e) {
    $conn->rollback();
    $errors[] = "An error occurred: " . $e->getMessage();
  }
}

// Fetch existing services for the admin to manage, with optional trainer filtering
$query = "
    SELECT ts.id, s.name AS subject_name, u.name AS university_name, t.firstname AS trainer_firstname, t.lastname AS trainer_lastname, ts.price, ts.available_slots, ts.short_description, ts.start_date, ts.end_date
    FROM tutoring_services ts
    JOIN subjects s ON ts.subject_id = s.id
    JOIN universities u ON ts.university_id = u.id
    JOIN users t ON ts.trainer_id = t.id
";
if ($selected_trainer_id > 0) {
  $query .= " WHERE ts.trainer_id = ?";
}

$stmt = $conn->prepare($query);

if ($selected_trainer_id > 0) {
  $stmt->bind_param("i", $selected_trainer_id);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $services[] = $row;
}
$stmt->close();

// Fetch trainers, universities, and subjects for dropdowns
$trainers = $conn->query("SELECT id, firstname, lastname FROM users WHERE role = 'trainer'")->fetch_all(MYSQLI_ASSOC);
$universities = $conn->query("SELECT id, name FROM universities")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT id, name FROM subjects")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<?php
include('../components/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Services</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body {
      font-family: 'Roboto Mono', monospace;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      text-align: center;
    }

    h1,
    h2 {
      text-align: center;
    }

    .service-form,
    .service-list {
      margin-bottom: 40px;
    }

    .service-list {
      list-style: none;
      padding: 0;
    }

    .service-list li {
      background-color: #f9f9f9;
      margin-bottom: 15px;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .service-list strong {
      display: block;
      margin-bottom: 5px;
    }

    .service-actions a {
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
      margin-right: 10px;
      transition: color 0.3s, border-color 0.3s;
    }

    .service-actions a:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    .back-to-dashboard {
      display: block;
      text-align: center;
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
      margin-top: 20px;
      transition: color 0.3s, border-color 0.3s;
    }

    .back-to-dashboard:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    .btn-submit {
      background-color: #000;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn-submit:hover {
      background-color: #333;
    }

    select {
      appearance: none;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      width: 100%;
      max-width: 100%;
      background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik02LjYzIDEzLjM3bDM5LjU1IDM5LjU1YzEuNTMxIDEuNTMxIDMuNTkxIDIuMzk4IDUuNzYxIDIuMzk4czQuMjM5LS44NjcgNS43NjEtMi4zOThsMzkuNTUtMzkuNTVjMy4xNTUtMy4xNTQgMy4xNTUtOC4yMTQgMC0xMS4zNjhBNy43OTUgNy43OTUgMCAwIDAgODkuMjIzIDExTDUwIDQ5LjIzIDExLjA4IDExQTMuOTczIDMuOTczIDAgMCAwIDMuMTEgMi41YzAtMS4wODQgMC4zNTQtMi4xMDQgMS4wODgtMi44MzhoLjAwMWMwLjczNS0wLjczNiAxLjc1My0xLjA5NyAyLjgzNy0xLjA5N2guMDAxYzEuMDg0IDAgMi4xMDMgMC4zNjEgMi44NDkgMS4xMDdsNDMuNzIgNDMuNzJjMy4xNTQgMy4xNTUgMy4xNTQgOC4yMTQgMC4wMDEgMTEuMzY4WiIgZmlsbD0iIzAwMDAwMCIvPgo8L3N2Zz4K') no-repeat right 10px center/15px 15px;
    }

    select:focus {
      outline: none;
      border-color: #333;
    }

    select option {
      padding: 10px;
      background-color: #fff;
    }
  </style>
</head>

<body>
  <div class="container">
    <br>

    <?php
    if (!empty($success_message)) {
      echo "<p style='color:green;'>$success_message</p>";
    }

    if (!empty($errors)) {
      foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
      }
    }
    ?>

    <h2>Assign Trainer to Course</h2>
    <div class="service-form">
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="trainer_id">Trainer:</label><br>
        <select id="trainer_id" name="trainer_id" required>
          <option value="">-- Select a Trainer --</option>
          <?php foreach ($trainers as $trainer) : ?>
            <option value="<?php echo $trainer['id']; ?>"><?php echo htmlspecialchars($trainer['firstname'] . ' ' . $trainer['lastname']); ?></option>
          <?php endforeach; ?>
        </select><br><br>

        <label for="university_id">University:</label><br>
        <select id="university_id" name="university_id" required>
          <option value="">-- Select a University --</option>
          <?php foreach ($universities as $university) : ?>
            <option value="<?php echo $university['id']; ?>"><?php echo htmlspecialchars($university['name']); ?></option>
          <?php endforeach; ?>
        </select><br><br>

        <label for="subject_id">Subject:</label><br>
        <select id="subject_id" name="subject_id" required>
          <option value="">-- Select a Subject --</option>
          <?php foreach ($subjects as $subject) : ?>
            <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
          <?php endforeach; ?>
        </select><br><br>

        <label for="price">Price:</label><br>
        <input type="number" id="price" name="price" step="0.01" required><br><br>

        <label for="available_slots">Available Slots:</label><br>
        <input type="number" id="available_slots" name="available_slots" required><br><br>

        <label for="short_description">Short Description:</label><br>
        <textarea id="short_description" name="short_description" rows="4" cols="50" required></textarea><br><br>

        <label for="start_date">Start Date:</label><br>
        <input type="date" id="start_date" name="start_date" required><br><br>

        <label for="end_date">End Date:</label><br>
        <input type="date" id="end_date" name="end_date" required><br><br>

        <input type="submit" name="assign_trainer" value="Assign Trainer" class="btn-submit">
      </form>
    </div>

    <h2>Filter Services by Trainer</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="service-form">
      <label for="trainer_id_filter">Filter by Trainer:</label><br>
      <select id="trainer_id_filter" name="trainer_id" onchange="this.form.submit()">
        <option value="0">-- All Trainers --</option>
        <?php foreach ($trainers as $trainer) : ?>
          <option value="<?php echo $trainer['id']; ?>" <?php echo ($trainer['id'] == $selected_trainer_id) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($trainer['firstname'] . ' ' . $trainer['lastname']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <h2>Existing Services</h2>
    <ul class="service-list">
      <?php if (count($services) > 0) : ?>
        <?php foreach ($services as $service) : ?>
          <li>
            <strong><?php echo htmlspecialchars($service['subject_name']); ?></strong> at <?php echo htmlspecialchars($service['university_name']); ?> by <?php echo htmlspecialchars($service['trainer_firstname'] . ' ' . $service['trainer_lastname']); ?><br>
            Short Description: <?php echo htmlspecialchars($service['short_description']); ?><br>
            Price: $<?php echo htmlspecialchars($service['price']); ?><br>
            Slots: <?php echo htmlspecialchars($service['available_slots']); ?><br>
            Start Date: <?php echo htmlspecialchars($service['start_date']); ?><br>
            End Date: <?php echo htmlspecialchars($service['end_date']); ?><br>
            <div class="service-actions">
              <a href="edit_service.php?id=<?php echo $service['id']; ?>">Edit</a>
              <a href="manage_services.php?delete_id=<?php echo $service['id']; ?>" onclick="return confirm('Are you sure you want to delete this service? This will also delete all associated bookings, availability, and materials.');">Delete</a>
              <a href="manage_schedule.php?id=<?php echo $service['id']; ?>">Manage Schedule</a>
            </div>
          </li>
        <?php endforeach; ?>
      <?php else : ?>
        <li>No services found.</li>
      <?php endif; ?>
    </ul>

    <p><a href="dashboard.php" class="back-to-dashboard">Back to Dashboard</a></p>
  </div>

  <?php include '../components/footer.php'; ?>
</body>

</html>