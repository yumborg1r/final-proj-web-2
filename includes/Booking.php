<?php
// Always use Philippine time globally
date_default_timezone_set('Asia/Manila');

class Booking {
    private $conn;
    private $table_name = "coach_bookings";

    public $id;
    public $user_id;
    public $coach_id;
    public $booking_date;
    public $time_slot;
    public $status;
    public $notes;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            // Validate required fields
            if (empty($this->user_id) || empty($this->coach_id) || empty($this->booking_date) || empty($this->time_slot)) {
                throw new Exception("Missing required fields");
            }

            // Check availability
            if (!$this->isSlotAvailable($this->coach_id, $this->booking_date, $this->time_slot)) {
                throw new Exception("This time slot is already booked.");
            }

            $query = "INSERT INTO {$this->table_name} 
                      SET user_id=:user_id, coach_id=:coach_id, booking_date=:booking_date, 
                          time_slot=:time_slot, notes=:notes, status='pending', created_at=NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':coach_id', $this->coach_id);
            $stmt->bindParam(':booking_date', $this->booking_date);
            $stmt->bindParam(':time_slot', $this->time_slot);
            $stmt->bindParam(':notes', $this->notes);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Create booking error: " . $e->getMessage());
            throw $e; // Re-throw to handle message in frontend
        }
    }

    public function isSlotAvailable($coach_id, $date, $time_slot) {
        $query = "SELECT COUNT(*) as count FROM {$this->table_name} 
                  WHERE coach_id = :coach_id AND booking_date = :booking_date 
                  AND time_slot = :time_slot AND status != 'cancelled'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':coach_id', $coach_id);
        $stmt->bindParam(':booking_date', $date);
        $stmt->bindParam(':time_slot', $time_slot);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'] == 0;
    }

    public function getUserBookings($user_id) {
        $query = "SELECT b.*, c.first_name as coach_first, c.last_name as coach_last, c.specialization, c.photo
                  FROM {$this->table_name} b
                  JOIN coaches c ON b.coach_id = c.id
                  WHERE b.user_id = :user_id
                  ORDER BY b.booking_date ASC, b.time_slot ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCoachBookings($coach_id) {
         $query = "SELECT b.*, u.first_name as user_first, u.last_name as user_last
                  FROM {$this->table_name} b
                  JOIN users u ON b.user_id = u.id
                  WHERE b.coach_id = :coach_id
                  ORDER BY b.booking_date ASC, b.time_slot ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':coach_id', $coach_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelBooking($booking_id, $user_id) {
        // Only allow user who owns the booking to cancel (or generic cancel if we add admin support later)
        $query = "UPDATE {$this->table_name} SET status = 'cancelled' 
                  WHERE id = :id AND user_id = :user_id AND status != 'cancelled'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $booking_id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    // Admin: Get all bookings
    public function getAllBookings() {
        $query = "SELECT b.*, 
                         u.first_name as user_first, u.last_name as user_last, u.email as user_email,
                         c.first_name as coach_first, c.last_name as coach_last
                  FROM {$this->table_name} b
                  JOIN users u ON b.user_id = u.id
                  JOIN coaches c ON b.coach_id = c.id
                  ORDER BY b.booking_date DESC, b.time_slot ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Admin: Update booking status
    public function updateStatus($id, $status) {
        $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed', 'declined'];
        if (!in_array($status, $allowed_statuses)) {
            return false;
        }

        $query = "UPDATE {$this->table_name} SET status = :status WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        
        return $stmt->execute();
    }
}
?>
