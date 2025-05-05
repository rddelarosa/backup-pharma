<?php
date_default_timezone_set('Asia/Manila');

$servername = "localhost";
$uname = "root";
$password = "";
$dbname = "pharmasync";

$conn = mysqli_connect($servername, $uname, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
