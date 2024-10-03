<?php
require_once 'api_common.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    // Fetch reviews, possibly by service ID or review ID
    if (isset($_GET['id'])) {
      $stmt = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
      $stmt->bind_param("i", $_GET['id']);
    } else {
      $stmt = $conn->prepare("SELECT * FROM reviews");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    respond($reviews);
    break;

  case 'POST':
    // Create a new review
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, service_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $data['user_id'], $data['service_id'], $data['rating'], $data['comment']);
    $stmt->execute();
    respond(["message" => "Review created.", "review_id" => $conn->insert_id], 201);
    break;

  case 'PUT':
    // Update a review
    if (!isset($_GET['id'])) handleInvalidRequest();
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
    $stmt->bind_param("isi", $data['rating'], $data['comment'], $_GET['id']);
    $stmt->execute();
    respond(["message" => "Review updated."]);
    break;

  case 'DELETE':
    // Delete a review
    if (!isset($_GET['id'])) handleInvalidRequest();
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    respond(["message" => "Review deleted."]);
    break;

  default:
    handleInvalidRequest();
    break;
}
