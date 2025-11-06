<?php
// ---- DB credentials (edit these) ----
$host = "localhost";      
$user = "root";           
$pass = "wn56jaMSamgA@@";              
$dbname = "databases";  
// -------------------------------------

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed.");
}
?>