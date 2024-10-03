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
$reviews = [];
$errors = [];
$success_message = "";

// Handle review deletion
if (isset($_GET['delete_id'])) {
  $delete_id = intval($_GET['delete_id']);
  $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  if ($stmt->execute()) {
    $success_message = "Review deleted successfully.";
  } else {
    $errors[] = "An error occurred while deleting the review.";
  }
  $stmt->close();
}

// Fetch all reviews
$query = "
    SELECT r.id, r.rating, r.review_text, r.created_at, s.name AS service_name, u.firstname AS student_firstname, u.lastname AS student_lastname
    FROM reviews r
    JOIN services s ON r.service_id = s.id
    JOIN users u ON r.student_id = u.id
    ORDER BY r.created_at DESC
";


$conn->close();
?>
<?php
include('../components/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Reviews</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Manage Reviews</h1>

  <?php if ($success_message): ?>
    <p style="color:green;"><?php echo $success_message; ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (count($reviews) > 0): ?>
    <table border="1" cellpadding="10">
      <thead>
        <tr>
          <th>Service Name</th>
          <th>Student Name</th>
          <th>Rating</th>
          <th>Review Text</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reviews as $review): ?>
          <tr>
            <td><?php echo htmlspecialchars($review['service_name']); ?></td>
            <td><?php echo htmlspecialchars($review['student_firstname'] . ' ' . $review['student_lastname']); ?></td>
            <td><?php echo htmlspecialchars($review['rating']); ?></td>
            <td><?php echo htmlspecialchars($review['review_text']); ?></td>
            <td><?php echo htmlspecialchars($review['created_at']); ?></td>
            <td>
              <a href="edit_review.php?id=<?php echo $review['id']; ?>">Edit</a> |
              <a href="manage_reviews.php?delete_id=<?php echo $review['id']; ?>" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No reviews found.</p>
  <?php endif; ?>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
  <?php include '../components/footer.php'; ?>
</body>

</html>