<?php
class TutoringService
{
  private $conn;
  private $table = "tutoring_services";

  public function __construct($db)
  {
    $this->conn = $db;
  }

  public function addService($data)
  {
    $crud = new CrudOperations($this->conn, $this->table);
    return $crud->create($data);
  }

  public function updateService($data, $serviceId)
  {
    $crud = new CrudOperations($this->conn, $this->table);
    return $crud->update($data, "id = $serviceId");
  }

  public function deleteService($serviceId)
  {
    $crud = new CrudOperations($this->conn, $this->table);
    return $crud->delete("id = $serviceId");
  }

  public function getService($serviceId)
  {
    $crud = new CrudOperations($this->conn, $this->table);
    return $crud->read("WHERE id = $serviceId");
  }

  public function getAllServices()
  {
    $crud = new CrudOperations($this->conn, $this->table);
    return $crud->read();
  }
}
