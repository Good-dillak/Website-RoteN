<?php
// Pakai Tripay — langsung masuk DANA Anda
$api_key = 'YOUR-TRIPAY-KEY';
$payload = [
    'method' => 'QRIS',
    'amount' => 10000,
    'customer_name' => $_SESSION['username'],
    'return_url' => BASE_URL . 'payment-success.php'
];

$ch = curl_init('https://tripay.co.id/api/transaction/create');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $api_key]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);

header('Location: ' . $data['data']['checkout_url']);