<?php
require_once 'api_common.php';

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    // Fetch bookings, possibly by user ID or booking ID
    if (isset($_GET['id'])) {
      $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
      $stmt->bind_param("i", $_GET['id']);
    } else {
      $stmt = $conn->prepare("SELECT * FROM bookings");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    respond($bookings);
    break;

  case 'POST':
    // Create a new booking
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, booking_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $data['user_id'], $data['service_id'], $data['booking_date']);
    $stmt->execute();
    respond(["message" => "Booking created.", "booking_id" => $conn->insert_id], 201);
    break;

  case 'PUT':
    // Update a booking
    if (!isset($_GET['id'])) handleInvalidRequest();
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("UPDATE bookings SET service_id = ?, booking_date = ? WHERE id = ?");
    $stmt->bind_param("isi", $data['service_id'], $data['booking_date'], $_GET['id']);
    $stmt->execute();
    respond(["message" => "Booking updated."]);
    break;

  case 'DELETE':
    // Delete a booking
    if (!isset($_GET['id'])) handleInvalidRequest();
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    respond(["message" => "Booking deleted."]);
    break;

  default:
    handleInvalidRequest();
    break;
}
