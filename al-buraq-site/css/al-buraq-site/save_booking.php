<?php
// save_booking.php

// Debug helpers (local dev only): show and log errors
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Global exception and error handlers to capture unexpected failures
set_exception_handler(function($e){
  http_response_code(500);
  error_log("save_booking exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", 3, __DIR__ . '/save_booking.log');
  echo "Server error - internal exception: " . $e->getMessage();
  exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline){
  $msg = "PHP Error [$errno] $errstr in $errfile:$errline";
  error_log($msg . "\n", 3, __DIR__ . '/save_booking.log');
  http_response_code(500);
  echo "Server error - PHP error: " . $errstr;
  exit;
});

require "db.php";

// Helper: recreate table on demand (local dev)
$expected = [
  'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
  'name' => 'VARCHAR(120) NOT NULL',
  'email' => 'VARCHAR(160) NOT NULL',
  'phone' => 'VARCHAR(50) NOT NULL',
  'pickup_date' => 'DATE NOT NULL',
  'pickup_time' => 'TIME NULL',
  'pickup_location' => 'VARCHAR(255) NOT NULL',
  'dropoff_location' => 'VARCHAR(255) NOT NULL',
  'shipment_type' => 'VARCHAR(50) NULL',
  'weight' => 'DECIMAL(10,2) NOT NULL',
  'dimensions' => 'VARCHAR(80) NULL',
  'quantity' => 'INT DEFAULT 1',
  'declared_value' => 'DECIMAL(12,2) NULL',
  'notes' => 'TEXT NULL',
  'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
];

// Recreate table if requested (visit: save_booking.php?recreate=1)
if (isset($_GET['recreate']) && $_GET['recreate'] === '1') {
  $pdo->exec("DROP TABLE IF EXISTS bookings");
  $cols = [];
  foreach ($expected as $col => $def) $cols[] = "`$col` $def";
  $sql = "CREATE TABLE bookings (" . implode(",\n  ", $cols) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
  $pdo->exec($sql);
  exit("Bookings table recreated");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  exit("Access denied");
}

// Ensure required columns exist and align types where possible
$existingCols = [];
$stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings'");
$stmt->execute();
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $c) $existingCols[] = $c;

if (empty($existingCols)) {
  // Create the table if it does not exist
  $cols = [];
  foreach ($expected as $col => $def) $cols[] = "`$col` $def";
  $sql = "CREATE TABLE bookings (" . implode(",\n  ", $cols) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
  $pdo->exec($sql);
} else {
  // Add missing columns
  foreach ($expected as $col => $def) {
    if (!in_array($col, $existingCols)) {
      try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN `$col` $def");
      } catch (PDOException $e) {
        // Non-fatal; continue
      }
    }
  }
  // Attempt to modify column types to match expected (skip id modification)
  foreach ($expected as $col => $def) {
    if ($col === 'id') continue;
    try {
      $pdo->exec("ALTER TABLE bookings MODIFY COLUMN `$col` $def");
    } catch (PDOException $e) {
      // Non-fatal; ignore modification errors in local dev
    }
  }
}

// Collect POST data
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$pickup_date = trim($_POST["pickup_date"] ?? "");
$pickup_time = trim($_POST["pickup_time"] ?? null);
$pickup_location = trim($_POST["pickup_location"] ?? "");
$dropoff_location = trim($_POST["dropoff_location"] ?? "");
$shipment_type = trim($_POST["shipment_type"] ?? "");
$weight = isset($_POST["weight"]) && $_POST["weight"] !== '' ? number_format((float)$_POST["weight"], 2, '.', '') : null;
$dimensions = trim($_POST["dimensions"] ?? "");
$quantity = isset($_POST["quantity"]) && $_POST["quantity"] !== '' ? (int)$_POST["quantity"] : 1;
$declared_value = isset($_POST["declared_value"]) && $_POST["declared_value"] !== '' ? number_format((float)$_POST["declared_value"], 2, '.', '') : null;
$notes = trim($_POST["notes"] ?? "");

// Basic validation for required fields
if (
  $name === "" ||
  $email === "" ||
  $phone === "" ||
  $pickup_date === "" ||
  $pickup_location === "" ||
  $dropoff_location === "" ||
  $weight === null
) {
  http_response_code(400);
  exit("Please fill all required fields");
}

// Insert booking using prepared statement
$sql = "INSERT INTO bookings (name, email, phone, pickup_date, pickup_time, pickup_location, dropoff_location, shipment_type, weight, dimensions, quantity, declared_value, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
try {
  $stmt->execute([
    $name,
    $email,
    $phone,
    $pickup_date,
    $pickup_time ?: null,
    $pickup_location,
    $dropoff_location,
    $shipment_type ?: null,
    $weight,
    $dimensions ?: null,
    $quantity,
    $declared_value,
    $notes ?: null
  ]);

  echo "Booking saved successfully";
} catch (PDOException $e) {
  http_response_code(500);
  // Log the detailed PDO error for debugging
  error_log("save_booking PDOException: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", 3, __DIR__ . '/save_booking.log');
  exit("Server error - Could not save booking: " . $e->getMessage());
}

