<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$student_id = $_SESSION['user_id'];
$review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;
$rating = $review_text = "";
$errors = [];
$success_message = "";

// Fetch the existing review details
if ($review_id > 0) {
  $stmt = $conn->prepare("SELECT rating, review_text FROM reviews WHERE id = ? AND student_id = ?");
  $stmt->bind_param("ii", $review_id, $student_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $rating = $row['rating'];
    $review_text = $row['review_text'];
  } else {
    $errors[] = "Review not found or you don't have permission to edit this review.";
  }
  $stmt->close();
} else {
  $errors[] = "No review selected for editing.";
}

// Handle form submission to update the review
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_review'])) {
  $new_rating = intval($_POST['rating']);
  $new_review_text = htmlspecialchars(trim($_POST['review_text']));

  if (empty($new_rating) || $new_rating < 1 || $new_rating > 5 || empty($new_review_text)) {
    $errors[] = "Please provide a valid rating (between 1 and 5) and a review text.";
  } else {
    // Update the review in the database
    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, review_text = ? WHERE id = ? AND student_id = ?");
    $stmt->bind_param("isii", $new_rating, $new_review_text, $review_id, $student_id);

    if ($stmt->execute()) {
      $success_message = "Your review has been updated successfully!";
      $rating = $new_rating;
      $review_text = $new_review_text;
    } else {
      $errors[] = "An error occurred while updating your review. Please try again.";
    }
    $stmt->close();
  }
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
  <title>Edit Review</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <h1>Edit Review</h1>

  <?php if ($success_message): ?>
    <p style="color:green;"><?php echo $success_message; ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($review_id > 0 && empty($errors)): ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?review_id=' . $review_id; ?>" method="post">
      <label for="rating">Rating (1-5):</label><br>
      <input type="number" id="rating" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($rating); ?>" required><br><br>

      <label for="review_text">Review:</label><br>
      <textarea id="review_text" name="review_text" required><?php echo htmlspecialchars($review_text); ?></textarea><br><br>

      <input type="submit" name="update_review" value="Update Review">
    </form>
  <?php endif; ?>

  <p><a href="my_reviews.php">Back to My Reviews</a></p>
  <?php
  include '../components/footer.php';  // Adjust the path if necessary
  ?>
</body>

</html>