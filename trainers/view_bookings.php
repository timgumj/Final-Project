<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'trainer' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
  // If not logged in or not a trainer, redirect to the login page
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$trainer_id = $_SESSION['user_id'];
$bookings = [];
$errors = [];
$success_message = "";

// Handle form submission to update booking status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
  $booking_id = intval($_POST['booking_id']);
  $new_status = htmlspecialchars(trim($_POST['status']));

  if (empty($booking_id) || empty($new_status)) {
    $errors[] = "Booking ID and status are required.";
  } else {
    // Get the service_id associated with this booking
    $stmt = $conn->prepare("SELECT service_id FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($service_id);
    $stmt->fetch();
    $stmt->close();

    if ($service_id) {
      // Update the status for all bookings under the same service_id
      $stmt_update = $conn->prepare("UPDATE bookings SET status = ?, cancellation_pending = 0 WHERE service_id = ?");
      $stmt_update->bind_param("si", $new_status, $service_id);

      if ($stmt_update->execute()) {
        if ($new_status == 'canceled') {
          // Increase available slots if the booking is canceled
          $stmt_slots = $conn->prepare("UPDATE tutoring_services SET available_slots = available_slots + 1 WHERE id = ?");
          $stmt_slots->bind_param("i", $service_id);
          $stmt_slots->execute();
          $stmt_slots->close();
        }
        $success_message = "Booking status updated successfully!";
      } else {
        $errors[] = "An error occurred while updating the booking status. Please try again.";
      }
      $stmt_update->close();
    } else {
      $errors[] = "Service ID not found for this booking.";
    }
  }
}

// Fetch bookings related to the trainer's services with ratings and reviews
$sql = "
    SELECT 
        b.id, 
        b.booking_date, 
        st.email AS student_email, 
        st.picture AS student_picture, 
        b.status, 
        b.cancellation_pending, 
        CONCAT(u.firstname, ' ', u.lastname, ' - ', un.name, ' - ', s.name) AS service_name, 
        st.firstname AS student_firstname, 
        st.lastname AS student_lastname,
        r.rating,
        r.review_text
    FROM bookings b
    JOIN tutoring_services ts ON b.service_id = ts.id
    JOIN users u ON ts.trainer_id = u.id
    JOIN universities un ON ts.university_id = un.id
    JOIN subjects s ON ts.subject_id = s.id
    JOIN users st ON b.student_id = st.id
    LEFT JOIN reviews r ON r.service_id = ts.id AND r.student_id = b.student_id
    WHERE ts.trainer_id = ?
    ORDER BY b.booking_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $bookings[] = $row;
}
$stmt->close();

$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <br>
  <title>Your Bookings</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
  <style>
    .booking-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      padding: 20px;
      justify-content: center;
    }

    .booking-box {
      background-color: #f9f9f9;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      padding: 20px;
      width: calc(33.333% - 40px);
      box-sizing: border-box;
      transition: transform 0.2s;
      text-align: left;
    }

    .booking-box:hover {
      transform: translateY(-5px);
    }

    .booking-box img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 15px;
    }

    .booking-box h2 {
      font-size: 18px;
      margin-bottom: 10px;
      font-weight: 500;
    }

    .booking-box p {
      margin: 5px 0;
    }

    .booking-box .actions {
      margin-top: 15px;
    }

    .booking-box .actions form {
      display: inline-block;
      margin: 0;
    }

    .booking-box .actions select,
    .booking-box .actions input[type="submit"] {
      margin-top: 10px;
      padding: 5px 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    .booking-box .actions input[type="submit"] {
      background-color: #000;
      color: #fff;
      border: none;
      cursor: pointer;
    }

    .booking-box .actions input[type="submit"]:hover {
      background-color: #333;
    }

    @media (max-width: 768px) {
      .booking-box {
        width: calc(50% - 20px);
      }
    }

    @media (max-width: 480px) {
      .booking-box {
        width: 100%;
      }
    }

    h1 {
      text-align: center;
      font-size: 24px;
      margin-bottom: 20px;
    }

    .back-to-dashboard {
      text-align: center;
      margin-top: 30px;
    }

    .back-to-dashboard a {
      color: black;
      text-decoration: none;
      border-bottom: 1px solid red;
      font-weight: bold;
      padding-bottom: 2px;
    }

    .back-to-dashboard a:hover {
      color: red;
      border-bottom: 1px solid black;
    }
  </style>
</head>

<body>
  <h1>Your Bookings</h1>

  <?php if (!empty($success_message)): ?>
    <p style="color:green;"><?php echo $success_message; ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (count($bookings) > 0): ?>
    <div class="booking-container">
      <?php foreach ($bookings as $booking): ?>
        <div class="booking-box">
          <img src="../uploads/<?php echo htmlspecialchars($booking['student_picture'] ?? 'default_profile.png'); ?>" alt="Student Picture">
          <h2><?php echo htmlspecialchars($booking['service_name']); ?></h2>
          <p><strong>Student:</strong> <?php echo htmlspecialchars($booking['student_firstname'] . ' ' . $booking['student_lastname']); ?></p>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['student_email']); ?></p>
          <p><strong>Status:</strong> <?php echo htmlspecialchars($booking['status']); ?></p>
          <p><strong>Rating:</strong> <?php echo isset($booking['rating']) ? $booking['rating'] . ' / 5' : 'No rating yet'; ?></p>
          <p><strong>Review:</strong> <?php echo htmlspecialchars($booking['review_text'] ?? 'No review text provided'); ?></p>
          <div class="actions">
            <?php if ($booking['cancellation_pending'] == 1): ?>
              <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                <select name="status" required>
                  <option value="">-- Select Status --</option>
                  <option value="canceled">Confirm Cancellation</option>
                  <option value="confirmed">Reject Cancellation</option>
                </select>
                <input type="submit" name="update_status" value="Update">
              </form>
            <?php elseif ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
              <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                <select name="status" required>
                  <option value="">-- Select Status --</option>
                  <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirm</option>
                  <option value="canceled" <?php echo $booking['status'] == 'canceled' ? 'selected' : ''; ?>>Cancel</option>
                  <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Complete</option>
                </select>
                <input type="submit" name="update_status" value="Update">
              </form>
            <?php else: ?>
              <p>No actions available</p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No bookings found.</p>
  <?php endif; ?>

  <div class="back-to-dashboard">
    <p><a href="dashboard.php">Back to Dashboard</a></p>
  </div>
  <br>
  <?php include '../components/footer.php'; ?>

</body>

</html>