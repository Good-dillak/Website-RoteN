<?php
// Load ENV (jika pakai VPS, bisa pakai vlucas/phpdotenv)
// Untuk shared hosting, kita pakai array saja

return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'tourism_news',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_USER' => 'noreply@lolehrote.com',
    'MAIL_PASS' => 'apppassword',
    'MIDTRANS_SERVER_KEY' => 'SB-Mid-server-XXXX',
    'MIDTRANS_CLIENT_KEY' => 'SB-Mid-client-XXXX',
];