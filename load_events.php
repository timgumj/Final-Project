<?php
require_once '../config/dbconnection.php';

$events = [];
$query = "SELECT id, service_id, booking_date, time_slot FROM bookings";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
  $events[] = [
    'id' => $row['id'],
    'title' => 'Service ID: ' . $row['service_id'],
    'start' => $row['booking_date'] . 'T' . $row['time_slot'],
  ];
}

echo json_encode($events);
