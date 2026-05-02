<?php
/**
 * Password Hash Generator
 * Run this script ONCE after importing seed.sql to fix password hashes.
 * 
 * Usage: php hash_passwords.php
 * Or access via browser: http://localhost/it-helpdesk-ticketing/hash_passwords.php
 */

require_once __DIR__ . '/config/database.php';

$passwords = [
    'admin@smartdesk.local' => 'Admin123!',
    'staff@smartdesk.local' => 'Staff123!',
    'user@smartdesk.local' => 'User123!',
];

$pdo = getDBConnection();
$updated = 0;

// Update all users with the same default password for demo
$allUsers = $pdo->query("SELECT user_id, email FROM users")->fetchAll();

foreach ($allUsers as $user) {
    $password = $passwords[$user['email']] ?? 'User123!';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
        ->execute([$hash, $user['user_id']]);
    $updated++;
}

if (php_sapi_name() === 'cli') {
    echo "Updated $updated user password hashes.\n";
    echo "Default passwords:\n";
    echo "  Admin: admin@smartdesk.local / Admin123!\n";
    echo "  Staff: staff@smartdesk.local / Staff123!\n";
    echo "  User:  user@smartdesk.local  / User123!\n";
} else {
    echo "<h2>Password Hashes Updated</h2>";
    echo "<p>Updated <strong>$updated</strong> users.</p>";
    echo "<p><strong>Default login accounts:</strong></p>";
    echo "<ul>";
    echo "<li>Admin: admin@smartdesk.local / Admin123!</li>";
    echo "<li>Staff: staff@smartdesk.local / Staff123!</li>";
    echo "<li>User: user@smartdesk.local / User123!</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    echo "<p style='color:red;'><strong>DELETE THIS FILE after use for security!</strong></p>";
}
