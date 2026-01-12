<?php
require_once '../../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../includes/styles.css">
    <style>
        .about-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('/DWP/images/landing_page.png');
            background-size: cover;
            background-position: center;
            padding: 6rem 0;
            text-align: center;
            color: var(--text-light);
        }

        .about-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .about-hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            color: var(--text-gray);
        }

        .about-content {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .about-section {
            margin-bottom: 4rem;
        }

        .about-section h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .about-section p {
            color: var(--text-gray);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .team-member {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow-soft);
        }

        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }

        .team-member h3 {
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .team-member p {
            color: var(--text-gray);
            margin-bottom: 1rem;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .value-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow-soft);
        }

        .value-card i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .value-card h3 {
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .value-card p {
            color: var(--text-gray);
        }
    </style>
</head>
<body>
    <div id="header"></div>

    <section class="about-hero">
        <h1>About Spice Fusion</h1>
        <p>Bringing the authentic flavors of Indonesia and China to your table</p>
    </section>

    <div class="about-content">
        <div class="about-section">
            <h2>Our Story</h2>
            <p>Spice Fusion was born from a passion for authentic Asian cuisine. Our journey began with a simple dream: to bring the rich, diverse flavors of Indonesia and China to food lovers everywhere.</p>
            <p>Founded by culinary enthusiasts who spent years traveling through Asia, learning traditional recipes and cooking techniques, Spice Fusion combines the best of both worlds to create a unique dining experience.</p>
        </div>

        <div class="about-section">
            <h2>Our Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <i class="fas fa-heart"></i>
                    <h3>Authenticity</h3>
                    <p>We stay true to traditional recipes and cooking methods, ensuring every dish tastes like it was made in its country of origin.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-leaf"></i>
                    <h3>Fresh Ingredients</h3>
                    <p>We source only the freshest, highest quality ingredients to ensure the best possible taste and nutritional value.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-users"></i>
                    <h3>Community</h3>
                    <p>We believe in building a community around good food, bringing people together through shared culinary experiences.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-star"></i>
                    <h3>Excellence</h3>
                    <p>We strive for excellence in every aspect of our service, from food preparation to customer experience.</p>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>Our Team</h2>
            <div class="team-grid">
                <div class="team-member">
                    <img src="../../../images/Shaun.jpg" alt="Shaun">
                    <h3>CHUA SHEN LIN SHAUN</h3>
                    <p>Manager</p>
                    <p>Specializing in Indonesian cuisine with over 10 years of experience in traditional cooking methods.</p>
                </div>
                <div class="team-member">
                    <img src="../../../images/KEE.jpg" alt="KEE">
                    <h3>KEE JIN SHENG</h3>
                    <p>CEO</p>
                    <p>Expert in Chinese cuisine, bringing authentic flavors and techniques from various regions of China.</p>
                </div>
                <div class="team-member">
                    <img src="../../../images/LIM.jpg" alt="LIM">
                    <h3>LIM XING YI</h3>
                    <p>Manager</p>
                    <p>Master of fusion cuisine, creating innovative dishes that blend the best of both culinary traditions.</p>
                </div>
            </div>
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
            } catch (error) {
                console.error('Error loading header or footer:', error);
            }
        });
    </script>
</body>
</html> 