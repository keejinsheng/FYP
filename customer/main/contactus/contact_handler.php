<?php
require_once '../../../config/database.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Required files
require __DIR__ . '/../../phpmailer/src/Exception.php';
require __DIR__ . '/../../phpmailer/src/PHPMailer.php';
require __DIR__ . '/../../phpmailer/src/SMTP.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $response['message'] = 'Please fill in all fields.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address.';
        echo json_encode($response);
        exit;
    }

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pajegi4026@gmail.com';
        // App Password: qdytoggxzhtttxox
        $mail->Password   = 'qdytoggxzhtttxox';
        // Try TLS on port 587 first (more reliable)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPDebug  = 0; // Set to 2 for detailed debug output
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('pajegi4026@gmail.com', 'Spice Fusion Website');
        $mail->addAddress('pajegi4026@gmail.com'); // Send to this email
        $mail->addReplyTo($email, $name); // Reply to sender

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Contact Form: ' . $subject;
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #FF4B2B; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .info-row { margin-bottom: 15px; }
                    .label { font-weight: bold; color: #FF4B2B; }
                    .message-box { background: white; padding: 15px; border-left: 4px solid #FF4B2B; margin-top: 15px; }
                    .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>New Contact Form Message</h2>
                    </div>
                    <div class='content'>
                        <div class='info-row'>
                            <span class='label'>Name:</span> " . htmlspecialchars($name) . "
                        </div>
                        <div class='info-row'>
                            <span class='label'>Email:</span> " . htmlspecialchars($email) . "
                        </div>
                        <div class='info-row'>
                            <span class='label'>Subject:</span> " . htmlspecialchars($subject) . "
                        </div>
                        <div class='message-box'>
                            <span class='label'>Message:</span><br>
                            " . nl2br(htmlspecialchars($message)) . "
                        </div>
                    </div>
                    <div class='footer'>
                        <p>This message was sent from the Spice Fusion contact form.</p>
                        <p>Reply directly to this email to respond to " . htmlspecialchars($name) . "</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        // Plain text version
        $mail->AltBody = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";

        $mail->send();
        $response['success'] = true;
        $response['message'] = 'Thank you for your message! We will get back to you soon.';
    } catch (Exception $e) {
        // More detailed error message
        $errorMsg = $mail->ErrorInfo;
        if (strpos($errorMsg, 'Could not authenticate') !== false || strpos($errorMsg, 'authentication') !== false) {
            $response['message'] = 'Email authentication failed. Please verify: 1) Gmail App Password is correct for pajegi4026@gmail.com, 2) Two-factor authentication is enabled, 3) App Password was generated correctly.';
        } else {
            $response['message'] = 'Message could not be sent. Error: ' . $errorMsg;
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
