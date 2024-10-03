<?php
require_once 'api_common.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    // Fetch users, possibly by user ID
    if (isset($_GET['id'])) {
      $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
      $stmt->bind_param("i", $_GET['id']);
    } else {
      $stmt = $conn->prepare("SELECT * FROM users");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    respond($users);
    break;

  case 'POST':
    // Create a new user
    $data = json_decode(file_get_contents("php://input"), true);
    $password_hash = hash("sha256", $data['password']);
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $data['firstname'], $data['lastname'], $data['email'], $password_hash, $data['role']);
    $stmt->execute();
    respond(["message" => "User created.", "user_id" => $conn->insert_id], 201);
    break;

  case 'PUT':
    // Update a user
    if (!isset($_GET['id'])) handleInvalidRequest();
    $data = json_decode(file_get_contents("php://input"), true);
    $password_hash = hash("sha256", $data['password']);
    $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, password = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $data['firstname'], $data['lastname'], $data['email'], $password_hash, $data['role'], $_GET['id']);
    $stmt->execute();
    respond(["message" => "User updated."]);
    break;

  case 'DELETE':
    // Delete a user
    if (!isset($_GET['id'])) handleInvalidRequest();
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    respond(["message" => "User deleted."]);
    break;

  default:
    handleInvalidRequest();
    break;
}
