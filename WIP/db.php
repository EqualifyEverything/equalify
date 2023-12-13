<?php
// Database connection
$pdo = new PDO('mysql:host=v1-db;dbname=db', 'root', 'root');

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);