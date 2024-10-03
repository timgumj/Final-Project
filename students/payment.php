<?php
session_start();

// Check if the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: ../pages/login.php");
  exit();
}

// Include the database connection
require_once '../config/dbconnection.php';

// Check if the service_id is provided
if (!isset($_GET['service_id'])) {
  header("Location: book_service.php");
  exit();
}

$service_id = intval($_GET['service_id']);
$user_id = $_SESSION['user_id'];

// Fetch the service details
$stmt = $conn->prepare("
    SELECT 
        ts.id, 
        s.name AS subject_name, 
        u.name AS university_name, 
        t.firstname AS trainer_firstname, 
        t.lastname AS trainer_lastname, 
        ts.price 
    FROM tutoring_services ts
    JOIN subjects s ON ts.subject_id = s.id
    JOIN universities u ON ts.university_id = u.id
    JOIN users t ON ts.trainer_id = t.id
    WHERE ts.id = ?
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($service_id, $subject_name, $university_name, $trainer_firstname, $trainer_lastname, $price);
$stmt->fetch();
$stmt->close();

// Handle payment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay_now'])) {
  // Simulate a successful payment
  $stmt = $conn->prepare("INSERT INTO payments (student_id, service_id, amount, payment_status) VALUES (?, ?, ?, 'completed')");
  $stmt->bind_param("iid", $user_id, $service_id, $price);

  if ($stmt->execute()) {
    // Update booking status to confirmed
    $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE student_id = ? AND service_id = ?");
    $stmt->bind_param("ii", $user_id, $service_id);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php?payment=success");
    exit();
  } else {
    $error_message = "An error occurred during payment. Please try again.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Payment - <?php echo htmlspecialchars($subject_name); ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .payment-container {
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      background-color: #f9f9f9;
      max-width: 600px;
      margin: 50px auto;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .payment-details {
      margin-bottom: 20px;
    }

    .payment-details h2 {
      margin-bottom: 10px;
    }

    .payment-details p {
      margin: 5px 0;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="number"],
    .form-group select {
      width: 100%;
      padding: 8px;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .payment-button {
      display: block;
      width: 100%;
      padding: 10px;
      background-color: #0070ba;
      color: #fff;
      text-align: center;
      text-decoration: none;
      border-radius: 5px;
      font-size: 18px;
      margin-top: 20px;
      border: none;
      cursor: pointer;
    }

    .payment-button:hover {
      background-color: #005b94;
    }

    .error-message {
      color: red;
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <div class="payment-container">
    <div class="payment-details">
      <h2>Payment for <?php echo htmlspecialchars($subject_name); ?></h2>
      <p>University: <?php echo htmlspecialchars($university_name); ?></p>
      <p>Trainer: <?php echo htmlspecialchars($trainer_firstname . ' ' . $trainer_lastname); ?></p>
      <p>Amount: $<?php echo htmlspecialchars($price); ?></p>
    </div>

    <?php if (isset($error_message)): ?>
      <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?service_id=" . $service_id); ?>" method="post">
      <div class="form-group">
        <label for="card_number">Card Number</label>
        <input type="text" id="card_number" name="card_number" required placeholder="1234 5678 9012 3456">
      </div>
      <div class="form-group">
        <label for="expiry_date">Expiry Date</label>
        <input type="text" id="expiry_date" name="expiry_date" required placeholder="MM/YY">
      </div>
      <div class="form-group">
        <label for="cvv">CVV</label>
        <input type="number" id="cvv" name="cvv" required placeholder="123">
      </div>
      <div class="form-group">
        <label for="cardholder_name">Cardholder Name</label>
        <input type="text" id="cardholder_name" name="cardholder_name" required placeholder="John Doe">
      </div>
      <div class="form-group">
        <label for="billing_address">Billing Address</label>
        <input type="text" id="billing_address" name="billing_address" required placeholder="123 Main St">
      </div>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required placeholder="john.doe@example.com">
      </div>

      <input type="submit" name="pay_now" value="Pay Now" class="payment-button">
    </form>
  </div>
</body>

</html>