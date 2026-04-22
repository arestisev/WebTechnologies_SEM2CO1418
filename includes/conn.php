<?php
$host = "localhost";
$user = "aevangelou2";
$password = "BgNtbB9EFQ";
$database = "aevangelou2";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed");
}
