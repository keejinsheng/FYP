<?php
require_once '../../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Spice Fusion</title>
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
        <h1>Terms of Service</h1>
        
        <div class="last-updated">
            <p><strong>Last Updated:</strong> December 2024</p>
        </div>

        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using the Spice Fusion website and services, you accept and agree to be bound by the terms and provision of this agreement.</p>

        <h2>2. Use License</h2>
        <p>Permission is granted to temporarily download one copy of the materials (information or software) on Spice Fusion's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
        <ul>
            <li>modify or copy the materials;</li>
            <li>use the materials for any commercial purpose or for any public display (commercial or non-commercial);</li>
            <li>attempt to decompile or reverse engineer any software contained on Spice Fusion's website;</li>
            <li>remove any copyright or other proprietary notations from the materials; or</li>
            <li>transfer the materials to another person or "mirror" the materials on any other server.</li>
        </ul>

        <h2>3. Disclaimer</h2>
        <p>The materials on Spice Fusion's website are provided on an 'as is' basis. Spice Fusion makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

        <h2>4. Limitations</h2>
        <p>In no event shall Spice Fusion or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Spice Fusion's website, even if Spice Fusion or a Spice Fusion authorized representative has been notified orally or in writing of the possibility of such damage.</p>

        <h2>5. Accuracy of Materials</h2>
        <p>The materials appearing on Spice Fusion's website could include technical, typographical, or photographic errors. Spice Fusion does not warrant that any of the materials on its website are accurate, complete or current. Spice Fusion may make changes to the materials contained on its website at any time without notice.</p>

        <h2>6. Links</h2>
        <p>Spice Fusion has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by Spice Fusion of the site. Use of any such linked website is at the user's own risk.</p>

        <h2>7. Modifications</h2>
        <p>Spice Fusion may revise these terms of service for its website at any time without notice. By using this website you are agreeing to be bound by the then current version of these Terms of Service.</p>

        <h2>8. Governing Law</h2>
        <p>These terms and conditions are governed by and construed in accordance with the laws of Singapore and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>

        <h2>9. Contact Information</h2>
        <p>If you have any questions about these Terms of Service, please contact us at:</p>
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