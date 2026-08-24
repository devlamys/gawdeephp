<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Gawdee — Pure food, thoughtfully made';
$pageDescription = $pageDescription ?? 'Traditional foods and natural wellness essentials, thoughtfully sourced and made for modern families.';
$bodyClass = $bodyClass ?? '';
$headerCustomer = gawdee_customer();
$isReferenceProductPage = str_contains($bodyClass, 'product-page--reference');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#063E2B">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="gawdee-csrf" content="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Lora:ital,wght@0,400..700;1,400..700&family=Manrope:wght@400..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
<a class="skip-link" href="#main-content">Skip to content</a>

<header class="commerce-header" data-header>
    <div class="container commerce-header__main">
        <button class="commerce-icon mobile-menu-toggle mobile-only" type="button" data-menu-toggle aria-label="Open menu" aria-expanded="false">
            <i class="ph ph-list" aria-hidden="true"></i>
        </button>

        <a class="commerce-logo" href="index.php" aria-label="Gawdee home">
            <img src="assets/images/logo.png" alt="Gawdee — The Soul of Wellness">
        </a>

        <div class="commerce-search">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <label class="sr-only" for="site-search">Search products and categories</label>
            <input id="site-search" type="search" placeholder="Search for organic products, categories…" autocomplete="off" data-site-search>
            <button type="button" aria-label="Search"><i class="ph ph-magnifying-glass"></i></button>
        </div>

        <div class="commerce-actions">
            <a class="commerce-action desktop-only" href="<?= $headerCustomer ? 'account.php' : 'login.php' ?>">
                <i class="ph <?= $headerCustomer ? 'ph-user-circle-check' : 'ph-user' ?>" aria-hidden="true"></i><span><?= $headerCustomer ? htmlspecialchars(explode(' ', trim((string) $headerCustomer['name']))[0]) : 'Account' ?></span>
            </a>
            <button class="commerce-action desktop-only" type="button" data-wishlist aria-label="Open wishlist" aria-pressed="false">
                <span class="commerce-action__icon"><i class="ph ph-heart" aria-hidden="true"></i><b>0</b></span><span>Wishlist</span>
            </button>
            <button class="commerce-action" type="button" data-cart-toggle aria-label="Open shopping bag">
                <span class="commerce-action__icon"><i class="ph ph-shopping-cart" aria-hidden="true"></i><b data-cart-count>0</b></span><span class="desktop-only">Cart</span>
            </button>
        </div>
    </div>

    <div class="container commerce-nav-shell">
        <a class="category-menu-button" href="products.php" aria-label="Browse all categories">
            <i class="ph ph-squares-four" aria-hidden="true"></i> All Categories
        </a>
        <nav class="commerce-nav" aria-label="Product categories">
            <a href="products.php?category=ghee" data-nav-filter="ghee">Ghee</a>
            <a href="products.php?category=honey" data-nav-filter="honey">Honey</a>
            <a href="products.php?category=wellness" data-nav-filter="wellness">Drops</a>
            <a href="products.php?category=nutrition" data-nav-filter="nutrition">Mix Me</a>
            <a href="products.php?category=sugar" data-nav-filter="sugar">Sugar</a>
            <a href="index.php#offers">Offers <small>HOT</small></a>
            <a href="blog.php">Blog</a>
        </nav>
    </div>

    <nav class="mobile-nav commerce-mobile-nav" data-mobile-menu aria-label="Mobile navigation">
        <a href="products.php">All products <i class="ph ph-arrow-right"></i></a>
        <a href="index.php#categories">Shop by category <i class="ph ph-arrow-right"></i></a>
        <a href="index.php#offers">Special offers <i class="ph ph-arrow-right"></i></a>
        <a href="blog.php">Wellness stories <i class="ph ph-arrow-right"></i></a>
        <a href="<?= $headerCustomer ? 'account.php' : 'login.php' ?>"><?= $headerCustomer ? 'My account & orders' : 'Sign in / register' ?> <i class="ph ph-arrow-right"></i></a>
    </nav>
</header>

<div class="drawer-backdrop" data-drawer-backdrop></div>
<aside class="cart-drawer" data-cart-drawer aria-labelledby="cart-title" aria-hidden="true">
    <div class="cart-drawer__header">
        <div><span class="eyebrow">Your selection</span><h2 id="cart-title">Shopping bag</h2></div>
        <button class="icon-button" type="button" data-cart-close aria-label="Close shopping bag"><i class="ph ph-x"></i></button>
    </div>
    <div class="cart-items" data-cart-items></div>
    <div class="cart-empty" data-cart-empty>
        <i class="ph ph-shopping-bag-open" aria-hidden="true"></i>
        <h3>Your bag is waiting</h3>
        <p>Add a few natural essentials and they’ll appear here.</p>
        <button class="button button--secondary" type="button" data-cart-close>Continue shopping</button>
    </div>
    <div class="cart-summary" data-cart-summary hidden>
        <div class="cart-summary__line"><span>Subtotal</span><strong data-cart-total>₹0</strong></div>
        <p>Taxes and delivery are calculated at checkout.</p>
        <a class="button button--primary button--full" href="checkout.php">Secure checkout <i class="ph ph-arrow-right"></i></a>
    </div>
</aside>

<main id="main-content">
