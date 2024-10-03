<?php

class Review
{
  private $conn;
  private $table_name = "reviews";

  // Review properties
  public $id;
  public $student_id;
  public $service_id;
  public $rating;
  public $review_text;
  public $created_at;

  // Constructor with database connection
  public function __construct($db)
  {
    $this->conn = $db;
  }

  // Create a new review
  public function create()
  {
    $query = "INSERT INTO " . $this->table_name . " (student_id, service_id, rating, review_text) VALUES (?, ?, ?, ?)";

    $stmt = $this->conn->prepare($query);

    // Sanitize input
    $this->student_id = htmlspecialchars(strip_tags($this->student_id));
    $this->service_id = htmlspecialchars(strip_tags($this->service_id));
    $this->rating = htmlspecialchars(strip_tags($this->rating));
    $this->review_text = htmlspecialchars(strip_tags($this->review_text));

    // Bind data
    $stmt->bind_param("iiis", $this->student_id, $this->service_id, $this->rating, $this->review_text);

    // Execute the query
    if ($stmt->execute()) {
      return true;
    }

    return false;
  }

  // Get reviews by student
  public function getByStudent($student_id)
  {
    $query = "SELECT r.id, r.rating, r.review_text, r.created_at, ts.description AS service_description
                  FROM " . $this->table_name . " r
                  JOIN tutoring_services ts ON r.service_id = ts.id
                  WHERE r.student_id = ?
                  ORDER BY r.created_at DESC";

    $stmt = $this->conn->prepare($query);

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    return $stmt->get_result();
  }

  // Get reviews by service
  public function getByService($service_id)
  {
    $query = "SELECT r.id, r.rating, r.review_text, r.created_at, u.firstname AS student_firstname, u.lastname AS student_lastname
                  FROM " . $this->table_name . " r
                  JOIN users u ON r.student_id = u.id
                  WHERE r.service_id = ?
                  ORDER BY r.created_at DESC";

    $stmt = $this->conn->prepare($query);

    $stmt->bind_param("i", $service_id);
    $stmt->execute();

    return $stmt->get_result();
  }

  // Get a specific review by ID
  public function getById($id)
  {
    $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

    $stmt = $this->conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }

  // Update a review
  public function update()
  {
    $query = "UPDATE " . $this->table_name . " SET rating = ?, review_text = ? WHERE id = ?";

    $stmt = $this->conn->prepare($query);

    // Sanitize input
    $this->rating = htmlspecialchars(strip_tags($this->rating));
    $this->review_text = htmlspecialchars(strip_tags($this->review_text));

    // Bind data
    $stmt->bind_param("isi", $this->rating, $this->review_text, $this->id);

    // Execute the query
    if ($stmt->execute()) {
      return true;
    }

    return false;
  }

  // Delete a review
  public function delete($id)
  {
    $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

    $stmt = $this->conn->prepare($query);

    $stmt->bind_param("i", $id);

    // Execute the query
    if ($stmt->execute()) {
      return true;
    }

    return false;
  }
}
