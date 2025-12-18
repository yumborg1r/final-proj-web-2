<?php
// Coach management class for VIP Plan subscribers
class Coach {
    private $conn;
    private $table_name = "coaches";

    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $specialization;
    public $distinction;
    public $bio;
    public $experience_years;
    public $certifications;
    public $photo;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all active coaches
    public function getAllCoaches() {
        try {
            $query = "SELECT * FROM {$this->table_name} 
                      WHERE status = 'active' 
                      ORDER BY experience_years DESC, last_name ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all coaches error: " . $e->getMessage());
            return [];
        }
    }

    // Get coach by ID
    public function getCoachById($id) {
        try {
            $query = "SELECT * FROM {$this->table_name} WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get coach by ID error: " . $e->getMessage());
            return false;
        }
    }

    // Get coaches by specialization
    public function getCoachesBySpecialization($specialization) {
        try {
            $query = "SELECT * FROM {$this->table_name} 
                      WHERE specialization = :specialization AND status = 'active' 
                      ORDER BY experience_years DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':specialization', $specialization);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get coaches by specialization error: " . $e->getMessage());
            return [];
        }
    }

    // Get all specializations
    public function getSpecializations() {
        try {
            $query = "SELECT DISTINCT specialization FROM {$this->table_name} 
                      WHERE status = 'active' 
                      ORDER BY specialization ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $results;
        } catch (PDOException $e) {
            error_log("Get specializations error: " . $e->getMessage());
            return [];
        }
    }

    // Create new coach (admin function)
    public function createCoach() {
        try {
            $query = "INSERT INTO {$this->table_name} 
                      SET first_name=:first_name, last_name=:last_name, email=:email, 
                          phone=:phone, specialization=:specialization, distinction=:distinction,
                          bio=:bio, experience_years=:experience_years, certifications=:certifications,
                          photo=:photo, status=:status";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':first_name', $this->first_name);
            $stmt->bindParam(':last_name', $this->last_name);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':phone', $this->phone);
            $stmt->bindParam(':specialization', $this->specialization);
            $stmt->bindParam(':distinction', $this->distinction);
            $stmt->bindParam(':bio', $this->bio);
            $stmt->bindParam(':experience_years', $this->experience_years, PDO::PARAM_INT);
            $stmt->bindParam(':certifications', $this->certifications);
            $stmt->bindParam(':photo', $this->photo);
            $stmt->bindParam(':status', $this->status);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Create coach error: " . $e->getMessage());
            return false;
        }
    }

    // Update coach (admin function)
    public function updateCoach() {
        try {
            $query = "UPDATE {$this->table_name} 
                      SET first_name=:first_name, last_name=:last_name, email=:email, 
                          phone=:phone, specialization=:specialization, distinction=:distinction,
                          bio=:bio, experience_years=:experience_years, certifications=:certifications,
                          photo=:photo, status=:status
                      WHERE id=:id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':first_name', $this->first_name);
            $stmt->bindParam(':last_name', $this->last_name);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':phone', $this->phone);
            $stmt->bindParam(':specialization', $this->specialization);
            $stmt->bindParam(':distinction', $this->distinction);
            $stmt->bindParam(':bio', $this->bio);
            $stmt->bindParam(':experience_years', $this->experience_years, PDO::PARAM_INT);
            $stmt->bindParam(':certifications', $this->certifications);
            $stmt->bindParam(':photo', $this->photo);
            $stmt->bindParam(':status', $this->status);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update coach error: " . $e->getMessage());
            return false;
        }
    }

    // Delete coach (admin function)
    public function deleteCoach() {
        try {
            $query = "DELETE FROM {$this->table_name} WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete coach error: " . $e->getMessage());
            return false;
        }
    }
}
?>


