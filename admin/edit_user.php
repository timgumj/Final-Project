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
$firstname = $lastname = $email = $role = $profile_info = $picture = "";
$errors = [];
$success_message = "";

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  $errors[] = "User ID is missing.";
} else {
  $user_id = intval($_GET['id']);

  // Fetch the user's current data
  $stmt = $conn->prepare("SELECT firstname, lastname, email, role, profile_info, picture FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $stmt->bind_result($firstname, $lastname, $email, $role, $profile_info, $current_picture);
  $stmt->fetch();
  $stmt->close();

  // Handle form submission
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $firstname = sanitizeInput($_POST["firstname"]);
    $lastname = sanitizeInput($_POST["lastname"]);
    $email = sanitizeInput($_POST["email"]);
    $role = sanitizeInput($_POST["role"]);
    $profile_info = sanitizeInput($_POST["profile_info"]);
    $picture = $current_picture; // Default to current picture

    if (empty($firstname) || empty($lastname) || empty($email) || empty($role)) {
      $errors[] = "All fields except profile info are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Invalid email format.";
    } else {
      // Handle file upload if a new file is uploaded
      if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "../uploads/";
        $file_name = basename($_FILES['profile_picture']['name']);
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $target_file = $target_dir . uniqid() . "." . $file_type;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file type
        if (in_array($file_type, $allowed_types)) {
          if ($_FILES['profile_picture']['size'] <= 104857600) { // Limit file size to 100MB
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
              $picture = basename($target_file); // Update picture name
            } else {
              $errors[] = "There was an error uploading the file.";
            }
          } else {
            $errors[] = "File size exceeds the 100MB limit.";
          }
        } else {
          $errors[] = "Invalid file type. Allowed types: jpg, jpeg, png, gif.";
        }
      }

      // Update the user in the database
      $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, role = ?, profile_info = ?, picture = ? WHERE id = ?");
      $stmt->bind_param("ssssssi", $firstname, $lastname, $email, $role, $profile_info, $picture, $user_id);

      if ($stmt->execute()) {
        $success_message = "User updated successfully.";
      } else {
        $errors[] = "Failed to update user. Please try again.";
      }
      $stmt->close();
    }
  }

  // Display the form with the user's data
?>
  <?php include('../components/navbar.php'); ?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/css/style.css">
  </head>

  <body>
    <h2>Edit User</h2>

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

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $user_id; ?>" method="post" enctype="multipart/form-data">
      <label for="firstname">First Name:</label><br>
      <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required><br><br>

      <label for="lastname">Last Name:</label><br>
      <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required><br><br>

      <label for="email">Email:</label><br>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br>

      <label for="role">Role:</label><br>
      <select id="role" name="role" required>
        <option value="student" <?php echo $role === 'student' ? 'selected' : ''; ?>>Student</option>
        <option value="trainer" <?php echo $role === 'trainer' ? 'selected' : ''; ?>>Trainer</option>
        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
      </select><br><br>

      <label for="profile_info">Profile Info:</label><br>
      <textarea id="profile_info" name="profile_info"><?php echo htmlspecialchars($profile_info); ?></textarea><br><br>

      <label for="profile_picture">Profile Picture:</label><br>
      <?php if (!empty($current_picture)): ?>
        <img src="../uploads/<?php echo htmlspecialchars($current_picture); ?>" alt="Profile Picture" style="width:100px;height:100px;"><br>
      <?php endif; ?>
      <input type="file" id="profile_picture" name="profile_picture" accept="image/*"><br><br>

      <input type="submit" value="Update User">
    </form>

    <p><a href="manage_users.php">Back to Manage Users</a></p>
    <?php include '../components/footer.php'; ?>
  </body>

  </html>
<?php
}
$conn->close();
?>