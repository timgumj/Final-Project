<?php
header("Content-Type: application/json");
require_once '../config/dbconnection.php';

// Function to respond with a JSON message
function respond($data, $status = 200)
{
  http_response_code($status);
  echo json_encode($data);
  exit();
}

// Function to handle invalid requests
function handleInvalidRequest()
{
  respond(["message" => "Invalid request."], 400);
}
