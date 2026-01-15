<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>About Us</h3>
            <p>Spice Fusion brings you the best of Indonesian and Chinese cuisine, crafted with passion and served with care.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        <div class="footer-section">
            <h3>Quick Links</h3>
            <a href="/FYP/customer/main/menu/menu.php">Menu</a>
            <a href="/FYP/customer/main/aboutus/aboutus.php">About Us</a>
            <a href="/FYP/customer/main/contactus/contactus.php">Contact Us</a>
            <a href="/FYP/customer/main/legal/terms.php">Terms and Conditions</a>
            <a href="/FYP/customer/main/legal/privacy.php">Privacy Policy</a>
        </div>
        <div class="footer-section">
            <h3>Contact Info</h3>
            <p>180 Ang Mo Kio Avenue 8</p>
            <p>Singapore 569830</p>
            <p>Phone: +65 6451 5115</p>
            <p>Email: hello@spicefusion.com</p>
        </div>
        <div class="footer-section">
            <h3>Opening Hours</h3>
            <p>Monday - Sunday</p>
            <p>10:00 AM - 10:00 PM</p>
            <p>Including Public Holidays</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2024 Spice Fusion. All rights reserved. | 
            <a href="/FYP/customer/main/legal/terms.php">Terms and Conditions</a> | 
            <a href="/FYP/customer/main/legal/privacy.php">Privacy Policy</a>
        </p>
    </div>
</footer>
<style>
    .footer {
        background: var(--card-bg);
        padding: 3rem 0;
        margin-top: 4rem;
        color: var(--text-light);
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
    }

    .footer-section h3 {
        color: var(--primary-color);
        margin-bottom: 1rem;
        font-size: 1.2rem;
    }

    .footer-section p,
    .footer-section a {
        color: var(--text-gray);
        margin: 0.5rem 0;
        text-decoration: none;
        display: block;
        transition: var(--transition);
    }

    .footer-section a:hover {
        color: var(--primary-color);
    }

    .social-links {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .social-links a {
        color: var(--text-gray);
        font-size: 1.5rem;
        transition: var(--transition);
    }

    .social-links a:hover {
        color: var(--primary-color);
        transform: translateY(-2px);
    }

    .footer-bottom {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
        color: var(--text-gray);
        font-size: 0.9rem;
    }

    .footer-bottom a {
        color: var(--text-gray);
        text-decoration: none;
        margin: 0 0.5rem;
        transition: var(--transition);
    }

    .footer-bottom a:hover {
        color: var(--primary-color);
    }

    @media (max-width: 768px) {
        .footer-content {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .social-links {
            justify-content: center;
        }
    }
</style> 