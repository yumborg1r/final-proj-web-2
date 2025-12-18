-- Coaches table for VIP Plan subscribers
-- This table stores information about personal trainers/coaches available for VIP members

USE gym_membership;

-- Create coaches table
CREATE TABLE IF NOT EXISTS coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    specialization VARCHAR(100) NOT NULL,
    distinction TEXT NOT NULL,
    bio TEXT,
    experience_years INT DEFAULT 0,
    certifications TEXT,
    photo VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample coaches with different distinctions
INSERT INTO coaches (first_name, last_name, email, phone, specialization, distinction, bio, experience_years, certifications, status) VALUES
('Michael', 'Johnson', 'michael.johnson@gym.com', '+63 912 345 6789', 'Strength Training', 'Olympic Weightlifting Specialist', 'Certified strength and conditioning coach with 10+ years of experience. Specializes in Olympic weightlifting, powerlifting, and functional strength training. Has trained multiple national-level athletes.', 10, 'NSCA-CSCS, USA Weightlifting Level 2', 'active'),
('Sarah', 'Martinez', 'sarah.martinez@gym.com', '+63 912 345 6790', 'Bodybuilding', 'IFBB Pro Bodybuilder', 'Professional bodybuilder and nutrition expert. Specializes in physique transformation, contest preparation, and advanced training techniques. Multiple competition wins.', 8, 'IFBB Pro, NASM-CPT, Nutrition Specialist', 'active'),
('David', 'Chen', 'david.chen@gym.com', '+63 912 345 6791', 'Cardio & Endurance', 'Marathon & Triathlon Coach', 'Endurance training specialist with expertise in marathon, triathlon, and ultra-distance events. Helps athletes achieve peak cardiovascular performance.', 12, 'USAT Level 2, RRCA Certified, ACSM-CEP', 'active'),
('Emily', 'Rodriguez', 'emily.rodriguez@gym.com', '+63 912 345 6792', 'Functional Fitness', 'CrossFit Level 3 Trainer', 'Functional movement and CrossFit specialist. Focuses on improving daily life movements, injury prevention, and athletic performance through functional training.', 7, 'CrossFit Level 3, FMS Certified, Mobility Specialist', 'active'),
('James', 'Anderson', 'james.anderson@gym.com', '+63 912 345 6793', 'Rehabilitation', 'Physical Therapy & Injury Recovery', 'Expert in post-injury rehabilitation and corrective exercise. Specializes in helping clients recover from injuries and prevent future problems.', 15, 'DPT, CSCS, Corrective Exercise Specialist', 'active'),
('Lisa', 'Thompson', 'lisa.thompson@gym.com', '+63 912 345 6794', 'Yoga & Flexibility', 'Yoga Master & Flexibility Expert', 'Yoga instructor and flexibility specialist. Combines traditional yoga practices with modern flexibility training for improved mobility and stress relief.', 9, 'RYT-500, Flexibility & Mobility Specialist', 'active'),
('Robert', 'Williams', 'robert.williams@gym.com', '+63 912 345 6795', 'Nutrition', 'Sports Nutritionist & Meal Planning', 'Registered dietitian specializing in sports nutrition and meal planning. Creates personalized nutrition plans to support fitness goals and optimal performance.', 11, 'RD, CSSD, Sports Nutrition Specialist', 'active'),
('Jennifer', 'Garcia', 'jennifer.garcia@gym.com', '+63 912 345 6796', 'Women\'s Fitness', 'Women\'s Health & Postnatal Specialist', 'Specializes in women\'s fitness, prenatal and postnatal training, and hormonal health. Empowers women to achieve their fitness goals safely and effectively.', 6, 'Pre/Postnatal Certified, Women\'s Health Specialist', 'active');


