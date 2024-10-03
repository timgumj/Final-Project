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
$services = [];
$booked_services = [];
$not_booked_services = [];
$user_id = $_SESSION['user_id'];
$success_message = "";
$errors = [];

// Handle booking request or cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['service_id']) && isset($_POST['action'])) {
    $service_id = intval($_POST['service_id']);
    $user_id = $_SESSION['user_id'];

    if ($_POST['action'] === 'book') {
      // Check if the student has already booked the service
      $query_check_booking = "SELECT id FROM bookings WHERE service_id = ? AND student_id = ? AND status != 'canceled'";
      $stmt = $conn->prepare($query_check_booking);
      $stmt->bind_param("ii", $service_id, $user_id);
      $stmt->execute();
      $stmt->store_result();

      if ($stmt->num_rows === 0) {
        // Create a new booking with status 'pending'
        $query_book = "INSERT INTO bookings (service_id, student_id, status) VALUES (?, ?, 'pending')";
        $stmt_book = $conn->prepare($query_book);
        $stmt_book->bind_param("ii", $service_id, $user_id);

        if ($stmt_book->execute()) {
          $success_message = "Service booked successfully! Waiting for trainer's confirmation.";
        } else {
          $errors[] = "Failed to book the service. Please try again.";
        }

        $stmt_book->close();
      } else {
        $errors[] = "You have already booked this service.";
      }

      $stmt->close();
    } elseif ($_POST['action'] === 'cancel') {
      // Set cancellation as pending
      $query_set_pending = "UPDATE bookings SET cancellation_pending = 1 WHERE service_id = ? AND student_id = ?";
      $stmt_pending = $conn->prepare($query_set_pending);
      $stmt_pending->bind_param("ii", $service_id, $user_id);

      if ($stmt_pending->execute()) {
        $success_message = "Cancellation request sent. Waiting for trainer's confirmation.";
      } else {
        $errors[] = "Failed to request cancellation. Please try again.";
      }

      $stmt_pending->close();
    }
  }
}

// Fetch all available tutoring services
$query = "
    SELECT 
        ts.id, 
        ts.description,
        ts.short_description,
        s.name AS subject_name, 
        u.name AS university_name, 
        t.firstname AS trainer_firstname, 
        t.lastname AS trainer_lastname, 
        t.picture AS trainer_picture,  
        ts.price, 
        ts.available_slots, 
        (SELECT COUNT(*) FROM bookings WHERE service_id = ts.id AND status = 'confirmed') AS confirmed_booked_slots,
        (SELECT COUNT(*) FROM bookings WHERE service_id = ts.id AND status = 'pending') AS pending_booked_slots
    FROM tutoring_services ts
    JOIN subjects s ON ts.subject_id = s.id
    JOIN universities u ON ts.university_id = u.id
    JOIN users t ON ts.trainer_id = t.id
    WHERE ts.is_available = 1
";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
  $services[] = $row;
}

// Fetch services the student has already booked
$query_booked = "
    SELECT 
        ts.id, 
        s.name AS subject_name, 
        u.name AS university_name, 
        t.firstname AS trainer_firstname, 
        t.lastname AS trainer_lastname, 
        t.picture AS trainer_picture,  
        ts.price, 
        b.status,
        b.cancellation_pending,
        ts.available_slots,
        (SELECT COUNT(*) FROM bookings WHERE service_id = ts.id AND status = 'confirmed') AS confirmed_booked_slots
    FROM bookings b
    JOIN tutoring_services ts ON b.service_id = ts.id
    JOIN subjects s ON ts.subject_id = s.id
    JOIN universities u ON ts.university_id = u.id
    JOIN users t ON ts.trainer_id = t.id
    WHERE b.student_id = ? AND b.status != 'canceled'
";
$stmt = $conn->prepare($query_booked);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_booked = $stmt->get_result();

while ($row_booked = $result_booked->fetch_assoc()) {
  $booked_services[] = $row_booked;
}

// Determine the services not yet booked by the student
$booked_service_ids = array_column($booked_services, 'id');
foreach ($services as $service) {
  if (!in_array($service['id'], $booked_service_ids)) {
    $not_booked_services[] = $service;
  }
}

$stmt->close();
$conn->close();
?>

<?php include('../components/navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Book a Service</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .content-container {
      padding-left: 15px;
      padding-right: 15px;
    }

    .box {
      border: 1px solid #ccc;
      padding: 20px;
      border-radius: 5px;
      background-color: #f9f9f9;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .booked-box {
      border: 1px solid #000;
      padding: 20px;
      border-radius: 5px;
      background-color: #000;
      color: #fff;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .service-box img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 15px;
    }

    .service-details {
      display: flex;
      align-items: center;
    }

    .service-details div {
      margin-left: 15px;
    }

    .booking-form {
      margin-bottom: 40px;
    }

    .search-bar {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-bar input[type="text"] {
      width: 100%;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 5px;
      margin-bottom: 10px;
    }

    .service-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }

    @media (min-width: 768px) {
      .service-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 767px) {
      .service-grid {
        grid-template-columns: 1fr;
      }
    }

    .book-now-button {
      background-color: #000;
      color: #fff;
      border: none;
      padding: 10px 20px;
      cursor: pointer;
      border-radius: 5px;
      text-align: center;
      display: inline-block;
      margin-top: 10px;
    }

    .book-now-button:hover {
      background-color: #333;
    }

    .cancel-button {
      background-color: #ff0000;
      color: #fff;
      border: none;
      padding: 10px 20px;
      cursor: pointer;
      border-radius: 5px;
      text-align: center;
      display: inline-block;
      margin-top: 10px;
    }

    .cancel-button:hover {
      background-color: #cc0000;
    }

    .pending-cancellation {
      color: orange;
      font-weight: bold;
    }

    .pending-booking {
      color: orange;
      font-weight: bold;
    }

    .booked-text {
      color: red;
      font-weight: bold;
    }

    .summary-button {
      background-color: transparent;
      border: none;
      color: black;
      text-decoration: none;
      border-bottom: 1px solid black;
      cursor: pointer;
      margin-top: 20px;
      /* Pushed down a bit */
      display: block;
    }

    .summary-button:hover {
      color: red;
      border-bottom: 1px solid red;
    }

    .booked-summary-button {
      color: white;
      border-bottom: 1px solid white;
    }

    .summary-content {
      display: none;
      margin-top: 10px;
    }

    .capacity-text {
      font-weight: bold;
      margin-top: 10px;
    }

    .full-capacity {
      color: red;
    }
  </style>
</head>

<body>
  <br>
  <div class="content-container">
    <h1>Book a Tutoring Service</h1>

    <?php
    if (!empty($success_message)) {
      echo "<p style='color:green;'>$success_message</p>";
    }

    if (!empty($errors)) {
      foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
      }
    }
    ?>

    <div class="search-bar">
      <input type="text" id="search" placeholder="Search by subject, university, or tutor">
    </div>

    <div id="service-grid" class="service-grid">
      <?php foreach ($services as $service): ?>
        <div class="box service-box"
          data-status="<?php echo in_array($service['id'], $booked_service_ids) ? 'booked' : 'available'; ?>"
          data-subject="<?php echo strtolower(htmlspecialchars($service['subject_name'])); ?>"
          data-university="<?php echo strtolower(htmlspecialchars($service['university_name'])); ?>"
          data-tutor="<?php echo strtolower(htmlspecialchars($service['trainer_firstname'] . ' ' . $service['trainer_lastname'])); ?>"
          style="<?php echo in_array($service['id'], $booked_service_ids) ? 'background-color: black; color: white;' : ''; ?>">
          <div class="service-details">
            <img src="../uploads/<?php echo htmlspecialchars($service['trainer_picture']); ?>"
              alt="Trainer Picture">
            <div>
              <strong><?php echo htmlspecialchars($service['subject_name']); ?></strong> - <?php echo htmlspecialchars($service['university_name']); ?>
              <br>
              Tutor: <?php echo htmlspecialchars($service['trainer_firstname'] . ' ' . $service['trainer_lastname']); ?>
              <br>
              Amount: $<?php echo htmlspecialchars($service['price']); ?>
              <br>
              <p class="capacity-text">
                <?php
                if ($service['confirmed_booked_slots'] >= $service['available_slots']) {
                  echo "<span class='full-capacity'>Full Capacity</span>";
                } else if ($service['pending_booked_slots'] > 0) {
                  echo "Pending";
                } else {
                  $places_left = $service['available_slots'] - $service['confirmed_booked_slots'];
                  echo "$places_left places left";
                }
                ?>
              </p>
              <button
                class="summary-button <?php echo in_array($service['id'], $booked_service_ids) ? 'booked-summary-button' : ''; ?>"
                onclick="toggleSummary(<?php echo $service['id']; ?>)">
                Course Summary
              </button>
              <div id="summary-<?php echo $service['id']; ?>" class="summary-content">
                <?php echo htmlspecialchars($service['short_description']); ?> <!-- Display the short description -->
              </div>
              <?php if (in_array($service['id'], $booked_service_ids)): ?>
                <?php
                $current_booking = $booked_services[array_search($service['id'], array_column($booked_services, 'id'))];
                $cancellationPending = isset($current_booking['cancellation_pending']) ? $current_booking['cancellation_pending'] : 0;
                $bookingStatus = isset($current_booking['status']) ? $current_booking['status'] : '';
                ?>
                <?php if ($cancellationPending): ?>
                  <br><span class="pending-cancellation">Cancellation Pending</span>
                <?php elseif ($bookingStatus === 'pending'): ?>
                  <br><span class="pending-booking">Pending</span>
                <?php elseif ($bookingStatus === 'completed'): ?>
                  <br><span class="booked-text">Course completed</span>
                  <a href="/students/my_reviews.php">Review</a>
                <?php else: ?>
                  <br><span class="booked-text">BOOKED</span>
                  <form action="book_service.php" method="post">
                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="cancel-button">Cancel Booking</button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <form action="book_service.php" method="post">
                  <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                  <input type="hidden" name="action" value="book">
                  <button type="submit" class="book-now-button">Book Now</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <a href="dashboard.php" class="back-to-dashboard">Back to Dashboard</a>
  </div>
  <br>
  <br>

  <script>
    function toggleSummary(serviceId) {
      const summaryElement = document.getElementById('summary-' + serviceId);
      if (summaryElement.style.display === 'none' || summaryElement.style.display === '') {
        summaryElement.style.display = 'block';
      } else {
        summaryElement.style.display = 'none';
      }
    }
  </script>

  <?php include '../components/footer.php'; ?>
</body>

</html>