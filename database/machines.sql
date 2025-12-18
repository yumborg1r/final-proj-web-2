USE gym_membership;

CREATE TABLE IF NOT EXISTS machines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    photo VARCHAR(255),
    status ENUM('active','maintenance','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS machine_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    machine_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    duration_minutes INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
    INDEX idx_user_day (user_id, start_time)
);

-- Seed Data
INSERT INTO machines (name, description, status) VALUES 
('Treadmill X1', 'High-end cardio treadmill with incline', 'active'),
('Smith Machine', 'Guided barbell system for squats and presses', 'active'),
('Cable Crossover', 'Multi-functional cable machine', 'active'),
('Leg Press 3000', 'Heavy duty leg press machine', 'maintenance'),
('Dumbbell Rack', 'Set of dumbbells from 5lbs to 100lbs', 'active'),
('Rowing Machine', 'Concept2 rower for full body cardio', 'active');
