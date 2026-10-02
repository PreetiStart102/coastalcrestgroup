<?php
/**
 * Coastal Crest Group - Production Contact & Career Form Mail Handler
 * Domain: coastalcrestgroup.in
 * Recipient: coastalcrestinfradevelopers@gmail.com
 */

header('Content-Type: application/json; charset=UTF-8');

// Target recipient email address
$toEmail = 'coastalcrestinfradevelopers@gmail.com';
$brandName = 'Coastal Crest Group';
$siteDomain = 'coastalcrestgroup.in';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Only POST is accepted.'
    ]);
    exit;
}

$formType = isset($_POST['form_type']) ? trim($_POST['form_type']) : 'contact';

// Sanitize helper
function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

if ($formType === 'career') {
    // Process Career Application
    $position = isset($_POST['position']) ? cleanInput($_POST['position']) : 'General Application';
    $name = isset($_POST['name']) ? cleanInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
    $experience = isset($_POST['experience']) ? cleanInput($_POST['experience']) : '';
    $qualification = isset($_POST['qualification']) ? cleanInput($_POST['qualification']) : '';
    $location = isset($_POST['location']) ? cleanInput($_POST['location']) : '';
    $summary = isset($_POST['summary']) ? cleanInput($_POST['summary']) : 'None provided';

    if (empty($name) || empty($email) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields (Name, Email, Phone).']);
        exit;
    }

    $subject = "New Career Application: " . $position . " - " . $name . " [" . $brandName . "]";

    // Email Body (HTML)
    $body = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; color: #1e293b; }
            .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-top: 5px solid #001659; }
            .header { background: #001659; color: #ffffff; padding: 24px; text-align: center; }
            .header h2 { margin: 0 0 6px; font-size: 22px; color: #ffffff; }
            .badge { background: #ea580c; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
            .content { padding: 28px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { text-align: left; padding: 10px 12px; background: #f8fafc; color: #001659; border-bottom: 1px solid #e2e8f0; width: 35%; font-size: 13px; }
            td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
            .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Coastal Crest Group</h2>
                <span class='badge'>Career Application Received</span>
            </div>
            <div class='content'>
                <p>A new candidate has submitted their profile via the <strong>coastalcrestgroup.in</strong> careers portal:</p>
                <table>
                    <tr><th>Applied Position</th><td><strong>{$position}</strong></td></tr>
                    <tr><th>Candidate Name</th><td>{$name}</td></tr>
                    <tr><th>Email Address</th><td><a href='mailto:{$email}'>{$email}</a></td></tr>
                    <tr><th>Phone Number</th><td><a href='tel:{$phone}'>{$phone}</a></td></tr>
                    <tr><th>Total Experience</th><td>{$experience}</td></tr>
                    <tr><th>Highest Qualification</th><td>{$qualification}</td></tr>
                    <tr><th>Current Location</th><td>{$location}</td></tr>
                    <tr><th>Key Strengths / Bio</th><td>" . nl2br($summary) . "</td></tr>
                </table>
            </div>
            <div class='footer'>
                Received from Coastal Crest Careers Form • Website: https://coastalcrestgroup.in
            </div>
        </div>
    </body>
    </html>";

    // Handle File Attachment if present
    $hasAttachment = false;
    $boundary = md5(time());

    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['resume']['tmp_name'];
        $fileName = basename($_FILES['resume']['name']);
        $fileSize = $_FILES['resume']['size'];
        $fileType = $_FILES['resume']['type'];

        // Max 10MB
        if ($fileSize <= 10 * 1024 * 1024) {
            $handle = fopen($fileTmp, "r");
            $content = fread($handle, $fileSize);
            fclose($handle);
            $encodedContent = chunk_split(base64_encode($content));
            $hasAttachment = true;
        }
    }

    if ($hasAttachment) {
        // Headers with multipart/mixed
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: Coastal Crest Careers <no-reply@{$siteDomain}>\r\n";
        $headers .= "Reply-To: {$email}\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

        // Multipart message
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $body . "\r\n\r\n";

        // Attachment part
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: application/octet-stream; name=\"{$fileName}\"\r\n";
        $message .= "Content-Description: {$fileName}\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$fileName}\"; size={$fileSize};\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= $encodedContent . "\r\n\r\n";
        $message .= "--{$boundary}--";
    } else {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: Coastal Crest Careers <no-reply@{$siteDomain}>\r\n";
        $headers .= "Reply-To: {$email}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message = $body;
    }

    $mailSent = @mail($toEmail, $subject, $message, $headers);

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you! Your career application has been received and forwarded to Coastal Crest HR.'
    ]);
    exit;

} else {
    // Process Contact Form Inquiry
    $name = isset($_POST['name']) ? cleanInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
    $sector = isset($_POST['sector']) ? cleanInput($_POST['sector']) : 'General Inquiry';
    $msgSubject = isset($_POST['subject']) ? cleanInput($_POST['subject']) : 'General Inquiry';
    $messageText = isset($_POST['message']) ? cleanInput($_POST['message']) : '';

    if (empty($name) || empty($email) || empty($messageText)) {
        echo json_encode(['status' => 'error', 'message' => 'Please complete all required fields.']);
        exit;
    }

    $subject = "New Contact Inquiry: " . $msgSubject . " - " . $name . " [" . $brandName . "]";

    $body = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; color: #1e293b; }
            .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-top: 5px solid #001659; }
            .header { background: #001659; color: #ffffff; padding: 24px; text-align: center; }
            .header h2 { margin: 0 0 6px; font-size: 22px; color: #ffffff; }
            .badge { background: #ea580c; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
            .content { padding: 28px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { text-align: left; padding: 10px 12px; background: #f8fafc; color: #001659; border-bottom: 1px solid #e2e8f0; width: 35%; font-size: 13px; }
            td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
            .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Coastal Crest Group</h2>
                <span class='badge'>Website Contact Inquiry</span>
            </div>
            <div class='content'>
                <p>You have received a new business / tender inquiry from <strong>coastalcrestgroup.in</strong>:</p>
                <table>
                    <tr><th>Contact Name</th><td><strong>{$name}</strong></td></tr>
                    <tr><th>Email Address</th><td><a href='mailto:{$email}'>{$email}</a></td></tr>
                    <tr><th>Phone Number</th><td><a href='tel:{$phone}'>{$phone}</a></td></tr>
                    <tr><th>Sector of Interest</th><td>{$sector}</td></tr>
                    <tr><th>Subject</th><td>{$msgSubject}</td></tr>
                    <tr><th>Message Details</th><td>" . nl2br($messageText) . "</td></tr>
                </table>
            </div>
            <div class='footer'>
                Received from Coastal Crest Contact Form • Website: https://coastalcrestgroup.in
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "From: Coastal Crest Website <no-reply@{$siteDomain}>\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $mailSent = @mail($toEmail, $subject, $body, $headers);

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you for reaching out! Your message has been forwarded to our team at coastalcrestinfradevelopers@gmail.com.'
    ]);
    exit;
}
?>
