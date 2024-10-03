<?php
session_start();

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$student_id = $_SESSION['user_id'];
$reviews = [];
$errors = [];
$success_message = "";

// Handle form submission for new or edited reviews
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
  $service_id = intval($_POST['service_id']);
  $rating = intval($_POST['rating']);
  $review_text = htmlspecialchars(trim($_POST['review_text']));

  if ($rating < 1 || $rating > 5) {
    $errors[] = "Please select a valid rating between 1 and 5 stars.";
  } elseif (empty($review_text)) {
    $errors[] = "Please provide a review text.";
  } else {
    // Check if the review already exists
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE student_id = ? AND service_id = ?");
    $stmt->bind_param("ii", $student_id, $service_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      // Update existing review
      $stmt->close();
      $stmt = $conn->prepare("UPDATE reviews SET rating = ?, review_text = ? WHERE student_id = ? AND service_id = ?");
      $stmt->bind_param("isii", $rating, $review_text, $student_id, $service_id);
      if ($stmt->execute()) {
        $success_message = "Your review has been updated successfully!";
      } else {
        $errors[] = "An error occurred while updating your review. Please try again.";
      }
    } else {
      // Insert new review
      $stmt->close();
      $stmt = $conn->prepare("INSERT INTO reviews (student_id, service_id, rating, review_text) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("iiis", $student_id, $service_id, $rating, $review_text);
      if ($stmt->execute()) {
        $success_message = "Your review has been submitted successfully!";
      } else {
        $errors[] = "An error occurred while submitting your review. Please try again.";
      }
    }
    $stmt->close();
  }
}

// Fetch the courses booked by the student along with their reviews
$query = "
    SELECT 
        ts.id AS service_id,
        CONCAT(t.firstname, ' ', t.lastname) AS trainer_name,
        s.name AS subject_name,
        r.rating,
        r.review_text,
        r.created_at,
        b.status AS booking_status
    FROM 
        bookings b
    JOIN 
        tutoring_services ts ON b.service_id = ts.id
    JOIN 
        users t ON ts.trainer_id = t.id
    JOIN 
        subjects s ON ts.subject_id = s.id
    LEFT JOIN 
        reviews r ON r.service_id = ts.id AND r.student_id = b.student_id
    WHERE 
        b.student_id = ?
    ORDER BY 
        r.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $reviews[] = $row;
}

$stmt->close();
$conn->close();
?>
<?php

include('../components/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>My Reviews</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    .content {
      margin: 0 auto;
      padding: 0 20px;
      max-width: 1200px;
    }

    .rating {
      color: gold;
      font-size: 16px;
    }

    .no-reviews {
      color: red;
      font-weight: bold;
    }

    .review-form {
      margin-top: 20px;
      border-top: 1px solid #ccc;
      padding-top: 10px;
    }

    .review-form label {
      display: block;
      margin-bottom: 5px;
    }

    .review-form textarea {
      width: 100%;
      height: 80px;
    }

    .review-form input[type="submit"] {
      margin-top: 10px;
    }

    .return-to-dashboard {
      margin-top: 30px;
      font-size: 18px;
      color: black;
      text-decoration: none;
      display: inline-block;
      border-bottom: 2px solid red;
    }

    .return-to-dashboard:hover {
      color: red;
      border-bottom-color: transparent;
    }

    @media (max-width: 768px) {
      .table-responsive {
        display: block;
        overflow-x: auto;
        width: 100%;
      }

      table {
        border-collapse: collapse;
        width: 100%;
        display: block;
      }

      table thead {
        display: none;
      }

      table tbody,
      table tr,
      table td {
        display: block;
        width: 100%;
      }

      table tr {
        margin-bottom: 15px;
      }

      table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
      }

      table td::before {
        content: attr(data-label);
        position: absolute;
        left: 0;
        width: 50%;
        padding-left: 15px;
        font-weight: bold;
        text-align: left;
      }
    }
  </style>
</head>

<body>
  <div class="content">
    <h1>My Reviews</h1>

    <?php if (!empty($success_message)): ?>
      <p style="color:green;"><?php echo $success_message; ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <?php foreach ($errors as $error): ?>
        <p style="color:red;"><?php echo $error; ?></p>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (count($reviews) > 0): ?>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Service Name</th>
              <th>Trainer Name</th>
              <th>Rating</th>
              <th>Review Text</th>
              <th>Timestamp</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reviews as $review): ?>
              <tr>
                <td data-label="Service Name"><?php echo htmlspecialchars($review['subject_name']); ?></td>
                <td data-label="Trainer Name"><?php echo htmlspecialchars($review['trainer_name']); ?></td>
                <td data-label="Rating">
                  <?php
                  if (isset($review['rating'])) {
                    for ($i = 0; $i < 5; $i++) {
                      echo ($i < $review['rating']) ? '★' : '☆';
                    }
                  } else {
                    echo "No rating yet";
                  }
                  ?>
                </td>
                <td data-label="Review Text"><?php echo htmlspecialchars($review['review_text'] ?? 'No review text provided'); ?></td>
                <td data-label="Timestamp"><?php echo htmlspecialchars($review['created_at'] ?? 'No review yet'); ?></td>
                <td data-label="Action">
                  <?php if ($review['booking_status'] === 'completed'): ?>
                    <!-- Display edit form if the booking is completed -->
                    <div class="review-form">
                      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="service_id" value="<?php echo $review['service_id']; ?>">
                        <label for="rating">Rating:</label>
                        <select name="rating" required>
                          <option value="5" <?php echo ($review['rating'] == 5) ? 'selected' : ''; ?>>5 Stars</option>
                          <option value="4" <?php echo ($review['rating'] == 4) ? 'selected' : ''; ?>>4 Stars</option>
                          <option value="3" <?php echo ($review['rating'] == 3) ? 'selected' : ''; ?>>3 Stars</option>
                          <option value="2" <?php echo ($review['rating'] == 2) ? 'selected' : ''; ?>>2 Stars</option>
                          <option value="1" <?php echo ($review['rating'] == 1) ? 'selected' : ''; ?>>1 Star</option>
                        </select>
                        <label for="review_text">Review Text:</label>
                        <textarea name="review_text" required><?php echo htmlspecialchars($review['review_text'] ?? ''); ?></textarea>
                        <input type="submit" name="submit_review" value="<?php echo isset($review['rating']) ? 'Update Review' : 'Submit Review'; ?>">
                      </form>
                    </div>
                  <?php else: ?>
                    <em>Course not completed yet</em>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="no-reviews">You have not booked any courses yet.</p>
    <?php endif; ?>

    <p><a href="dashboard.php" class="return-to-dashboard">Back to Dashboard</a></p>
  </div>

  <?php
  include '../components/footer.php';  // Adjust the path if necessary
  ?>
</body>

</html>