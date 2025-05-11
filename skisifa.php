<?php
// Mailer application with modern UI and error handling

// Configuration
$logFile = 'mailer.log';

// Error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    $errorMsg = date('[Y-m-d H:i:s]') . " Error [$errno]: $errstr in $errfile on line $errline\n";
    file_put_contents($logFile, $errorMsg, FILE_APPEND);
    return true;
});

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senderName = $_POST['sender_name'] ?? '';
    $fromEmail = $_POST['from_email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $emailList = $_POST['email_list'] ?? '';
    $htmlContent = $_POST['html_content'] ?? '';
    
    // Validate inputs
    $errors = [];
    if (empty($senderName)) $errors[] = 'Sender name is required';
    if (empty($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid from email is required';
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($emailList)) $errors[] = 'Email list is required';
    
    if (empty($errors)) {
        $emails = array_map('trim', explode(',', $emailList));
        
        foreach ($emails as $to) {
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $headers = "From: $senderName <$fromEmail>\r\n";
                $headers .= "Content-type: text/html; charset=utf-8\r\n";
                
                $success = mail($to, $subject, $htmlContent, $headers);
                
                $logMsg = date('[Y-m-d H:i:s]') . " - Mail sent to $to: " . ($success ? 'SUCCESS' : 'FAILED') . "\n";
                file_put_contents($logFile, $logMsg, FILE_APPEND);
            }
        }
        
        $response = ['success' => true, 'message' => 'Emails sent successfully'];
    } else {
        $response = ['success' => false, 'errors' => $errors];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Mailer</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background: #f5f7fa; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input[type="text"], input[type="email"], textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        textarea { min-height: 200px; }
        button { background: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        button:hover { background: #2980b9; }
        .logs { margin-top: 30px; background: #f8f9fa; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto; }
        .log-entry { margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid #eee; }
        .error { color: #e74c3c; }
        .success { color: #2ecc71; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Professional Mailer</h1>
        
        <form id="mailerForm">
            <div class="form-group">
                <label for="sender_name">Sender Name</label>
                <input type="text" id="sender_name" name="sender_name" required>
            </div>
            
            <div class="form-group">
                <label for="from_email">From Email</label>
                <input type="email" id="from_email" name="from_email" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="email_list">Email List (comma separated)</label>
                <textarea id="email_list" name="email_list" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="html_content">HTML Content</label>
                <textarea id="html_content" name="html_content" required></textarea>
            </div>
            
            <button type="submit">Send Emails</button>
        </form>
        
        <div class="logs" id="logs">
            <h3>Recent Activity</h3>
            <?php
            if (file_exists($logFile)) {
                $logs = array_reverse(file($logFile));
                foreach ($logs as $log) {
                    $logClass = strpos($log, 'FAILED') !== false ? 'error' : 'success';
                    echo '<div class="log-entry ' . $logClass . '">' . htmlspecialchars($log) . '</div>';
                }
            }
            ?>
        </div>
    </div>
    
    <script>
        document.getElementById('mailerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Emails sent successfully!');
                location.reload();
            } else {
                alert('Error: ' + result.errors.join('\n'));
            }
        });
    </script>
</body>
</html>