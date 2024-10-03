<?php
class CrudOperations
{
  private $conn;
  private $table;

  public function __construct($db, $table)
  {
    $this->conn = $db;
    $this->table = $table;
  }

  // Create
  public function create($data)
  {
    $keys = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));

    $sql = "INSERT INTO $this->table ($keys) VALUES ($placeholders)";
    $stmt = $this->conn->prepare($sql);

    foreach ($data as $key => $value) {
      $stmt->bindValue(":$key", $value);
    }

    return $stmt->execute();
  }

  // Read
  public function read($conditions = "")
  {
    $sql = "SELECT * FROM $this->table $conditions";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Update
  public function update($data, $conditions)
  {
    $setClause = "";
    foreach ($data as $key => $value) {
      $setClause .= "$key = :$key, ";
    }
    $setClause = rtrim($setClause, ", ");

    $sql = "UPDATE $this->table SET $setClause WHERE $conditions";
    $stmt = $this->conn->prepare($sql);

    foreach ($data as $key => $value) {
      $stmt->bindValue(":$key", $value);
    }

    return $stmt->execute();
  }

  // Delete
  public function delete($conditions)
  {
    $sql = "DELETE FROM $this->table WHERE $conditions";
    $stmt = $this->conn->prepare($sql);

    return $stmt->execute();
  }
}
