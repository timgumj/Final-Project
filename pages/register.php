<?php
require_once '../config/dbconnection.php';

// Function to sanitize user input
function sanitizeInput($data)
{
  return htmlspecialchars(stripslashes(trim($data)));
}

// Initialize variables
$firstname = $lastname = $email = $password = $profile_info = "";
$errors = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitize and validate inputs
  $firstname = sanitizeInput($_POST["firstname"]);
  $lastname = sanitizeInput($_POST["lastname"]);
  $email = sanitizeInput($_POST["email"]);
  $password = sanitizeInput($_POST["password"]);
  $profile_info = sanitizeInput($_POST["profile_info"]);
  $picture = 'avatar.png'; // Default picture

  if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
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
      $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, role, picture, profile_info) VALUES (?, ?, ?, ?, 'student', ?, ?)");
      $stmt->bind_param("ssssss", $firstname, $lastname, $email, $password, $picture, $profile_info);

      if ($stmt->execute()) {
        // Registration successful, redirect to login page
        header("Location: login.php");
        exit();
      } else {
        $errors[] = "Registration failed. Please try again.";
      }
      $stmt->close();
    }
  }
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Registration</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
      color: #333;
    }

    header {
      background: url('https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?q=80&w=2349&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center/cover;
      color: white;
      text-align: center;
      padding: 100px 20px;
    }

    header h1 {
      font-size: 3em;
      margin: 0;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
    }

    main {
      max-width: 1000px;
      margin: 20px auto;
      background: #fff;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    form {
      max-width: 90%;
      /* Nearly full width */
      margin: 0 auto;
    }

    form label {
      display: block;
      margin-bottom: 10px;
      font-weight: bold;
    }

    form input[type="text"],
    form input[type="email"],
    form input[type="password"],
    form textarea,
    form input[type="file"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    form input[type="submit"] {
      background-color: #007bff;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1em;
    }

    form input[type="submit"]:hover {
      background-color: #0056b3;
    }

    .error {
      color: red;
      margin-top: 20px;
    }
  </style>
</head>

<body>
  <?php include('../components/navbar.php'); ?>

  <main>
    <h2>Register</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
      <label for="firstname">First Name:</label>
      <input type="text" id="firstname" name="firstname" required>

      <label for="lastname">Last Name:</label>
      <input type="text" id="lastname" name="lastname" required>

      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required>

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>

      <label for="profile_info">Profile Info:</label>
      <textarea id="profile_info" name="profile_info"></textarea>

      <label for="profile_picture">Profile Picture:</label>
      <input type="file" id="profile_picture" name="profile_picture" accept="image/*">

      <input type="submit" value="Register">
    </form>

    <?php
    // Display errors
    if (!empty($errors)) {
      echo '<div class="error">';
      foreach ($errors as $error) {
        echo "<p>$error</p>";
      }
      echo '</div>';
    }
    ?>
  </main>

  <?php include('../components/footer.php'); ?>
</body>

</html>