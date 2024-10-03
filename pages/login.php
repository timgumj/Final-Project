<?php
session_start(); // Start the session to access session variables

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$email = $password = "";
$errors = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitize and validate inputs
  $email = htmlspecialchars(trim($_POST["email"]));
  $password = htmlspecialchars(trim($_POST["password"]));

  if (empty($email) || empty($password)) {
    $errors[] = "Email and password are required.";
  } else {
    // Hash the input password
    $password = hash("sha256", $password);

    // Check if the email and password match an entry in the database
    $stmt = $conn->prepare("SELECT id, firstname, lastname, role FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      // Fetch user details
      $stmt->bind_result($id, $firstname, $lastname, $role);
      $stmt->fetch();

      // Store user information in session variables
      $_SESSION['user_id'] = $id;
      $_SESSION['firstname'] = $firstname;
      $_SESSION['lastname'] = $lastname;
      $_SESSION['role'] = $role;

      // Redirect based on user role
      if ($role == 'admin') {
        header("Location: ../admin/dashboard.php");
      } elseif ($role == 'trainer') {
        header("Location: ../trainers/dashboard.php"); // Corrected path for trainers
      } elseif ($role == 'student') {
        header("Location: ../students/dashboard.php");
      } else {
        // Handle unknown role
        $errors[] = "Unknown role.";
      }
      exit();
    } else {
      $errors[] = "Invalid email or password.";
    }
    $stmt->close();
  }
  $conn->close();
}
?>
<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }

    .login-container {
      width: 300px;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      background-color: #f9f9f9;
      margin: 100px auto;
      /* Centers the login form horizontally */
    }

    .login-container h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .login-container label {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
    }

    .login-container input[type="email"],
    .login-container input[type="password"] {
      width: 100%;
      padding: 8px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .login-container input[type="submit"] {
      width: 100%;
      padding: 10px;
      border: none;
      border-radius: 5px;
      background-color: #000;
      /* Black background */
      color: white;
      /* White text */
      font-weight: bold;
      cursor: pointer;
    }

    .login-container input[type="submit"]:hover {
      background-color: #333;
      /* Slightly lighter black on hover */
    }

    .login-container p {
      color: red;
      text-align: center;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <h2>Login</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required>

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>

      <input type="submit" value="Login">
    </form>

    <?php
    // Display errors
    if (!empty($errors)) {
      foreach ($errors as $error) {
        echo "<p>$error</p>";
      }
    }
    ?>
  </div>

  <?php include '../components/footer.php'; ?>
</body>

</html>