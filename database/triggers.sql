-- Database Triggers for Automatic Subscription Status Updates
-- Run this SQL script to enable automatic status updates in the database
-- This will automatically update subscription status when dates are modified directly in the database

USE gym_membership;

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS update_subscription_status_on_insert;
DROP TRIGGER IF EXISTS update_subscription_status_on_update;
DROP TRIGGER IF EXISTS update_subscription_status_on_date_change;

-- Trigger to automatically update status when a subscription is inserted
DELIMITER $$
CREATE TRIGGER update_subscription_status_on_insert
BEFORE INSERT ON user_subscriptions
FOR EACH ROW
BEGIN
    DECLARE days_diff INT;
    DECLARE today_date DATE;
    DECLARE active_subs_count INT;
    
    -- Set timezone to Philippines (Asia/Manila)
    SET time_zone = '+08:00';
    SET today_date = CURDATE();
    
    -- If status is NULL or empty, default to 'pending' first
    IF NEW.status IS NULL OR NEW.status = '' THEN
        SET NEW.status = 'pending';
    END IF;
    
    -- Only auto-update status if it's not explicitly set to 'pending' or 'cancelled'
    IF NEW.status NOT IN ('pending', 'cancelled') THEN
        -- Calculate days difference
        SET days_diff = DATEDIFF(NEW.end_date, NEW.start_date);
        
        -- Update status based on dates (priority order matters)
        -- Rule 1: If end_date has passed, mark as expired
        IF NEW.end_date < today_date THEN
            SET NEW.status = 'expired';
        -- Rule 2: If subscription period exceeds 30 days, mark as expired
        ELSEIF days_diff > 30 THEN
            SET NEW.status = 'expired';
        -- Rule 3: If start_date is in the future, keep as pending (hasn't started yet)
        ELSEIF NEW.start_date > today_date THEN
            SET NEW.status = 'pending';
        -- Rule 4: Otherwise, subscription is active
        ELSE
            SET NEW.status = 'active';
        END IF;
    -- If status is 'pending' but start_date is in the past, evaluate if it should be active
    ELSEIF NEW.status = 'pending' AND NEW.start_date <= today_date THEN
        SET days_diff = DATEDIFF(NEW.end_date, NEW.start_date);
        IF NEW.end_date < today_date THEN
            SET NEW.status = 'expired';
        ELSEIF days_diff > 30 THEN
            SET NEW.status = 'expired';
        ELSE
            SET NEW.status = 'active';
        END IF;
    END IF;

    -- Sync user status based on subscription status
    IF NEW.status = 'active' THEN
        UPDATE users SET status = 'active' WHERE id = NEW.user_id;
    END IF;
    -- Note: For INSERT, we don't set to inactive because the user might have other active subscriptions
    -- and check active_subs_count here for "other" subs is complex locally, but let's trust default user status?
    -- Actually, if a user has NO active subscriptions, they should be inactive.
    -- But NEW record is not in DB yet.
    -- So we check existing records.
    -- IF (SELECT COUNT(*) FROM user_subscriptions WHERE user_id = NEW.user_id AND status = 'active') = 0 AND NEW.status != 'active' THEN
    --    UPDATE users SET status = 'inactive' WHERE id = NEW.user_id;
    -- END IF;
    -- But safer to only upgrade to active on insert.
END$$
DELIMITER ;

-- Trigger to automatically update status when subscription dates are updated
DELIMITER $$
CREATE TRIGGER update_subscription_status_on_date_change
BEFORE UPDATE ON user_subscriptions
FOR EACH ROW
BEGIN
    DECLARE days_diff INT;
    DECLARE today_date DATE;
    DECLARE other_active_count INT;
    
    -- Set timezone to Philippines (Asia/Manila)
    SET time_zone = '+08:00';
    SET today_date = CURDATE();
    
    -- Always respect 'cancelled' status - never auto-update it
    IF NEW.status = 'cancelled' THEN
        SET NEW.status = 'cancelled';
    -- If dates have changed, recalculate status (unless explicitly cancelled)
    ELSEIF (NEW.start_date != OLD.start_date OR NEW.end_date != OLD.end_date) THEN
        -- Calculate days difference
        SET days_diff = DATEDIFF(NEW.end_date, NEW.start_date);
        
        -- Update status based on dates (priority order matters)
        -- Rule 1: If end_date has passed, mark as expired
        IF NEW.end_date < today_date THEN
            SET NEW.status = 'expired';
        -- Rule 2: If subscription period exceeds 30 days, mark as expired
        ELSEIF days_diff > 30 THEN
            SET NEW.status = 'expired';
        -- Rule 3: If start_date is in the future, set to pending (hasn't started yet)
        ELSEIF NEW.start_date > today_date THEN
            SET NEW.status = 'pending';
        -- Rule 4: Otherwise, subscription is active
        ELSE
            SET NEW.status = 'active';
        END IF;
    -- If status was explicitly changed but dates didn't change, validate the status
    ELSEIF NEW.status != OLD.status AND NEW.status != 'cancelled' AND NEW.status != 'pending' THEN
        -- Recalculate to ensure status matches dates
        SET days_diff = DATEDIFF(NEW.end_date, NEW.start_date);
        
        IF NEW.end_date < today_date THEN
            SET NEW.status = 'expired';
        ELSEIF days_diff > 30 THEN
            SET NEW.status = 'expired';
        ELSEIF NEW.start_date > today_date THEN
            SET NEW.status = 'pending';
        ELSE
            SET NEW.status = 'active';
        END IF;
    -- If nothing changed but status is not pending/cancelled, re-validate (handles time-based expiration)
    ELSEIF NEW.status NOT IN ('pending', 'cancelled') THEN
        SET days_diff = DATEDIFF(NEW.end_date, NEW.start_date);
        
        IF NEW.end_date < today_date THEN
            SET NEW.status = 'expired';
        ELSEIF days_diff > 30 THEN
            SET NEW.status = 'expired';
        ELSEIF NEW.start_date > today_date THEN
            SET NEW.status = 'pending';
        ELSE
            SET NEW.status = 'active';
        END IF;
    END IF;

    -- Sync user status logic
    IF NEW.status = 'active' THEN
        UPDATE users SET status = 'active' WHERE id = NEW.user_id;
    ELSE
        -- If subscription is NOT active (expired, cancelled, pending), check if user has ANY other active subscription
        SELECT COUNT(*) INTO other_active_count 
        FROM user_subscriptions 
        WHERE user_id = NEW.user_id 
        AND status = 'active' 
        AND id != NEW.id;
        
        IF other_active_count = 0 THEN
            UPDATE users SET status = 'inactive' WHERE id = NEW.user_id;
        END IF;
    END IF;
END$$
DELIMITER ;

-- Trigger to automatically update all subscription statuses periodically (via scheduled event)
-- This creates a stored procedure that can be called to update all statuses
DELIMITER $$
CREATE PROCEDURE auto_update_subscription_statuses()
BEGIN
    DECLARE today_date DATE;
    SET time_zone = '+08:00';
    SET today_date = CURDATE();
    
    -- 1. Update Subscriptions
    UPDATE user_subscriptions
    SET status = CASE
        WHEN end_date < today_date THEN 'expired'
        WHEN start_date > today_date THEN 'pending'
        WHEN DATEDIFF(end_date, start_date) > 30 THEN 'expired'
        WHEN status = 'pending' THEN 'pending'
        WHEN status = 'cancelled' THEN 'cancelled'
        ELSE 'active'
    END,
    updated_at = NOW()
    WHERE status NOT IN ('pending', 'cancelled')
       OR (
           (status = 'pending' AND start_date <= today_date AND end_date >= today_date AND DATEDIFF(end_date, start_date) <= 30) OR
           (status = 'expired' AND end_date >= today_date AND DATEDIFF(end_date, start_date) <= 30 AND start_date <= today_date) OR
           (status = 'active' AND (end_date < today_date OR DATEDIFF(end_date, start_date) > 30 OR start_date > today_date))
       );

    -- 2. Sync Users Status (Set to inactive if no active subscriptions found)
    -- This is a bit heavy but ensures consistency
    UPDATE users u
    SET status = 'inactive'
    WHERE role = 'user' 
    AND status = 'active'
    AND NOT EXISTS (
        SELECT 1 FROM user_subscriptions us 
        WHERE us.user_id = u.id 
        AND us.status = 'active'
    );
    
    -- 3. Sync Users Status (Set to active if active subscription found)
    UPDATE users u
    SET status = 'active'
    WHERE role = 'user'
    AND status != 'active'
    AND EXISTS (
        SELECT 1 FROM user_subscriptions us 
        WHERE us.user_id = u.id 
        AND us.status = 'active'
    );

END$$
DELIMITER ;
