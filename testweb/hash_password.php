<?php
// hash_password.php
$password = 'admin123';
$hashed = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password<br>";
echo "Hashed: $hashed<br>";

// You can also hash multiple passwords
$passwords = ['admin123', 'password123', 'secret123'];
foreach ($passwords as $pwd) {
    echo "<br>Password: $pwd<br>";
    echo "Hash: " . password_hash($pwd, PASSWORD_DEFAULT) . "<br>";
}
// Create a temporary file: hash_password.php
echo password_hash('admin123', PASSWORD_DEFAULT);
?>