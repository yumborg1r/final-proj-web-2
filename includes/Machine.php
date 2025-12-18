<?php
class Machine {
    private $conn;
    private $table_name = "machines";

    public $id;
    public $name;
    public $description;
    public $photo;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllMachines() {
        $query = "SELECT * FROM {$this->table_name} ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createMachine() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, description=:description, photo=:photo, status=:status";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->photo = htmlspecialchars(strip_tags($this->photo));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Bind
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":photo", $this->photo);
        $stmt->bindParam(":status", $this->status);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateMachine() {
        $query = "UPDATE " . $this->table_name . "
                  SET name=:name, description=:description, photo=:photo, status=:status
                  WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->photo = htmlspecialchars(strip_tags($this->photo));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":photo", $this->photo);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function deleteMachine() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
