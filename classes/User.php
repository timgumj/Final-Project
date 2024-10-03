<?php
class User
{
  private $conn;
  private $table = "users";

  public function __construct($db)
  {
    $this->conn = $db;
  }

  public function register($data)
  {
    $crud = new CrudOperations($this->conn, $this->table);
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    return $crud->create($data);
  }

  public function login($email, $password)
  {
    $crud = new CrudOperations($this->conn, $this->table);
    $result = $crud->read("WHERE email = '$email'");

    if ($result && password_verify($password, $result[0]['password'])) {
      return $result[0];
    } else {
      return false;
    }
  }

  public function updateProfile($data, $userId)
  {
    $crud = new CrudOperations($this->conn, $this->table);
    return $crud->update($data, "id = $userId");
  }
}
