<?php
// Always use Philippine time globally
date_default_timezone_set('Asia/Manila');

// Workout Plan management class
class WorkoutPlan {
    private $conn;
    private $table_name = "workout_plans";

    public $id;
    public $name;
    public $description;
    public $exercises;
    public $duration_minutes;
    public $difficulty_level;
    public $cover_photo;
    public $created_by;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllWorkoutPlans() {
        $query = "SELECT wp.*, u.first_name, u.last_name 
                  FROM " . $this->table_name . " wp 
                  JOIN users u ON wp.created_by = u.id 
                  WHERE wp.status = 'active' 
                  ORDER BY wp.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWorkoutPlanById($id) {
        $query = "SELECT wp.*, u.first_name, u.last_name 
                  FROM " . $this->table_name . " wp 
                  JOIN users u ON wp.created_by = u.id 
                  WHERE wp.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createWorkoutPlan() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, description=:description, exercises=:exercises, 
                      duration_minutes=:duration_minutes, difficulty_level=:difficulty_level, 
                      cover_photo=:cover_photo, created_by=:created_by, status=:status";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':exercises', $this->exercises);
        $stmt->bindParam(':duration_minutes', $this->duration_minutes);
        $stmt->bindParam(':difficulty_level', $this->difficulty_level);
        $stmt->bindParam(':cover_photo', $this->cover_photo);
        $stmt->bindParam(':created_by', $this->created_by);
        $stmt->bindParam(':status', $this->status);

        return $stmt->execute();
    }

    public function updateWorkoutPlan() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, description=:description, exercises=:exercises, 
                      duration_minutes=:duration_minutes, difficulty_level=:difficulty_level, cover_photo=:cover_photo, 
                      status=:status 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':exercises', $this->exercises);
        $stmt->bindParam(':duration_minutes', $this->duration_minutes);
        $stmt->bindParam(':difficulty_level', $this->difficulty_level);
        $stmt->bindParam(':cover_photo', $this->cover_photo);
        $stmt->bindParam(':status', $this->status);

        return $stmt->execute();
    }

    public function deleteWorkoutPlan() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}

// Attendance management class
class Attendance {
    private $conn;
    private $table_name = "user_attendance";

    public $id;
    public $user_id;
    public $workout_plan_id;
    public $attendance_date;
    public $check_in_time;
    public $check_out_time;
    public $notes;
    public $marked_by;

    public function __construct($db) {
        $this->conn = $db;
        // Ensure Philippine timezone
        date_default_timezone_set('Asia/Manila');
    }

    public function markAttendance() {
        // Ensure Philippine timezone
        date_default_timezone_set('Asia/Manila');
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, workout_plan_id=:workout_plan_id, 
                      attendance_date=:attendance_date, check_in_time=:check_in_time, 
                      check_out_time=:check_out_time, notes=:notes, marked_by=:marked_by";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':workout_plan_id', $this->workout_plan_id);
        $stmt->bindParam(':attendance_date', $this->attendance_date);
        $stmt->bindParam(':check_in_time', $this->check_in_time);
        $stmt->bindParam(':check_out_time', $this->check_out_time);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':marked_by', $this->marked_by);

        return $stmt->execute();
    }

    public function getAttendanceByUser($user_id, $start_date = null, $end_date = null) {
        $query = "SELECT ua.*, wp.name as workout_name, u.first_name, u.last_name 
                  FROM " . $this->table_name . " ua 
                  JOIN workout_plans wp ON ua.workout_plan_id = wp.id 
                  JOIN users u ON ua.user_id = u.id 
                  WHERE ua.user_id = :user_id";
        
        if($start_date && $end_date) {
            $query .= " AND ua.attendance_date BETWEEN :start_date AND :end_date";
        }
        
        $query .= " ORDER BY ua.attendance_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAttendance($start_date = null, $end_date = null) {
        $query = "SELECT ua.*, wp.name as workout_name, u.first_name, u.last_name, 
                         u.email, u.phone 
                  FROM " . $this->table_name . " ua 
                  JOIN workout_plans wp ON ua.workout_plan_id = wp.id 
                  JOIN users u ON ua.user_id = u.id";
        
        if($start_date && $end_date) {
            $query .= " WHERE ua.attendance_date BETWEEN :start_date AND :end_date";
        }
        
        $query .= " ORDER BY ua.attendance_date DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttendanceStats($user_id) {
        $query = "SELECT COUNT(*) as total_workouts, 
                         COUNT(DISTINCT DATE(attendance_date)) as unique_days,
                         AVG(TIMESTAMPDIFF(MINUTE, check_in_time, check_out_time)) as avg_duration
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOpenAttendanceForUserToday($user_id) {
        // Ensure Philippine timezone
        date_default_timezone_set('Asia/Manila');
        $this->conn->exec("SET time_zone = '+08:00'");
        
        $today = date('Y-m-d');
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                    AND attendance_date = :today
                    AND (check_out_time IS NULL OR check_out_time = '')
                  ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checkOutById($attendance_id) {
        // Ensure Philippine timezone
        date_default_timezone_set('Asia/Manila');
        
        $query = "UPDATE " . $this->table_name . " 
                  SET check_out_time = :check_out_time 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $now = date('H:i:s');
        $stmt->bindParam(':check_out_time', $now);
        $stmt->bindParam(':id', $attendance_id);
        return $stmt->execute();
    }
}
