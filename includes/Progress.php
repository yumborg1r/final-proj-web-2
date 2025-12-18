<?php
class Progress {
    private $conn;
    private $table_name = "user_progress";

    public $id;
    public $user_id;
    public $weight;
    public $body_fat_percent;
    public $notes;
    public $photo_path;
    public $recorded_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add new progress record
    public function addProgress() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id = :user_id,
                      weight = :weight,
                      body_fat_percent = :body_fat_percent,
                      notes = :notes,
                      photo_path = :photo_path,
                      recorded_date = :recorded_date";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->weight = htmlspecialchars(strip_tags($this->weight));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $this->photo_path = htmlspecialchars(strip_tags($this->photo_path));
        $this->recorded_date = htmlspecialchars(strip_tags($this->recorded_date));
        
        // Convert empty body fat to null
        if (empty($this->body_fat_percent)) {
            $this->body_fat_percent = null;
        }

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":weight", $this->weight);
        $stmt->bindParam(":body_fat_percent", $this->body_fat_percent);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":photo_path", $this->photo_path);
        $stmt->bindParam(":recorded_date", $this->recorded_date);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get progress history for a user
    public function getProgressHistory($user_id, $limit = 0) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE user_id = :user_id
                  ORDER BY recorded_date DESC";
        
        if ($limit > 0) {
            $query .= " LIMIT " . $limit;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get latest progress record
    public function getLatestProgress($user_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE user_id = :user_id
                  ORDER BY recorded_date DESC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Delete a progress record
    public function deleteProgress($id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
