<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/platform.php';

$db = gawdee_db();

$newPassword = 'AdminPassword123!';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

// Update muhammedlameesmv@gmail.com password
$stmt = $db->prepare("UPDATE users SET email='muhammedlameesmv@gmail.com', password_hash = ? WHERE id = 4");
$stmt->execute([$hash]);

echo "Admin password reset successfully for ID 4!\n";
echo "Email: muhammedlameesmv@gmail.com\n";
echo "Password: AdminPassword123!\n";
