-- Create dummy_bank table for bank verification
-- This table stores dummy bank account information for testing/verification purposes

CREATE TABLE IF NOT EXISTS `dummy_bank` (
  `bank_id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) NOT NULL,
  `cardholder_name` varchar(100) NOT NULL,
  `card_number` varchar(19) NOT NULL COMMENT 'Full card number (will be stored for verification)',
  `expiry_date` varchar(5) NOT NULL COMMENT 'Format: MM/YY',
  `cvv` varchar(4) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1 = active, 0 = inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`bank_id`),
  KEY `idx_bank_name` (`bank_name`),
  KEY `idx_card_number` (`card_number`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample dummy bank data (you can manually add more)
-- Note: These are dummy/test data only
INSERT INTO `dummy_bank` (`bank_name`, `cardholder_name`, `card_number`, `expiry_date`, `cvv`, `is_active`) VALUES
('Maybank', 'John Doe', '1234567890123456', '12/25', '123', 1),
('CIMB', 'Jane Smith', '9876543210987654', '06/26', '456', 1),
('Public Bank', 'Bob Johnson', '5555666677778888', '09/27', '789', 1),
('Hong Leong Bank', 'Alice Brown', '1111222233334444', '03/28', '321', 1),
('RHB Bank', 'Charlie Wilson', '9999888877776666', '11/29', '654', 1);

