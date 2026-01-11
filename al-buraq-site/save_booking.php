<?php
require_once __DIR__ . '/db.php';

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$fields = [
    'name','email','phone','pickup_date','pickup_time','pickup_location','dropoff_location',
    'shipment_type','weight','dimensions','quantity','declared_value','notes'
];

$data = [];
foreach ($fields as $f) {
    $data[$f] = isset($_POST[$f]) ? trim($_POST[$f]) : null;
}

// Minimal validation for required fields
$required = ['name','email','phone','pickup_date','pickup_location','dropoff_location','weight'];
$errors = [];
foreach ($required as $r) {
    if (empty($data[$r])) {
        $errors[] = $r;
    }
}
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email_invalid';
}

if ($errors) {
    http_response_code(400);
    ?>
    <!doctype html>
    <html lang="en"><head><meta charset="utf-8"><title>Invalid submission</title></head><body>
    <h1>Invalid submission</h1>
    <p>Missing or invalid fields: <?php echo htmlspecialchars(implode(', ', $errors)); ?></p>
    <p><a href="booking.html">Return to booking form</a></p>
    </body></html>
    <?php
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO bookings (name,email,phone,pickup_date,pickup_time,pickup_location,dropoff_location,shipment_type,weight,dimensions,quantity,declared_value,notes) VALUES (:name,:email,:phone,:pickup_date,:pickup_time,:pickup_location,:dropoff_location,:shipment_type,:weight,:dimensions,:quantity,:declared_value,:notes)");

    $stmt->execute([
        ':name'=>$data['name'],
        ':email'=>$data['email'],
        ':phone'=>$data['phone'],
        ':pickup_date'=>$data['pickup_date'],
        ':pickup_time'=> $data['pickup_time'] ?: null,
        ':pickup_location'=>$data['pickup_location'],
        ':dropoff_location'=>$data['dropoff_location'],
        ':shipment_type'=>$data['shipment_type'] ?: null,
        ':weight'=>$data['weight'],
        ':dimensions'=>$data['dimensions'] ?: null,
        ':quantity'=>!empty($data['quantity']) ? (int)$data['quantity'] : null,
        ':declared_value'=>!empty($data['declared_value']) ? $data['declared_value'] : null,
        ':notes'=>$data['notes'] ?: null,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head><body>
    <h1>Server error</h1>
    <p>Could not save booking. Please try again later.</p>
    <p><a href="booking.html">Return to booking form</a></p>
    </body></html>
    <?php
    exit;
}

// Success response
header('Content-Type: text/plain; charset=utf-8');
echo 'Booking saved successfully';
exit;