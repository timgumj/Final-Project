<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$upload_dir = '../uploads/';
$default_avatar = 'avatar.png'; // Default avatar image
$allowed_image_types = ['jpg', 'jpeg', 'png', 'gif'];
$allowed_file_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
$errors = [];
$success_message = "";

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file_upload"])) {
  $file = $_FILES["file_upload"];
  $file_name = basename($file["name"]);
  $file_size = $file["size"];
  $file_tmp = $file["tmp_name"];
  $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

  // Validate file type
  if (in_array($file_type, $allowed_image_types)) {
    $target_dir = $upload_dir; // Upload to uploads directory for images
  } elseif (in_array($file_type, $allowed_file_types)) {
    $target_dir = '../files/'; // Upload to files directory for other files
  } else {
    $errors[] = "Invalid file type. Allowed types: " . implode(', ', array_merge($allowed_image_types, $allowed_file_types));
  }

  // Check file size (max 10MB)
  if ($file_size > 10485760) {
    $errors[] = "File size exceeds the 10MB limit.";
  }

  // Check if there are no errors before proceeding
  if (empty($errors)) {
    // Generate a unique file name to prevent overwriting
    $unique_name = uniqid() . "_" . $file_name;
    $target_file = $target_dir . $unique_name;

    // Move the file to the target directory
    if (move_uploaded_file($file_tmp, $target_file)) {
      // Update the user's profile picture if it's an image
      if (in_array($file_type, $allowed_image_types)) {
        $stmt = $conn->prepare("UPDATE users SET picture = ? WHERE id = ?");
        $stmt->bind_param("si", $unique_name, $_SESSION['user_id']);
        if ($stmt->execute()) {
          $success_message = "Profile picture updated successfully!";
        } else {
          $errors[] = "Failed to update profile picture in the database.";
        }
        $stmt->close();
      } else {
        $success_message = "File uploaded successfully!";
      }
    } else {
      $errors[] = "There was an error uploading your file. Please try again.";
    }
  }
}

// If no image was uploaded, set the default avatar
if (empty($_FILES["file_upload"]["name"])) {
  $stmt = $conn->prepare("UPDATE users SET picture = ? WHERE id = ?");
  $stmt->bind_param("si", $default_avatar, $_SESSION['user_id']);
  $stmt->execute();
  $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>File Upload</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <h2>Upload a File</h2>

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

  <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
    <label for="file_upload">Choose a file:</label><br>
    <input type="file" id="file_upload" name="file_upload"><br><br>

    <input type="submit" value="Upload File">
  </form>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>

</html>