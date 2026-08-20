<?php

declare(strict_types=1);

require_once __DIR__ . '/commerce.php';

function gawdee_http_request(string $method, string $url, array $headers = [], ?array $body = null, int $timeout = 30, ?array $basicAuth = null): array
{
    if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
        throw new RuntimeException('The integration endpoint is not a valid HTTP address.');
    }

    $handle = curl_init($url);
    if ($handle === false) {
        throw new RuntimeException('Unable to initialize the HTTP client.');
    }

    $requestHeaders = array_merge(['Accept: application/json'], $headers);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_HTTPHEADER => $requestHeaders,
    ];
    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
    if ($basicAuth) {
        $options[CURLOPT_USERPWD] = $basicAuth[0] . ':' . $basicAuth[1];
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
    }
    curl_setopt_array($handle, $options);

    $raw = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if ($raw === false) {
        throw new RuntimeException('Integration request failed: ' . $error);
    }

    $decoded = json_decode((string) $raw, true);
    $response = is_array($decoded) ? $decoded : ['raw' => mb_substr((string) $raw, 0, 3000)];
    if ($status < 200 || $status >= 300) {
        $message = $response['error']['description'] ?? $response['error']['message'] ?? $response['message'] ?? 'Remote service returned HTTP ' . $status;
        throw new RuntimeException(is_string($message) ? $message : 'Remote integration request failed.');
    }

    return ['status' => $status, 'data' => $response];
}

function gawdee_razorpay_configured(): bool
{
    return gawdee_setting('razorpay_key_id') !== '' && gawdee_setting('razorpay_key_secret') !== '';
}

function gawdee_razorpay_create_order(int $amountInRupees, string $receipt, array $notes = []): array
{
    if (!gawdee_razorpay_configured()) {
        throw new RuntimeException('Razorpay is not configured yet. Add the Key ID and Key Secret in Admin > Integrations.');
    }

    $response = gawdee_http_request(
        'POST',
        'https://api.razorpay.com/v1/orders',
        ['Content-Type: application/json'],
        [
            'amount' => $amountInRupees * 100,
            'currency' => 'INR',
            'receipt' => $receipt,
            'notes' => $notes,
        ],
        30,
        [gawdee_setting('razorpay_key_id'), gawdee_setting('razorpay_key_secret')]
    );
    $data = $response['data'];
    if (empty($data['id'])) {
        throw new RuntimeException('Razorpay did not return an order ID.');
    }
    gawdee_log_integration('razorpay', 'create_order', 'success', 'Payment order created.', (string) $data['id']);
    return $data;
}

function gawdee_razorpay_verify_payment(string $serverOrderId, string $paymentId, string $signature): bool
{
    $secret = gawdee_setting('razorpay_key_secret');
    if ($secret === '' || $serverOrderId === '' || $paymentId === '' || $signature === '') {
        return false;
    }
    $expected = hash_hmac('sha256', $serverOrderId . '|' . $paymentId, $secret);
    return hash_equals($expected, $signature);
}

function gawdee_razorpay_verify_webhook(string $rawBody, string $signature): bool
{
    $secret = gawdee_setting('razorpay_webhook_secret');
    if ($secret === '' || $signature === '') {
        return false;
    }
    return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
}

function gawdee_ai_configured(?string $provider = null): bool
{
    $provider ??= gawdee_setting('ai_provider', 'groq');
    return $provider === 'openai'
        ? gawdee_setting('openai_api_key') !== ''
        : gawdee_setting('groq_api_key') !== '';
}

function gawdee_ai_generate(string $instructions, string $input, int $maxTokens = 1100, ?string $provider = null): string
{
    $provider ??= gawdee_setting('ai_provider', 'groq');
    if (!in_array($provider, ['groq', 'openai'], true)) {
        throw new RuntimeException('Select Groq or OpenAI as the AI provider.');
    }
    if (!gawdee_ai_configured($provider)) {
        throw new RuntimeException(ucfirst($provider) . ' is not configured. Add its API key in Admin > AI & Blog.');
    }

    if ($provider === 'openai') {
        $response = gawdee_http_request(
            'POST',
            'https://api.openai.com/v1/responses',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . gawdee_setting('openai_api_key'),
            ],
            [
                'model' => gawdee_setting('openai_model', 'gpt-5.6-luna'),
                'instructions' => $instructions,
                'input' => $input,
                'max_output_tokens' => $maxTokens,
                'store' => false,
            ],
            90
        );
        $text = gawdee_openai_output_text($response['data']);
    } else {
        $response = gawdee_http_request(
            'POST',
            'https://api.groq.com/openai/v1/chat/completions',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . gawdee_setting('groq_api_key'),
            ],
            [
                'model' => gawdee_setting('groq_model', 'llama-3.3-70b-versatile'),
                'messages' => [
                    ['role' => 'system', 'content' => $instructions],
                    ['role' => 'user', 'content' => $input],
                ],
                'temperature' => 0.65,
                'max_completion_tokens' => $maxTokens,
            ],
            90
        );
        $text = (string) ($response['data']['choices'][0]['message']['content'] ?? '');
    }

    if (trim($text) === '') {
        throw new RuntimeException('The AI provider returned an empty response.');
    }
    gawdee_log_integration($provider, 'generate_text', 'success', 'Text generation completed.');
    return trim($text);
}

function gawdee_openai_output_text(array $response): string
{
    if (!empty($response['output_text']) && is_string($response['output_text'])) {
        return $response['output_text'];
    }
    $parts = [];
    foreach (($response['output'] ?? []) as $item) {
        foreach (($item['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                $parts[] = (string) $content['text'];
            }
        }
    }
    return implode("\n", $parts);
}

function gawdee_generate_blog(string $topic, string $status = 'draft'): array
{
    $provider = gawdee_setting('ai_provider', 'groq');
    $products = array_map(static fn(array $product): string => $product['full_name'] . ': ' . $product['description'], gawdee_products());
    $instructions = <<<'PROMPT'
You are Gawdee's responsible food and wellness editor. Write accurate, helpful Indian consumer content. Avoid medical claims, disease-treatment language, fabricated research, and guaranteed outcomes. Never claim organic certification unless the supplied product data explicitly says so. Return only valid JSON with keys: title, excerpt, meta_description, content_html. content_html must use only h2, h3, p, ul, ol, li, strong, em, and blockquote tags. The article should be original, readable, practical, and 700-1000 words.
PROMPT;
    $input = "Topic: {$topic}\n\nApproved Gawdee product context:\n" . implode("\n", $products);
    $raw = gawdee_ai_generate($instructions, $input, 2400, $provider);
    $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($raw)) ?? $raw;
    $article = json_decode($raw, true);
    if (!is_array($article) || empty($article['title']) || empty($article['content_html'])) {
        throw new RuntimeException('The AI response was not a valid blog document. Try a more specific topic.');
    }

    $title = trim(mb_substr((string) $article['title'], 0, 180));
    $baseSlug = gawdee_slug($title);
    $slug = $baseSlug;
    $counter = 2;
    $check = gawdee_db()->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = ?');
    do {
        $check->execute([$slug]);
        if ((int) $check->fetchColumn() === 0) {
            break;
        }
        $slug = $baseSlug . '-' . $counter++;
    } while ($counter < 100);

    $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
    $statement = gawdee_db()->prepare(<<<'SQL'
INSERT INTO blog_posts (title, slug, excerpt, content, status, source, ai_provider, meta_description, published_at)
VALUES (?, ?, ?, ?, ?, 'ai', ?, ?, ?)
SQL);
    $statement->execute([
        $title,
        $slug,
        trim(mb_substr((string) ($article['excerpt'] ?? ''), 0, 360)),
        gawdee_sanitize_article_html((string) $article['content_html']),
        $status,
        $provider,
        trim(mb_substr((string) ($article['meta_description'] ?? ''), 0, 180)),
        $publishedAt,
    ]);
    gawdee_set_setting('ai_last_blog_at', date(DATE_ATOM));

    return ['id' => (int) gawdee_db()->lastInsertId(), 'title' => $title, 'slug' => $slug, 'status' => $status];
}

function gawdee_sanitize_article_html(string $html): string
{
    $html = strip_tags($html, '<h2><h3><p><ul><ol><li><strong><em><blockquote>');
    $html = preg_replace('/\s+(?:on\w+|style|class|id)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
    return trim($html);
}

function gawdee_dtdc_configured(): bool
{
    return gawdee_setting('dtdc_enabled', '0') === '1' && gawdee_setting('dtdc_booking_endpoint') !== '' && (
        gawdee_setting('dtdc_api_token') !== '' ||
        (gawdee_setting('dtdc_username') !== '' && gawdee_setting('dtdc_password') !== '')
    );
}

function gawdee_dtdc_create_shipment(array $order, array $items): array
{
    if (!gawdee_dtdc_configured()) {
        throw new RuntimeException('DTDC is not configured. Add your merchant-issued endpoint and credentials in Admin > Integrations.');
    }

    $endpoint = gawdee_setting('dtdc_booking_endpoint');
    $token = gawdee_setting('dtdc_api_token');
    $headerName = trim(gawdee_setting('dtdc_auth_header', 'Authorization')) ?: 'Authorization';
    $prefix = gawdee_setting('dtdc_auth_prefix', 'Bearer');
    $headers = ['Content-Type: application/json'];
    if ($token !== '') {
        $headers[] = $headerName . ': ' . trim($prefix . ' ' . $token);
    }

    $weight = max(0.5, count($items) * 0.5);
    $payload = [
        'customer_code' => gawdee_setting('dtdc_customer_code'),
        'reference_number' => $order['order_number'],
        'service_type' => gawdee_setting('dtdc_service_type', 'EXPRESS'),
        'shipment_type' => $order['payment_method'] === 'cod' ? 'COD' : 'PREPAID',
        'cod_amount' => $order['payment_method'] === 'cod' ? (int) $order['total'] : 0,
        'pickup_pincode' => gawdee_setting('dtdc_pickup_pincode'),
        'consignee' => [
            'name' => $order['customer_name'],
            'phone' => $order['phone'],
            'email' => $order['email'],
            'address1' => $order['address1'],
            'address2' => $order['address2'],
            'city' => $order['city'],
            'state' => $order['state'],
            'pincode' => $order['pincode'],
            'country' => 'IN',
        ],
        'pieces' => array_map(static fn(array $item): array => [
            'sku' => $item['product_id'],
            'description' => $item['product_name'],
            'quantity' => (int) $item['quantity'],
            'declared_value' => (int) $item['unit_price'] * (int) $item['quantity'],
        ], $items),
        'weight_kg' => $weight,
    ];

    $template = trim(gawdee_setting('dtdc_payload_template'));
    if ($template !== '') {
        $payload = gawdee_dtdc_apply_template($template, $order, $items);
    }

    $basicAuth = null;
    if ($token === '' && gawdee_setting('dtdc_username') !== '') {
        $basicAuth = [gawdee_setting('dtdc_username'), gawdee_setting('dtdc_password')];
    }

    try {
        $response = gawdee_http_request('POST', $endpoint, $headers, $payload, 45, $basicAuth);
        $data = $response['data'];
        $reference = (string) ($data['awb_number'] ?? $data['awb'] ?? $data['consignment_number'] ?? $data['reference_number'] ?? $data['data']['awb_number'] ?? '');
        if ($reference === '') {
            throw new RuntimeException('DTDC accepted the request but no AWB/consignment number was found in its response.');
        }
        $trackingUrl = str_replace(['{awb}', '{reference}'], rawurlencode($reference), gawdee_setting('dtdc_tracking_endpoint'));
        gawdee_log_integration('dtdc', 'create_shipment', 'success', 'Shipment created.', $reference);
        return ['reference' => $reference, 'tracking_url' => $trackingUrl, 'response' => $data];
    } catch (Throwable $error) {
        gawdee_log_integration('dtdc', 'create_shipment', 'failed', $error->getMessage(), (string) $order['order_number']);
        throw $error;
    }
}

function gawdee_dtdc_apply_template(string $template, array $order, array $items): array
{
    $replacements = [
        '{{order_number}}' => (string) $order['order_number'],
        '{{customer_code}}' => gawdee_setting('dtdc_customer_code'),
        '{{customer_name}}' => (string) $order['customer_name'],
        '{{email}}' => (string) $order['email'],
        '{{phone}}' => (string) $order['phone'],
        '{{address1}}' => (string) $order['address1'],
        '{{address2}}' => (string) $order['address2'],
        '{{city}}' => (string) $order['city'],
        '{{state}}' => (string) $order['state'],
        '{{pincode}}' => (string) $order['pincode'],
        '{{amount}}' => (string) $order['total'],
        '{{payment_method}}' => (string) $order['payment_method'],
        '{{items_json}}' => json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ];
    $decoded = json_decode(strtr($template, $replacements), true);
    if (!is_array($decoded)) {
        throw new RuntimeException('The DTDC payload template is not valid JSON after placeholder replacement.');
    }
    return $decoded;
}
