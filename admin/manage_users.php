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
$users = [];
$errors = [];
$success_message = "";

// Handle user deletion
if (isset($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);
  $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  if ($stmt->execute()) {
    $success_message = "User deleted successfully.";
  } else {
    $errors[] = "An error occurred while deleting the user.";
  }
  $stmt->close();
}

// Fetch all users
$query = "SELECT id, firstname, lastname, email, role FROM users ORDER BY lastname ASC, firstname ASC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
  $users[] = $row;
}

$conn->close();
?>
<?php
include('../components/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Users</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Manage Users</h1>

  <?php if ($success_message): ?>
    <p style="color:green;"><?php echo $success_message; ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (count($users) > 0): ?>
    <table border="1" cellpadding="10">
      <thead>
        <tr>
          <th>First Name</th>
          <th>Last Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?php echo htmlspecialchars($user['firstname']); ?></td>
            <td><?php echo htmlspecialchars($user['lastname']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['role']); ?></td>
            <td>
              <a href="edit_user.php?id=<?php echo $user['id']; ?>">Edit</a> |
              <a href="manage_users.php?delete_id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No users found.</p>
  <?php endif; ?>

  <p><a href="add_user.php">Add New User</a></p>
  <p><a href="dashboard.php">Back to Dashboard</a></p>
  <?php include '../components/footer.php'; ?>
</body>

</html>