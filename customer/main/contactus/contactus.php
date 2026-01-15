<?php
require_once '../../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../includes/styles.css">
    <style>
        .contact-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('/FYP/images/landing_page.png');
            background-size: cover;
            background-position: center;
            padding: 6rem 0;
            text-align: center;
            color: var(--text-light);
        }

        .contact-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .contact-hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            color: var(--text-gray);
        }

        .contact-content {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
        }

        .contact-info {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-soft);
        }

        .contact-info h2 {
            color: var(--primary-color);
            margin-bottom: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .contact-item i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 1rem;
            width: 30px;
        }

        .contact-item div h3 {
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .contact-item div p {
            color: var(--text-gray);
        }

        .contact-form {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-soft);
        }

        .contact-form h2 {
            color: var(--primary-color);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: #fff;
            color: #000;
            font-family: inherit;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .contact-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>
</head>
<body>
    <div id="header"></div>

    <section class="contact-hero">
        <h1>Contact Us</h1>
        <p>Get in touch with us for any questions or feedback</p>
    </section>

    <div class="contact-content">
        <div class="contact-info">
            <h2>Get in Touch</h2>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h3>Address</h3>
                    <p>180 Ang Mo Kio Avenue 8<br>Singapore 569830</p>
                </div>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <div>
                    <h3>Phone</h3>
                    <p>+65 6451 5115</p>
                </div>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h3>Email</h3>
                    <p>spicefusion0711@gmail.com</p>
                </div>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <div>
                    <h3>Opening Hours</h3>
                    <p>Monday - Sunday<br>10:00 AM - 10:00 PM</p>
                </div>
            </div>
        </div>

        <div class="contact-form">
            <h2>Send us a Message</h2>
            <div id="formMessage" style="display: none; padding: 1rem; margin-bottom: 1rem; border-radius: 6px; text-align: center;"></div>
            <form id="contactForm">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <button type="submit" class="submit-btn" id="submitBtn">Send Message</button>
            </form>
        </div>
    </div>

    <div id="footer"></div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const headerResponse = await fetch('../../includes/header.php');
                const footerResponse = await fetch('../../includes/footer.php');
                
                if (!headerResponse.ok || !footerResponse.ok) {
                    throw new Error('Failed to load header or footer');
                }
                
                const headerHtml = await headerResponse.text();
                const footerHtml = await footerResponse.text();
                
                document.getElementById('header').innerHTML = headerHtml;
                document.getElementById('footer').innerHTML = footerHtml;

                // Contact form handling
                const contactForm = document.getElementById('contactForm');
                const formMessage = document.getElementById('formMessage');
                const submitBtn = document.getElementById('submitBtn');

                contactForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // Disable submit button
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Sending...';
                    formMessage.style.display = 'none';

                    const formData = new FormData(this);

                    try {
                        const response = await fetch('contact_handler.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        // Show message
                        formMessage.style.display = 'block';
                        formMessage.textContent = result.message;
                        
                        if (result.success) {
                            formMessage.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
                            formMessage.style.border = '1px solid #28a745';
                            formMessage.style.color = '#28a745';
                            this.reset();
                        } else {
                            formMessage.style.backgroundColor = 'rgba(255, 75, 43, 0.1)';
                            formMessage.style.border = '1px solid #FF4B2B';
                            formMessage.style.color = '#FF4B2B';
                        }
                    } catch (error) {
                        formMessage.style.display = 'block';
                        formMessage.style.backgroundColor = 'rgba(255, 75, 43, 0.1)';
                        formMessage.style.border = '1px solid #FF4B2B';
                        formMessage.style.color = '#FF4B2B';
                        formMessage.textContent = 'An error occurred. Please try again later.';
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Send Message';
                    }
                });
            } catch (error) {
                console.error('Error loading header or footer:', error);
            }
        });
    </script>
</body>
</html> 