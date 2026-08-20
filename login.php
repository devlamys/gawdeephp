<?php

declare(strict_types=1);

require __DIR__ . '/includes/platform.php';

$returnTo = gawdee_safe_return_path((string) ($_GET['return'] ?? $_POST['return'] ?? 'account.php'));
if (gawdee_customer()) {
    header('Location: ' . $returnTo);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        gawdee_verify_csrf($_POST['csrf_token'] ?? null);
        $attempts = is_array($_SESSION['customer_login_attempts'] ?? null) ? $_SESSION['customer_login_attempts'] : [];
        $attempts = array_values(array_filter($attempts, static fn(int $timestamp): bool => $timestamp > time() - 900));
        if (count($attempts) >= 6) {
            throw new RuntimeException('Too many sign-in attempts. Please wait 15 minutes and try again.');
        }
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $statement = gawdee_db()->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer'");
        $statement->execute([$email]);
        $customer = $statement->fetch();
        if (!$customer || !password_verify($password, (string) $customer['password_hash'])) {
            $attempts[] = time();
            $_SESSION['customer_login_attempts'] = $attempts;
            throw new RuntimeException('The email or password is incorrect.');
        }
        session_regenerate_id(true);
        $_SESSION['customer_user_id'] = (int) $customer['id'];
        $_SESSION['customer_login_attempts'] = [];
        gawdee_db()->prepare('UPDATE users SET last_login_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int) $customer['id']]);
        header('Location: ' . $returnTo);
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$pageTitle = 'Customer sign in | Gawdee';
$pageDescription = 'Sign in to view your Gawdee orders, delivery updates and DTDC tracking.';
$bodyClass = 'customer-auth-page';
require __DIR__ . '/includes/header.php';
?>
<section class="customer-auth-shell">
    <div class="customer-auth-visual">
        <span class="customer-auth-visual__label"><i class="ph ph-package"></i> One place for every order</span>
        <div class="customer-auth-product-collage" aria-hidden="true">
            <figure class="customer-auth-product-collage__card customer-auth-product-collage__card--ghee"><img src="assets/images/products/ghee-500.webp" alt=""><figcaption>Pure pantry</figcaption></figure>
            <figure class="customer-auth-product-collage__card customer-auth-product-collage__card--mixme"><img src="assets/images/products/mixme-choco.webp" alt=""><figcaption>Family wellness</figcaption></figure>
            <span class="customer-auth-product-collage__rating"><strong>4.9</strong><i>★★★★★</i><small>customer love</small></span>
        </div>
        <div class="customer-auth-visual__copy"><span class="eyebrow eyebrow--light">Gawdee customer account</span><h1>Your goodness,<br><em>always in view.</em></h1><p>See order history, follow fulfilment progress and open DTDC tracking as soon as your shipment is booked.</p></div>
        <div class="customer-auth-steps"><span><i class="ph ph-check"></i> Order confirmed</span><span><i class="ph ph-package"></i> Packed with care</span><span><i class="ph ph-truck"></i> Tracked delivery</span></div>
    </div>
    <div class="customer-auth-panel">
        <div class="customer-auth-card">
            <div class="customer-auth-card__brand"><span><i class="ph ph-leaf"></i></span><div><strong>Welcome to Gawdee</strong><small>Secure customer portal</small></div></div>
            <a class="customer-auth-back" href="index.php"><i class="ph ph-arrow-left"></i> Back to shop</a>
            <span class="eyebrow">Welcome back</span>
            <h2>Sign in to your account</h2>
            <p>Use the email and password you registered with Gawdee.</p>
            <?php if ($error): ?><div class="customer-auth-alert"><i class="ph ph-warning-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post" class="customer-auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                <input type="hidden" name="return" value="<?= htmlspecialchars($returnTo) ?>">
                <label><span>Email address</span><div><i class="ph ph-envelope"></i><input type="email" name="email" autocomplete="email" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>" placeholder="you@example.com"></div></label>
                <label><span>Password</span><div><i class="ph ph-lock-key"></i><input type="password" name="password" autocomplete="current-password" required placeholder="Your password"><button type="button" data-password-toggle aria-label="Show password"><i class="ph ph-eye"></i></button></div></label>
                <button class="button button--primary" type="submit">Sign in securely <i class="ph ph-arrow-right"></i></button>
            </form>
            <p class="customer-auth-switch">New to Gawdee? <a href="register.php?return=<?= rawurlencode($returnTo) ?>">Create your account</a></p>
            <div class="customer-auth-note"><i class="ph ph-shield-check"></i><span><strong>Your account stays private</strong>Order access is checked securely on the server.</span></div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
