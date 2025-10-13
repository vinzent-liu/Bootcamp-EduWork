<?php
// Konfigurasi database dan mulai session
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'e_commerce';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    die('Database connection error: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// Helper: escape output (fungsi untuk mengamankan output)
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
