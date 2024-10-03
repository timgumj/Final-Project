<?php
require_once 'api_common.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    // Fetch services, possibly by service ID
    if (isset($_GET['id'])) {
      $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
      $stmt->bind_param("i", $_GET['id']);
    } else {
      $stmt = $conn->prepare("SELECT * FROM services");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $services = $result->fetch_all(MYSQLI_ASSOC);
    respond($services);
    break;

  case 'POST':
    // Create a new service
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("INSERT INTO services (name, description, price) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $data['name'], $data['description'], $data['price']);
    $stmt->execute();
    respond(["message" => "Service created.", "service_id" => $conn->insert_id], 201);
    break;

  case 'PUT':
    // Update a service
    if (!isset($_GET['id'])) handleInvalidRequest();
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, price = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $data['name'], $data['description'], $data['price'], $_GET['id']);
    $stmt->execute();
    respond(["message" => "Service updated."]);
    break;

  case 'DELETE':
    // Delete a service
    if (!isset($_GET['id'])) handleInvalidRequest();
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    respond(["message" => "Service deleted."]);
    break;

  default:
    handleInvalidRequest();
    break;
}
