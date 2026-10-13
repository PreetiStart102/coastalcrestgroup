<?php
/**
 * Coastal Crest Group - Production Contact & Career Form Mail Handler
 * Domain: coastalcrestgroup.in
 * Primary Recipient: info@coastalcrestgroup.in
 * Authenticated via Google Workspace SMTP (SSL:465 / TLS:587)
 */

header('Content-Type: application/json; charset=UTF-8');

// Configuration
$toEmail     = 'info@coastalcrestgroup.in';
$smtpUser    = 'info@coastalcrestgroup.in';
$smtpPass    = 'scpxydhldaneroax'; // Google Workspace App Password
$smtpHost    = 'smtp.gmail.com';
$brandName   = 'Coastal Crest Group';
$siteDomain  = 'coastalcrestgroup.in';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid request method. Only POST is accepted.'
    ]);
    exit;
}

$formType = isset($_POST['form_type']) ? trim($_POST['form_type']) : 'contact';

// Sanitize helper
function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Robust SMTP Email Sender using direct socket stream
 * Authenticates with Google Workspace using SSL port 465 (or STARTTLS port 587)
 */
function sendSmtpMail($to, $subject, $htmlBody, $replyTo = '', $replyToName = '', $attachment = null) {
    global $smtpHost, $smtpUser, $smtpPass, $brandName;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ]);

    // Try SSL on port 465 first
    $socket = @stream_socket_client("ssl://{$smtpHost}:465", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    $useStartTls = false;

    // Fallback to port 587 if 465 is blocked
    if (!$socket) {
        $socket = @stream_socket_client("tcp://{$smtpHost}:587", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        $useStartTls = true;
    }

    if (!$socket) {
        return ['success' => false, 'error' => "SMTP connection failed: {$errno} {$errstr}"];
    }

    $readResp = function($expectedCode) use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                break;
            }
        }
        $code = substr($response, 0, 3);
        return [
            'ok'       => ($code == $expectedCode),
            'response' => $response,
            'code'     => $code
        ];
    };

    $init = $readResp(220);
    if (!$init['ok']) { fclose($socket); return ['success' => false, 'error' => 'Greeting error: ' . $init['response']]; }

    fputs($socket, "EHLO coastalcrestgroup.in\r\n");
    $ehlo = $readResp(250);
    if (!$ehlo['ok']) { fclose($socket); return ['success' => false, 'error' => 'EHLO error: ' . $ehlo['response']]; }

    if ($useStartTls) {
        fputs($socket, "STARTTLS\r\n");
        $tls = $readResp(220);
        if ($tls['ok']) {
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO coastalcrestgroup.in\r\n");
            $readResp(250);
        }
    }

    fputs($socket, "AUTH LOGIN\r\n");
    $auth = $readResp(334);
    if (!$auth['ok']) { fclose($socket); return ['success' => false, 'error' => 'AUTH error: ' . $auth['response']]; }

    fputs($socket, base64_encode($smtpUser) . "\r\n");
    $user = $readResp(334);
    if (!$user['ok']) { fclose($socket); return ['success' => false, 'error' => 'Username rejected: ' . $user['response']]; }

    fputs($socket, base64_encode($smtpPass) . "\r\n");
    $pass = $readResp(235);
    if (!$pass['ok']) { fclose($socket); return ['success' => false, 'error' => 'Password rejected: ' . $pass['response']]; }

    fputs($socket, "MAIL FROM:<{$smtpUser}>\r\n");
    $mailFrom = $readResp(250);
    if (!$mailFrom['ok']) { fclose($socket); return ['success' => false, 'error' => 'MAIL FROM error: ' . $mailFrom['response']]; }

    fputs($socket, "RCPT TO:<{$to}>\r\n");
    $rcptTo = $readResp(250);
    if (!$rcptTo['ok']) { fclose($socket); return ['success' => false, 'error' => 'RCPT TO error: ' . $rcptTo['response']]; }

    fputs($socket, "DATA\r\n");
    $dataCmd = $readResp(354);
    if (!$dataCmd['ok']) { fclose($socket); return ['success' => false, 'error' => 'DATA command error: ' . $dataCmd['response']]; }

    $boundary = "----=_Part_" . md5(uniqid(time()));
    $headers = [];
    $headers[] = "Date: " . date('r');
    $headers[] = "From: =?UTF-8?B?" . base64_encode($brandName) . "?= <{$smtpUser}>";
    $headers[] = "To: <{$to}>";
    if (!empty($replyTo)) {
        $rName = !empty($replyToName) ? "=?UTF-8?B?" . base64_encode($replyToName) . "?= " : "";
        $headers[] = "Reply-To: {$rName}<{$replyTo}>";
    }
    $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
    $headers[] = "MIME-Version: 1.0";

    $messageContent = "";
    if ($attachment && !empty($attachment['content']) && !empty($attachment['filename'])) {
        $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

        $messageContent .= "--{$boundary}\r\n";
        $messageContent .= "Content-Type: text/html; charset=UTF-8\r\n";
        $messageContent .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $messageContent .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        $safeFilename = str_replace(['"', "\r", "\n"], '', $attachment['filename']);
        $messageContent .= "--{$boundary}\r\n";
        $messageContent .= "Content-Type: application/octet-stream; name=\"{$safeFilename}\"\r\n";
        $messageContent .= "Content-Description: {$safeFilename}\r\n";
        $messageContent .= "Content-Disposition: attachment; filename=\"{$safeFilename}\"\r\n";
        $messageContent .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $messageContent .= chunk_split(base64_encode($attachment['content'])) . "\r\n";
        $messageContent .= "--{$boundary}--\r\n";
    } else {
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";
        $messageContent .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    }

    $rawEmail = implode("\r\n", $headers) . "\r\n\r\n" . $messageContent;
    $rawEmail = preg_replace('/^\./m', '..', $rawEmail);

    fputs($socket, $rawEmail . "\r\n.\r\n");
    $dataSent = $readResp(250);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    if ($dataSent['ok']) {
        return ['success' => true, 'response' => $dataSent['response']];
    } else {
        return ['success' => false, 'error' => 'Message send failed: ' . $dataSent['response']];
    }
}

// -------------------------------------------------------------
// 1. CAREER APPLICATION PROCESSING
// -------------------------------------------------------------
if ($formType === 'career') {
    $position      = isset($_POST['position']) ? cleanInput($_POST['position']) : 'General Application';
    $name          = isset($_POST['name']) ? cleanInput($_POST['name']) : '';
    $email         = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone         = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
    $experience    = isset($_POST['experience']) ? cleanInput($_POST['experience']) : '';
    $qualification = isset($_POST['qualification']) ? cleanInput($_POST['qualification']) : '';
    $location      = isset($_POST['location']) ? cleanInput($_POST['location']) : '';
    $summary       = isset($_POST['summary']) ? cleanInput($_POST['summary']) : 'None provided';

    if (empty($name) || empty($email) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields (Name, Email, Phone).']);
        exit;
    }

    $subject = "New Career Application: " . $position . " - " . $name . " [" . $brandName . "]";

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
                Received from Coastal Crest Careers Portal &bull; Delivered to info@coastalcrestgroup.in
            </div>
        </div>
    </body>
    </html>";

    // Handle File Attachment
    $attachment = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['resume']['tmp_name'];
        $fileName = basename($_FILES['resume']['name']);
        $fileSize = $_FILES['resume']['size'];

        // Max 10MB
        if ($fileSize <= 10 * 1024 * 1024) {
            $attachment = [
                'filename' => $fileName,
                'content'  => file_get_contents($fileTmp)
            ];
        }
    }

    // Send via Authenticated Google SMTP
    $smtpResult = sendSmtpMail($toEmail, $subject, $body, $email, $name, $attachment);

    // Fallback to native mail if SMTP fails
    if (!$smtpResult['success']) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: Coastal Crest Careers <{$smtpUser}>\r\n";
        $headers .= "Reply-To: {$email}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        @mail($toEmail, $subject, $body, $headers);
    }

    // Save lead backup log
    $hasFileStr = $attachment ? " [Resume: {$attachment['filename']}]" : " [No Resume]";
    $logEntry = date('Y-m-d H:i:s') . " | CAREER | " . $name . " | " . $email . " | " . $phone . " | " . $position . $hasFileStr . "\n";
    @file_put_contents(__DIR__ . '/.mail_leads.log', $logEntry, FILE_APPEND | LOCK_EX);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Thank you! Your career application has been received and forwarded to Coastal Crest HR at info@coastalcrestgroup.in.'
    ]);
    exit;

// -------------------------------------------------------------
// 2. CONTACT FORM INQUIRY PROCESSING
// -------------------------------------------------------------
} else {
    $name        = isset($_POST['name']) ? cleanInput($_POST['name']) : '';
    $email       = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $phone       = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
    $sector      = isset($_POST['sector']) ? cleanInput($_POST['sector']) : 'General Inquiry';
    $msgSubject  = isset($_POST['subject']) ? cleanInput($_POST['subject']) : 'General Inquiry';
    $messageText = isset($_POST['message']) ? cleanInput($_POST['message']) : '';

    if (empty($name) || empty($email) || empty($messageText)) {
        echo json_encode(['status' => 'error', 'message' => 'Please complete all required fields (Name, Email, Message).']);
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
                Received from Coastal Crest Contact Form &bull; Delivered to info@coastalcrestgroup.in
            </div>
        </div>
    </body>
    </html>";

    // Send via Authenticated Google SMTP
    $smtpResult = sendSmtpMail($toEmail, $subject, $body, $email, $name);

    // Fallback to native mail if SMTP fails
    if (!$smtpResult['success']) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: Coastal Crest Website <{$smtpUser}>\r\n";
        $headers .= "Reply-To: {$email}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        @mail($toEmail, $subject, $body, $headers);
    }

    // Save lead backup log
    $logEntry = date('Y-m-d H:i:s') . " | CONTACT | " . $name . " | " . $email . " | " . $phone . " | " . $sector . " | " . $msgSubject . "\n";
    @file_put_contents(__DIR__ . '/.mail_leads.log', $logEntry, FILE_APPEND | LOCK_EX);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Thank you for reaching out! Your message has been received and forwarded to our team at info@coastalcrestgroup.in.'
    ]);
    exit;
}
?>
