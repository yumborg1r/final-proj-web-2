<?php
// Database configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'gym_membership';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );

            // Set error reporting
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ✅ Always use Philippine time (MySQL + PHP)
            $this->conn->exec("SET time_zone = '+08:00'");
            date_default_timezone_set('Asia/Manila');

        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// User class for authentication and user management
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $phone;
    public $address;
    public $profile_photo;
    public $role;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT id, username, email, password, first_name, last_name, role, status 
                  FROM " . $this->table_name . " 
                  WHERE (username = :username OR email = :username) AND status != 'suspended'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->role = $row['role'];
                $this->status = $row['status'];
                return true;
            }
        }
        return false;
    }

    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password=:password, 
                      first_name=:first_name, last_name=:last_name, 
                      phone=:phone, address=:address, profile_photo=:profile_photo, 
                      role=:role, status=:status";
        
        $stmt = $this->conn->prepare($query);
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':profile_photo', $this->profile_photo);
        $stmt->bindParam(':role', $this->role);
        $status = $this->status ? $this->status : 'active';
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    public function checkUnique($field, $value, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE " . $field . " = :value";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':value', $value);
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }
        $stmt->execute();
        return $stmt->rowCount() == 0;
    }

    public function getAllUsers() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUser() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username=:username, email=:email, first_name=:first_name, 
                      last_name=:last_name, phone=:phone, address=:address, 
                      profile_photo=:profile_photo, role=:role, status=:status 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':profile_photo', $this->profile_photo);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':status', $this->status);

        return $stmt->execute();
    }

    public function deleteUser() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
?>
