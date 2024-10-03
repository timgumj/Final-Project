<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Function to sanitize user input
function sanitizeInput($data)
{
  return htmlspecialchars(stripslashes(trim($data)));
}

// Initialize variables
$firstname = $lastname = $email = $password = $profile_info = $role = "";
$errors = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitize and validate inputs
  $firstname = sanitizeInput($_POST["firstname"]);
  $lastname = sanitizeInput($_POST["lastname"]);
  $email = sanitizeInput($_POST["email"]);
  $password = sanitizeInput($_POST["password"]);
  $profile_info = sanitizeInput($_POST["profile_info"]);
  $role = sanitizeInput($_POST["role"]);
  $picture = 'avatar.png'; // Default picture

  if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($role)) {
    $errors[] = "All fields are required.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
  } else {
    // Check if email already exists
    $result = $conn->query("SELECT email FROM users WHERE email = '$email'");

    if ($result->num_rows > 0) {
      $errors[] = "An account with this email already exists.";
    } else {
      // Handle file upload if a file is uploaded
      if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "../uploads/";
        $file_name = basename($_FILES['profile_picture']['name']);
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $target_file = $target_dir . uniqid() . "." . $file_type;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file type
        if (in_array($file_type, $allowed_types)) {
          if ($_FILES['profile_picture']['size'] <= 10485760) { // Limit file size to 10MB
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
              $picture = basename($target_file); // Update picture name
            } else {
              $errors[] = "There was an error uploading the file.";
            }
          } else {
            $errors[] = "File size exceeds the 10MB limit.";
          }
        } else {
          $errors[] = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
        }
      }

      // Hash the password
      $password = hash("sha256", $password);

      // Insert the new user into the database
      $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, role, picture, profile_info) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssss", $firstname, $lastname, $email, $password, $role, $picture, $profile_info);

      if ($stmt->execute()) {
        // User added successfully, show a success message
        $success_message = "User added successfully.";
        // Clear form fields
        $firstname = $lastname = $email = $password = $profile_info = $role = "";
      } else {
        $errors[] = "Failed to add user. Please try again.";
      }
      $stmt->close();
    }
  }
  $conn->close();
}
?>
<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Add New User</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h2>Add New User</h2>
  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
    <label for="firstname">First Name:</label><br>
    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required><br><br>

    <label for="lastname">Last Name:</label><br>
    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required><br><br>

    <label for="email">Email:</label><br>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br>

    <label for="password">Password:</label><br>
    <input type="password" id="password" name="password" required><br><br>

    <label for="role">Role:</label><br>
    <select id="role" name="role" required>
      <option value="">-- Select a Role --</option>
      <option value="student" <?php echo $role === 'student' ? 'selected' : ''; ?>>Student</option>
      <option value="trainer" <?php echo $role === 'trainer' ? 'selected' : ''; ?>>Trainer</option>
      <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
    </select><br><br>

    <label for="profile_info">Profile Info:</label><br>
    <textarea id="profile_info" name="profile_info"><?php echo htmlspecialchars($profile_info); ?></textarea><br><br>

    <label for="profile_picture">Profile Picture:</label><br>
    <input type="file" id="profile_picture" name="profile_picture" accept="image/*"><br><br>

    <input type="submit" value="Add User">
  </form>

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

  <p><a href="manage_users.php">Back to Manage Users</a></p>
</body>

</html>