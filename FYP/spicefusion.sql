USE spicefusion;

-- Drop existing tables in the correct order
DROP TABLE IF EXISTS
    payment,
    review,
    shopping_cart,
    order_item,
    `order`,
    delivery_address,
    product,
    category,
    admin_user,
    user;

-- User table (with security_question_id)
CREATE TABLE user (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    profile_image VARCHAR(255) DEFAULT 'user.jpg',
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    security_question_id INT,
    security_answer_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Security Questions table
CREATE TABLE IF NOT EXISTS security_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL
);

-- Insert common security questions
INSERT INTO security_questions (question) VALUES
('What is your mother''s maiden name?'),
('What was the name of your primary school?'),
('What is your favorite food?'),
('What city were you born in?'),
('What was the name of your first pet?');

-- Add foreign key after user table creation
ALTER TABLE user
    ADD CONSTRAINT fk_security_question
    FOREIGN KEY (security_question_id) REFERENCES security_questions(id);

-- Category table
CREATE TABLE category (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Product table
CREATE TABLE product (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    stock_quantity INT DEFAULT 0,
    preparation_time INT DEFAULT 15,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE SET NULL
);

-- Delivery address table
CREATE TABLE delivery_address (
    address_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'Malaysia',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- Orders table (fixed TIMESTAMP fields)
CREATE TABLE `order` (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    order_status ENUM('Pending', 'Confirmed', 'Preparing', 'Ready', 'Out for Delivery', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    order_type ENUM('Dine-in', 'Takeaway', 'Delivery') DEFAULT 'Delivery',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    special_instructions TEXT,
    estimated_delivery_time TIMESTAMP NULL DEFAULT NULL,
    actual_delivery_time TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (address_id) REFERENCES delivery_address(address_id) ON DELETE SET NULL
);

-- Order item table
CREATE TABLE order_item (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES `order`(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
);

-- Shopping cart
CREATE TABLE shopping_cart (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Review table
CREATE TABLE review (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES `order`(order_id) ON DELETE SET NULL
);

-- Payment table
CREATE TABLE payment (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_method ENUM('Cash', 'Card', 'Credit Card', 'Debit Card', 'PayPal', 'Online Banking') NOT NULL,
    payment_status ENUM('Pending', 'Processing', 'Completed', 'Failed', 'Refunded') DEFAULT 'Pending',
    amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES `order`(order_id) ON DELETE CASCADE
);

-- Admin user table
CREATE TABLE admin_user (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('Super Admin', 'Manager', 'Staff') DEFAULT 'Staff',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample categories
INSERT INTO category (category_name, description) VALUES
('Main Course', 'Delicious main dishes'),
('Appetizers', 'Starters and snacks'),
('Beverages', 'Drinks and refreshments'),
('Desserts', 'Sweet treats'),
('Rice Dishes', 'Various rice-based meals'),
('Noodles', 'Noodle dishes'),
('Soups', 'Warm and comforting soups');

-- Insert sample products
INSERT INTO product (category_id, product_name, description, price, image, stock_quantity, is_featured) VALUES
(1, 'Rendang Beef', 'Tender beef cooked in rich coconut milk and spices', 25.90, 'rendang_beef.png', 50, TRUE),
(1, 'Kung Pao Chicken', 'Spicy diced chicken with peanuts and vegetables', 22.90, 'kung_pau_chciken.png', 45, TRUE),
(1, 'Mapo Tofu', 'Spicy tofu with minced meat in Sichuan sauce', 18.90, 'mapo_toufu.png', 40, FALSE),
(2, 'Char Siu Bao', 'Steamed BBQ pork buns', 8.90, 'char_siu_bao.png', 30, TRUE),
(2, 'Siew Mai', 'Steamed pork and shrimp dumplings', 12.90, 'siew_mai.png', 35, FALSE),
(2, 'Dumpling', 'Pan-fried dumplings with pork filling', 10.90, 'dumpling.png', 25, FALSE),
(3, 'Coffee', 'Freshly brewed coffee', 6.90, 'coffee.png', 100, TRUE),
(3, 'Tea', 'Traditional Chinese tea', 5.90, 'tea.png', 100, FALSE),
(3, 'Lime Juice', 'Refreshing lime juice', 7.90, 'lime_juice.png', 80, FALSE),
(4, 'Taiyaki', 'Fish-shaped waffle with sweet filling', 8.90, 'taiyaki.png', 20, TRUE),
(4, 'Tang Yuan', 'Sweet glutinous rice balls', 9.90, 'tang_yuan.png', 25, FALSE),
(5, 'Nasi Ayam Geprek', 'Crispy chicken with rice and sambal', 16.90, 'nasi_ayam_geprek.png', 40, TRUE),
(5, 'Nasi Campur', 'Mixed rice with various side dishes', 18.90, 'nasi_campur.png', 35, FALSE),
(6, 'Char Kuey Teow', 'Stir-fried flat rice noodles', 14.90, 'char_kuey_teow.png', 30, TRUE),
(6, 'Mie Goreng', 'Indonesian fried noodles', 13.90, 'mie_goreng.png', 30, FALSE),
(7, 'Wantan Soup', 'Clear soup with wonton dumplings', 12.90, 'wantan_soup.png', 25, FALSE);

-- Insert admin user
INSERT INTO admin_user (username, email, password_hash, first_name, last_name, role) VALUES
('admin', 'admin@spicefusion.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'Super Admin');

-- Indexes
CREATE INDEX idx_user_email ON user(email);
CREATE INDEX idx_user_username ON user(username);
CREATE INDEX idx_product_category ON product(category_id);
CREATE INDEX idx_product_featured ON product(is_featured);
CREATE INDEX idx_order_user ON `order`(user_id);
CREATE INDEX idx_order_status ON `order`(order_status);
CREATE INDEX idx_order_item_order ON order_item(order_id);
CREATE INDEX idx_review_product ON review(product_id);
CREATE INDEX idx_review_user ON review(user_id);
CREATE INDEX idx_payment_order ON payment(order_id);
CREATE INDEX idx_cart_user ON shopping_cart(user_id);
