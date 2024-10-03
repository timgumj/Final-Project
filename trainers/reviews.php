<?php
session_start();

// Check if the user is logged in and has the 'trainer' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$trainer_id = $_SESSION['user_id'];
$reviews = [];
$errors = [];

// Fetch the reviews for the courses assigned to the logged-in trainer
$query = "
    SELECT 
        r.review_text, 
        r.rating, 
        r.created_at, 
        ts.description AS service_name, 
        s.name AS subject_name
    FROM reviews r
    JOIN tutoring_services ts ON r.service_id = ts.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.trainer_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $reviews[] = $row;
}

$stmt->close();
$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Reviews</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .review-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    .review-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }

    @media (min-width: 768px) {
      .review-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (min-width: 992px) {
      .review-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    .review-box {
      background-color: #f9f9f9;
      border: 1px solid #ddd;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .review-box .service-name,
    .review-box .subject-name {
      font-weight: bold;
      font-size: 1.2em;
      margin-bottom: 10px;
    }

    .review-box .review-text {
      font-size: 1em;
      margin-bottom: 10px;
    }

    .review-box .review-rating {
      color: #ff9800;
      margin-bottom: 10px;
    }

    .review-box .review-timestamp {
      font-size: 0.8em;
      color: #888;
    }
  </style>
</head>

<body>
  <div class="review-container">
    <h1>Student Reviews</h1>
    <?php if (count($reviews) > 0): ?>
      <div class="review-grid">
        <?php foreach ($reviews as $review): ?>
          <div class="review-box">
            <div class="subject-name"><?php echo htmlspecialchars($review['subject_name']); ?></div>
            <div class="service-name"><?php echo htmlspecialchars($review['service_name']); ?></div>
            <div class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></div>
            <div class="review-rating">
              <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                &#9733; <!-- Star icon -->
              <?php endfor; ?>
              <?php for ($i = $review['rating']; $i < 5; $i++): ?>
                &#9734; <!-- Empty star icon -->
              <?php endfor; ?>
            </div>
            <div class="review-timestamp"><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($review['created_at']))); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No reviews found for your courses.</p>
    <?php endif; ?>
  </div>

  <div class="back-to-dashboard">
    <p><a href="dashboard.php">Back to Dashboard</a></p>
  </div>
  <br>
  <?php include '../components/footer.php'; ?>
</body>

</html>