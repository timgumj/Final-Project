<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: ../login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$firstname = $lastname = $email = $old_password = $new_password = $profile_info = $profile_picture = "";
$errors = [];
$success_message = "";

// Fetch current user details
$stmt = $conn->prepare("SELECT firstname, lastname, email, profile_info, picture FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $email, $profile_info, $current_picture);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitize and validate inputs
  $firstname = htmlspecialchars(trim($_POST["firstname"]));
  $lastname = htmlspecialchars(trim($_POST["lastname"]));
  $email = htmlspecialchars(trim($_POST["email"]));
  $old_password = htmlspecialchars(trim($_POST["old_password"]));
  $new_password = htmlspecialchars(trim($_POST["new_password"]));
  $profile_info = htmlspecialchars(trim($_POST["profile_info"]));
  $profile_picture = $current_picture; // Default to current picture

  if (empty($firstname) || empty($lastname) || empty($email)) {
    $errors[] = "All fields except password are required.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
  } else {
    // Check if the user wants to change the password
    if (!empty($old_password) && !empty($new_password)) {
      $old_password_hash = hash("sha256", $old_password);
      // Verify old password
      $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
      $stmt->bind_param("i", $_SESSION['user_id']);
      $stmt->execute();
      $stmt->bind_result($stored_password);
      $stmt->fetch();
      if ($old_password_hash !== $stored_password) {
        $errors[] = "Old password is incorrect.";
      } else {
        // Update with the new password
        $new_password_hash = hash("sha256", $new_password);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
      }
    }

    // Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
      $target_dir = "../uploads/";
      $file_name = basename($_FILES['profile_picture']['name']);
      $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
      $target_file = $target_dir . uniqid() . "." . $file_type;

      $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

      if (in_array($file_type, $allowed_types)) {
        if ($_FILES['profile_picture']['size'] <= 104857600) { // Limit file size to 100MB
          if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            $profile_picture = basename($target_file); // Update picture name
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

    // Update the profile details
    $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, profile_info = ?, picture = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $firstname, $lastname, $email, $profile_info, $profile_picture, $_SESSION['user_id']);
    if ($stmt->execute()) {
      // Update session variables
      $_SESSION['firstname'] = $firstname;
      $_SESSION['lastname'] = $lastname;
      $_SESSION['email'] = $email;
      $_SESSION['picture'] = $profile_picture;
      $success_message = "Profile updated successfully.";
    } else {
      $errors[] = "An error occurred while updating the profile. Please try again.";
    }
    $stmt->close();
  }
}

$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .form-container {
      width: 100%;
      padding: 0 15px;
      /* Just a tiny bit of padding on the sides */
    }

    .form-container label {
      font-weight: bold;
    }

    .form-container input[type="text"],
    .form-container input[type="email"],
    .form-container input[type="password"],
    .form-container textarea,
    .form-container input[type="file"] {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      display: inline-block;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
    }

    .form-container input[type="submit"] {
      width: 100%;
      background-color: black;
      color: white;
      padding: 14px 20px;
      margin: 8px 0;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .form-container input[type="submit"]:hover {
      background-color: #333;
    }

    .form-container p {
      text-align: center;
    }

    .form-container p a {
      color: #4CAF50;
    }

    .form-container p a:hover {
      color: #45a049;
    }
  </style>
</head>

<body>
  <div class="form-container">
    <h2>Edit Profile</h2>

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

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
      <label for="firstname">First Name:</label><br>
      <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required><br>

      <label for="lastname">Last Name:</label><br>
      <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required><br>

      <label for="email">Email:</label><br>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br>

      <label for="old_password">Old Password:</label><br>
      <input type="password" id="old_password" name="old_password"><br>

      <label for="new_password">New Password:</label><br>
      <input type="password" id="new_password" name="new_password"><br>

      <label for="profile_info">Profile Info:</label><br>
      <textarea id="profile_info" name="profile_info"><?php echo htmlspecialchars($profile_info); ?></textarea><br>

      <label for="profile_picture">Profile Picture:</label><br>
      <input type="file" id="profile_picture" name="profile_picture" accept="image/*"><br>

      <input type="submit" value="Update Profile">
    </form>

    <p><a href="dashboard.php">Back to Dashboard</a></p>
  </div>

  <?php include '../components/footer.php'; ?>
</body>

</html>