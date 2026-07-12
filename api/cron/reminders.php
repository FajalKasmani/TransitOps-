<?php
declare(strict_types=1);

// Restrict web requests without confirmation param to prevent accidental cron trigger
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    echo "This script is designed to be executed via CLI cron jobs.";
    exit;
}

require_once __DIR__ . '/../classes/Database.php';

use Api\Classes\Database;

try {
    $pdo = Database::getInstance();
    
    // Find driver licenses expiring in exactly 7 days
    $targetDate = date('Y-m-d', strtotime('+7 days'));
    
    $stmt = $pdo->prepare("
        SELECT id, name, license_number, license_category, license_expiry_date, email, contact_number 
        FROM drivers 
        WHERE license_expiry_date = :target_date AND is_deleted = 0
    ");
    $stmt->execute(['target_date' => $targetDate]);
    $expiringDrivers = $stmt->fetchAll();

    echo "Found " . count($expiringDrivers) . " driver licenses expiring on {$targetDate}.\n";

    foreach ($expiringDrivers as $driver) {
        $subject = "Compliance Alert: Driver License Expiry Warning";
        $message = "Dear Compliance Team / {$driver['name']},\n\n";
        $message .= "This is an automated notification from TransitOps.\n";
        $message .= "Driver: {$driver['name']}\n";
        $message .= "License Number: {$driver['license_number']}\n";
        $message .= "Category: {$driver['license_category']}\n";
        $message .= "Expiry Date: {$driver['license_expiry_date']} (Expiring in exactly 7 days).\n\n";
        $message .= "Please ensure the license renewal is completed to avoid suspension of duty.\n\n";
        $message .= "Regards,\nTransitOps Operations Platform";

        $sent = false;
        
        // 1. Attempt using PHPMailer if vendor autoload is present
        if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            try {
                require_once __DIR__ . '/../../vendor/autoload.php';
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                // Standard local configuration fallback
                $mail->isSMTP();
                $mail->Host = 'localhost';
                $mail->SMTPAuth = false;
                $mail->Port = 25;
                $mail->setFrom('noreply@transitops.com', 'TransitOps Alerts');
                
                if ($driver['email']) {
                    $mail->addAddress($driver['email'], $driver['name']);
                }
                $mail->addAddress('safety@transitops.com', 'Safety Office');
                
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->send();
                $sent = true;
                echo "Email dispatched via PHPMailer for driver #{$driver['id']}.\n";
            } catch (\Exception $e) {
                echo "PHPMailer failed: " . $e->getMessage() . ". Falling back to mail().\n";
            }
        }

        // 2. Native mail() fallback
        if (!$sent && $driver['email']) {
            $headers = "From: noreply@transitops.com\r\nReply-To: noreply@transitops.com\r\n";
            $sent = @mail($driver['email'], $subject, $message, $headers);
            if ($sent) {
                echo "Email dispatched via native mail() for driver #{$driver['id']}.\n";
            }
        }

        // 3. Log notification action to audit trail
        $logStmt = $pdo->prepare("
            INSERT INTO action_logs (user_id, entity, entity_id, action, details) 
            VALUES (NULL, 'drivers', :driver_id, 'LICENSE_EXPIRY_NOTIFICATION', :details)
        ");
        $details = "License expiry warning sent for license {$driver['license_number']}. Expiry: {$driver['license_expiry_date']}";
        $logStmt->execute([
            'driver_id' => $driver['id'],
            'details' => $details
        ]);
        echo "Logged notification event to audit trail for driver #{$driver['id']}.\n";
    }

    echo "Cron job execution completed successfully.\n";

} catch (\Exception $e) {
    echo "Cron execution failed: " . $e->getMessage() . "\n";
}
