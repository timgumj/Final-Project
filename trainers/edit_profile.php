<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'trainer' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
  header("Location: ../pages/login.php");
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
    body {
      font-family: Arial, sans-serif;
    }

    .container {
      max-width: 90%;
      /* Nearly full width */
      margin: 20px auto;
      /* Centered with reduced margins */
      padding: 20px;
      background-color: #f9f9f9;
      border: 1px solid #ccc;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
    }

    label {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    textarea {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    textarea {
      height: 100px;
    }

    input[type="file"] {
      margin-bottom: 15px;
    }

    input[type="submit"] {
      width: 100%;
      padding: 10px;
      border: none;
      border-radius: 5px;
      background-color: #000;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }

    input[type="submit"]:hover {
      background-color: #333;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: black;
      text-decoration: none;
      border-bottom: 1px solid red;
    }

    .back-link:hover {
      color: red;
      border-bottom: 1px solid red;
    }
  </style>
</head>

<body>
  <div class="container">
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
      <label for="firstname">First Name:</label>
      <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>

      <label for="lastname">Last Name:</label>
      <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>

      <label for="email">Email:</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

      <label for="old_password">Old Password:</label>
      <input type="password" id="old_password" name="old_password">

      <label for="new_password">New Password:</label>
      <input type="password" id="new_password" name="new_password">

      <label for="profile_info">Profile Info:</label>
      <textarea id="profile_info" name="profile_info"><?php echo htmlspecialchars($profile_info); ?></textarea>

      <label for="profile_picture">Profile Picture:</label>
      <input type="file" id="profile_picture" name="profile_picture" accept="image/*">

      <input type="submit" value="Update Profile">
    </form>

    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
  </div>
  <br>

  <?php include '../components/footer.php'; ?>
</body>

</html>