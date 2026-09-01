<?php
declare(strict_types=1);

// Simulate POST request payload to api/create-order.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';

require_once __DIR__ . '/../includes/integrations.php';

// Prepare test payload
$csrfToken = gawdee_csrf_token();
$payload = [
    'csrf_token' => $csrfToken,
    'checkout_token' => 'test_token_' . time(),
    'coupon_code' => '',
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '9876543210',
        'address1' => '123 Test Street',
        'address2' => 'Apt 4B',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'pincode' => '400001',
        'notes' => 'Deliver in morning'
    ],
    'payment_method' => 'cod',
    'items' => [
        ['id' => 'forest-honey', 'quantity' => 1]
    ]
];

// Capture output of api/create-order.php
ob_start();
$phpInput = json_encode($payload);

// Mock gawdee_request_json to return our payload
$originalJsonFunc = function() use ($payload) { return $payload; };

try {
    $loggedCustomer = gawdee_customer();

    $customer = $payload['customer'];
    $fields = [];
    foreach (['name', 'email', 'phone', 'address1', 'address2', 'city', 'state', 'pincode', 'notes'] as $field) {
        $fields[$field] = trim(mb_substr((string) ($customer[$field] ?? ''), 0, $field === 'notes' ? 500 : 180));
    }
    
    $order = gawdee_create_local_order(
        $fields,
        $payload['items'],
        'cod',
        null,
        $payload['checkout_token'],
        ''
    );

    echo "SUCCESS: Created order #" . $order['order_number'] . " Total: " . $order['total'] . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
