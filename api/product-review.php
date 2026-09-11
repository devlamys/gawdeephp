<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/platform.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gawdee_json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $payload = gawdee_request_json();
    gawdee_verify_csrf($payload['csrf_token'] ?? null);

    $productId = trim((string) ($payload['product_id'] ?? ''));
    $name = trim((string) ($payload['name'] ?? ''));
    $email = strtolower(trim((string) ($payload['email'] ?? '')));
    $review = trim((string) ($payload['review'] ?? ''));
    $rating = (int) ($payload['rating'] ?? 0);

    $product = gawdee_product_by_id($productId);
    if (!$product) {
        throw new RuntimeException('This product is no longer available.');
    }
    // Store under the legacy products.id when available so FK + grouped
    // review history keep working; new variants fall back to their variant id
    // (a lightweight products mirror row is ensured for FK compliance).
    $storeId = $productId;
    if (!empty($product['legacy_product_id'])) {
        $storeId = (string) $product['legacy_product_id'];
    } elseif (!empty($product['variant_id']) && function_exists('gawdee_sync_variant_mirror')) {
        gawdee_sync_variant_mirror((int) $product['variant_id']);
        $storeId = (string) $product['variant_id'];
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        throw new RuntimeException('Enter your name.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid email address.');
    }
    if ($rating < 1 || $rating > 5) {
        throw new RuntimeException('Choose a rating from 1 to 5 stars.');
    }
    if (mb_strlen($review) < 15 || mb_strlen($review) > 1200) {
        throw new RuntimeException('Write a review between 15 and 1,200 characters.');
    }

    $statement = gawdee_db()->prepare('INSERT INTO product_reviews (product_id, rating, review, name, email) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$storeId, $rating, $review, $name, $email]);

    gawdee_json_response([
        'ok' => true,
        'message' => 'Thank you — your review is now published.',
        'review' => [
            'name' => $name,
            'rating' => $rating,
            'review' => $review,
            'date' => date('j M Y'),
        ],
    ]);
} catch (Throwable $exception) {
    gawdee_json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}
