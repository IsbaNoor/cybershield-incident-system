<?php
$password = 'Password123!';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Password: " . $password . "<br>";
echo "Hash: " . $hash;
?>