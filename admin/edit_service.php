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
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$subject_id = $trainer_id = $university_id = $description = $price = $available_slots = "";
$errors = [];
$success_message = "";

// Fetch the service details if service_id is valid
if ($service_id > 0) {
  $stmt = $conn->prepare("
        SELECT ts.id, ts.subject_id, ts.trainer_id, ts.university_id, ts.description, ts.price, ts.available_slots 
        FROM tutoring_services ts
        WHERE ts.id = ?
    ");
  $stmt->bind_param("i", $service_id);
  $stmt->execute();
  $stmt->bind_result($id, $subject_id, $trainer_id, $university_id, $description, $price, $available_slots);
  $stmt->fetch();
  $stmt->close();
}

// Handle form submission to update the service details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $subject_id = intval($_POST['subject_id']);
  $trainer_id = intval($_POST['trainer_id']);
  $university_id = intval($_POST['university_id']);
  $description = htmlspecialchars(trim($_POST['description']));
  $price = htmlspecialchars(trim($_POST['price']));
  $available_slots = intval($_POST['available_slots']);

  if (empty($subject_id) || empty($trainer_id) || empty($university_id) || empty($description) || empty($price) || $available_slots <= 0) {
    $errors[] = "All fields are required, and available slots must be positive.";
  } else {
    $stmt = $conn->prepare("UPDATE tutoring_services SET subject_id = ?, trainer_id = ?, university_id = ?, description = ?, price = ?, available_slots = ? WHERE id = ?");
    $stmt->bind_param("iiissdi", $subject_id, $trainer_id, $university_id, $description, $price, $available_slots, $service_id);
    if ($stmt->execute()) {
      $success_message = "Service updated successfully!";
    } else {
      $errors[] = "An error occurred while updating the service. Please try again.";
    }
    $stmt->close();
  }
}

// Fetch subjects for the dropdown
$subjects = [];
$stmt = $conn->prepare("SELECT id, name FROM subjects");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $subjects[] = $row;
}
$stmt->close();

// Fetch trainers for the dropdown
$trainers = [];
$stmt = $conn->prepare("SELECT id, firstname, lastname FROM users WHERE role = 'trainer'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $trainers[] = $row;
}
$stmt->close();

// Fetch universities for the dropdown
$universities = [];
$stmt = $conn->prepare("SELECT id, name FROM universities");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $universities[] = $row;
}
$stmt->close();

$conn->close();
?>
<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Service</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Edit Service</h1>

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

  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $service_id; ?>" method="post">
    <label for="subject_id">Subject:</label><br>
    <select id="subject_id" name="subject_id" required>
      <option value="">-- Select a Subject --</option>
      <?php foreach ($subjects as $subject): ?>
        <option value="<?php echo $subject['id']; ?>" <?php echo ($subject['id'] == $subject_id) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($subject['name']); ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

    <label for="trainer_id">Trainer:</label><br>
    <select id="trainer_id" name="trainer_id" required>
      <option value="">-- Select a Trainer --</option>
      <?php foreach ($trainers as $trainer): ?>
        <option value="<?php echo $trainer['id']; ?>" <?php echo ($trainer['id'] == $trainer_id) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($trainer['firstname'] . ' ' . $trainer['lastname']); ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

    <label for="university_id">University:</label><br>
    <select id="university_id" name="university_id" required>
      <option value="">-- Select a University --</option>
      <?php foreach ($universities as $university): ?>
        <option value="<?php echo $university['id']; ?>" <?php echo ($university['id'] == $university_id) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($university['name']); ?>
        </option>
      <?php endforeach; ?>
    </select><br><br>

    <label for="description">Description:</label><br>
    <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea><br><br>

    <label for="price">Price:</label><br>
    <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required><br><br>

    <label for="available_slots">Available Slots:</label><br>
    <input type="number" id="available_slots" name="available_slots" value="<?php echo htmlspecialchars($available_slots); ?>" required><br><br>

    <input type="submit" value="Update Service">
  </form>

  <p><a href="manage_services.php">Back to Manage Services</a></p>
  <?php include '../components/footer.php'; ?>
</body>

</html>