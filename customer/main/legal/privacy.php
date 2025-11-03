<?php
require_once '../../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../includes/styles.css">
    <style>
        .legal-content {
            max-width: 800px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .legal-content h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .legal-content h2 {
            color: var(--text-light);
            font-size: 1.5rem;
            margin: 2rem 0 1rem 0;
        }

        .legal-content p {
            color: var(--text-gray);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .legal-content ul {
            color: var(--text-gray);
            margin-bottom: 1rem;
            padding-left: 2rem;
        }

        .legal-content li {
            margin-bottom: 0.5rem;
        }

        .last-updated {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            text-align: center;
            color: var(--text-gray);
        }
    </style>
</head>
<body>
    <div id="header"></div>

    <div class="legal-content">
        <h1>Privacy Policy</h1>
        
        <div class="last-updated">
            <p><strong>Last Updated:</strong> December 2024</p>
        </div>

        <h2>1. Information We Collect</h2>
        <p>We collect information you provide directly to us, such as when you create an account, place an order, or contact us. This may include:</p>
        <ul>
            <li>Name, email address, and phone number</li>
            <li>Delivery address and payment information</li>
            <li>Order history and preferences</li>
            <li>Communications with us</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use the information we collect to:</p>
        <ul>
            <li>Process and fulfill your orders</li>
            <li>Communicate with you about your orders</li>
            <li>Send you marketing communications (with your consent)</li>
            <li>Improve our services and website</li>
            <li>Comply with legal obligations</li>
        </ul>

        <h2>3. Information Sharing</h2>
        <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except in the following circumstances:</p>
        <ul>
            <li>To process payments (payment processors)</li>
            <li>To deliver orders (delivery partners)</li>
            <li>To comply with legal requirements</li>
            <li>To protect our rights and safety</li>
        </ul>

        <h2>4. Data Security</h2>
        <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.</p>

        <h2>5. Cookies and Tracking</h2>
        <p>We use cookies and similar technologies to:</p>
        <ul>
            <li>Remember your preferences</li>
            <li>Analyze website traffic</li>
            <li>Improve user experience</li>
            <li>Provide personalized content</li>
        </ul>

        <h2>6. Your Rights</h2>
        <p>You have the right to:</p>
        <ul>
            <li>Access your personal information</li>
            <li>Correct inaccurate information</li>
            <li>Request deletion of your information</li>
            <li>Opt-out of marketing communications</li>
            <li>Withdraw consent at any time</li>
        </ul>

        <h2>7. Data Retention</h2>
        <p>We retain your personal information for as long as necessary to provide our services and comply with legal obligations. We will delete your information when it is no longer needed.</p>

        <h2>8. Children's Privacy</h2>
        <p>Our services are not intended for children under 13. We do not knowingly collect personal information from children under 13. If you believe we have collected information from a child under 13, please contact us.</p>

        <h2>9. Changes to This Policy</h2>
        <p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last Updated" date.</p>

        <h2>10. Contact Us</h2>
        <p>If you have any questions about this privacy policy, please contact us at:</p>
        <p>Email: hello@spicefusion.com<br>
        Phone: +65 6451 5115<br>
        Address: 180 Ang Mo Kio Avenue 8, Singapore 569830</p>
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
            } catch (error) {
                console.error('Error loading header or footer:', error);
            }
        });
    </script>
</body>
</html> 