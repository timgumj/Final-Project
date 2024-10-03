<?php

class Booking
{
  private $conn;
  private $table_name = "bookings";

  // Booking properties
  public $id;
  public $student_id;
  public $service_id;
  public $booking_date;
  public $time_slot;
  public $status;
  public $created_at;

  // Constructor with database connection
  public function __construct($db)
  {
    $this->conn = $db;
  }

  // Create a new booking
  public function create()
  {
    $query = "INSERT INTO " . $this->table_name . " (student_id, service_id, booking_date, time_slot, status) VALUES (?, ?, ?, ?, ?)";

    $stmt = $this->conn->prepare($query);

    // Sanitize input
    $this->student_id = htmlspecialchars(strip_tags($this->student_id));
    $this->service_id = htmlspecialchars(strip_tags($this->service_id));
    $this->booking_date = htmlspecialchars(strip_tags($this->booking_date));
    $this->time_slot = htmlspecialchars(strip_tags($this->time_slot));
    $this->status = htmlspecialchars(strip_tags($this->status));

    // Bind data
    $stmt->bind_param("iisss", $this->student_id, $this->service_id, $this->booking_date, $this->time_slot, $this->status);

    // Execute the query
    if ($stmt->execute()) {
      return true;
    }

    return false;
  }

  // Get bookings by student
  public function getByStudent($student_id)
  {
    $query = "SELECT b.id, b.booking_date, b.time_slot, b.status, ts.description AS service_description, u.firstname AS trainer_firstname, u.lastname AS trainer_lastname
                  FROM " . $this->table_name . " b
                  JOIN tutoring_services ts ON b.service_id = ts.id
                  JOIN users u ON ts.trainer_id = u.id
                  WHERE b.student_id = ?
                  ORDER BY b.booking_date DESC";

    $stmt = $this->conn->prepare($query);

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    return $stmt->get_result();
  }

  // Get booking details by ID
  public function getById($id)
  {
    $query = "SELECT b.id, b.student_id, b.service_id, b.booking_date, b.time_slot, b.status, ts.description AS service_description, u.firstname AS trainer_firstname, u.lastname AS trainer_lastname
                  FROM " . $this->table_name . " b
                  JOIN tutoring_services ts ON b.service_id = ts.id
                  JOIN users u ON ts.trainer_id = u.id
                  WHERE b.id = ? LIMIT 0,1";

    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }

  // Update booking status
  public function updateStatus($id, $status)
  {
    $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";

    $stmt = $this->conn->prepare($query);

    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
      return true;
    }

    return false;
  }

  // Cancel booking
  public function cancel($id)
  {
    return $this->updateStatus($id, 'canceled');
  }
}
