<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  // If not logged in or not an admin, redirect to the login page
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Initialize variables
$bookings = [];
$errors = [];
$success_message = "";

// Handle form submission to update booking status or delete a booking
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = htmlspecialchars(trim($_POST['status']));

    if (empty($booking_id) || empty($new_status)) {
      $errors[] = "Booking ID and status are required.";
    } else {
      // Update the booking status in the database
      $stmt = $conn->prepare("UPDATE bookings SET status = ?, cancellation_pending = 0 WHERE id = ?");
      $stmt->bind_param("si", $new_status, $booking_id);

      if ($stmt->execute()) {
        if ($new_status == 'canceled') {
          // Increase available slots if the booking is canceled
          $stmt_slots = $conn->prepare("UPDATE tutoring_services SET available_slots = available_slots + 1 WHERE id = (SELECT service_id FROM bookings WHERE id = ?)");
          $stmt_slots->bind_param("i", $booking_id);
          $stmt_slots->execute();
          $stmt_slots->close();
        }
        $success_message = "Booking status updated successfully!";
      } else {
        $errors[] = "An error occurred while updating the booking status. Please try again.";
      }
      $stmt->close();
    }
  } elseif (isset($_POST['delete_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    if ($booking_id) {
      // Delete the booking from the database
      $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
      $stmt->bind_param("i", $booking_id);

      if ($stmt->execute()) {
        $success_message = "Booking deleted successfully!";
      } else {
        $errors[] = "An error occurred while deleting the booking. Please try again.";
      }
      $stmt->close();
    } else {
      $errors[] = "Invalid booking ID.";
    }
  }
}

// Fetch all bookings with their details for the admin
$sql = "
    SELECT 
        b.id, 
        b.booking_date, 
        b.time_slot, 
        b.status, 
        b.cancellation_pending, /* Include the cancellation_pending status */
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
    ORDER BY b.booking_date DESC
";
$stmt = $conn->prepare($sql);
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
  <title>Manage Bookings</title>
  <link rel="stylesheet" href="../assets/css/style.css"> <!-- Adjust the path if necessary -->
</head>

<body>
  <h1>Manage Bookings</h1>

  <?php if (!empty($success_message)): ?>
    <p style="color:green;"><?php echo $success_message; ?></p>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $error): ?>
      <p style="color:red;"><?php echo $error; ?></p>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (count($bookings) > 0): ?>
    <table border="1" cellpadding="10">
      <thead>
        <tr>
          <th>Service Name</th>
          <th>Student Name</th>
          <th>Booking Date</th>
          <th>Time Slot</th>
          <th>Status</th>
          <th>Rating</th>
          <th>Review Text</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $booking): ?>
          <tr>
            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
            <td><?php echo htmlspecialchars($booking['student_firstname'] . ' ' . $booking['student_lastname']); ?></td>
            <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
            <td><?php echo htmlspecialchars($booking['time_slot']); ?></td>
            <td><?php echo htmlspecialchars($booking['status']); ?></td>
            <td><?php echo isset($booking['rating']) ? $booking['rating'] . ' / 5' : 'No rating yet'; ?></td>
            <td><?php echo htmlspecialchars($booking['review_text'] ?? 'No review text provided'); ?></td>
            <td>
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
              <?php endif; ?>
              <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" style="margin-top: 5px;">
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                <input type="submit" name="delete_booking" value="Delete" onclick="return confirm('Are you sure you want to delete this booking?');">
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No bookings found.</p>
  <?php endif; ?>

  <p><a href="dashboard.php">Back to Dashboard</a></p>
  <br>
  <br>
  <?php
  include '../components/footer.php';  // Adjust the path if necessary
  ?>
</body>

</html>