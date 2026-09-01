<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Gawdee — Pure food, Thoughtfully made';
$pageDescription = $pageDescription ?? 'Traditional foods and natural wellness essentials, Thoughtfully sourced and made for modern families.';
$pageKeywords = $pageKeywords ?? 'Gawdee, organic food, A2 Gir cow ghee, raw wild forest honey, MixMe nutrition, wellness drops, natural food India, pure ghee, traditional foods';
$pageRobots = $pageRobots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$bodyClass = $bodyClass ?? '';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$canonicalUrl = $canonicalUrl ?? ($scheme . '://' . $host . strtok($requestUri, '?'));
$siteUrl = $scheme . '://' . $host;

$ogType = $ogType ?? 'website';
$ogImage = $ogImage ?? ($siteUrl . '/assets/images/logo.png');
$twitterCard = $twitterCard ?? 'summary_large_image';

$headerCustomer = gawdee_customer();
$isReferenceProductPage = str_contains($bodyClass, 'product-page--reference');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#063E2B">

    <!-- SEO Primary Meta Tags -->
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
    <meta name="author" content="Gawdee">
    <meta name="application-name" content="Gawdee">
    <meta name="robots" content="<?= htmlspecialchars($pageRobots) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?= htmlspecialchars($siteUrl) ?>/sitemap.xml">
    <link rel="alternate" type="text/plain" href="<?= htmlspecialchars($siteUrl) ?>/llm.text"
        title="Gawdee LLM Context">

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:site_name" content="Gawdee">
    <meta property="og:type" content="<?= htmlspecialchars($ogType) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="<?= htmlspecialchars($twitterCard) ?>">
    <meta name="twitter:site" content="@gawdee">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

    <meta name="gawdee-csrf" content="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <meta name="google-site-verification" content="sMQUAczenGsQG2ZP_s3YY5s-stxH5gxvJK6MYAEMUxY" />

    <!-- Structured Data (Schema.org JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Gawdee",
        "url": <?= json_encode($siteUrl, JSON_UNESCAPED_SLASHES) ?>,
        "logo": <?= json_encode($siteUrl . '/assets/images/logo.png', JSON_UNESCAPED_SLASHES) ?>,
        "description": <?= json_encode($pageDescription) ?>
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Gawdee",
        "url": <?= json_encode($siteUrl, JSON_UNESCAPED_SLASHES) ?>,
        "potentialAction": {
            "@type": "SearchAction",
            "target": <?= json_encode($siteUrl . '/products?search={search_term_string}', JSON_UNESCAPED_SLASHES) ?>,
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <?php if (isset($jsonLdExtra) && is_array($jsonLdExtra)): ?>
        <script type="application/ld+json">
                            <?= json_encode($jsonLdExtra, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?>
                            </script>
    <?php endif; ?>
    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Lora:ital,wght@0,400..700;1,400..700&family=Manrope:wght@400..800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>document.documentElement.classList.add('js');</script>
</head>

<body class="<?= htmlspecialchars($bodyClass) ?>">
    <a class="skip-link" href="#main-content">Skip to content</a>

    <header class="commerce-header" data-header>
        <!-- Top Announcement Promo Bar -->
        <div class="header-announcement" data-header-announcement>
            <div class="container header-announcement__content">
                <div class="announcement-text">
                    <i class="ph-fill ph-sparkle"></i>
                    <span><strong>100% Certified Organic &amp; Pure</strong> · Free Shipping over
                        <?= money((int) gawdee_setting('free_shipping_threshold', '999')) ?></span>
                </div>
                <?php if (gawdee_setting('offer_popup_enabled', '1') === '1'): ?>
                    <div class="announcement-cta">
                        <a href="index#offers" class="announcement-badge">
                            <i class="ph ph-ticket"></i> Code:
                            <strong><?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?></strong>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Header Bar -->
        <div class="container commerce-header__main">
            <button class="commerce-icon mobile-menu-toggle mobile-only" type="button" data-menu-toggle
                aria-label="Open menu" aria-expanded="false">
                <i class="ph ph-list" aria-hidden="true"></i>
            </button>

            <a class="commerce-logo" href="index" aria-label="Gawdee home">
                <img src="assets/images/logo.png" alt="Gawdee — The Soul of Wellness" class="header-logo-img">
            </a>

            <nav class="desktop-main-nav desktop-only" aria-label="Primary navigation">
                <a href="products" class="nav-link">All Products</a>
                <a href="products?category=ghee" class="nav-link" data-nav-filter="ghee">Ghee</a>
                <a href="products?category=honey" class="nav-link" data-nav-filter="honey">Honey</a>
                <a href="products?category=nutrition" class="nav-link" data-nav-filter="nutrition">Mix Me</a>
                <a href="products?category=sugar" class="nav-link" data-nav-filter="sugar">Sugar</a>
                <a href="products?category=wellness" class="nav-link" data-nav-filter="wellness">Drops</a>
                <a href="reels" class="nav-link">Reels <span class="nav-badge-hot">NEW</span></a>
                <?php if (gawdee_setting('offer_popup_enabled', '1') === '1'): ?>
                    <a href="index#offers" class="nav-link">Offers</a>
                <?php endif; ?>
                <a href="blog" class="nav-link">Blog</a>
            </nav>

            <div class="commerce-actions">
                <form class="header-search-form desktop-only" action="products" method="get" role="search">
                    <i class="ph ph-magnifying-glass search-icon" aria-hidden="true"></i>
                    <input type="search" name="search" placeholder="Search pure essentials..."
                        aria-label="Search products" autocomplete="off">
                </form>

                <a class="commerce-action desktop-only" href="<?= $headerCustomer ? 'account' : 'login' ?>"
                    aria-label="Account">
                    <i class="ph <?= $headerCustomer ? 'ph-user-circle-check' : 'ph-user' ?>" aria-hidden="true"></i>
                    <span><?= $headerCustomer ? htmlspecialchars(explode(' ', trim((string) $headerCustomer['name']))[0]) : 'Account' ?></span>
                </a>

                <button class="commerce-action" type="button" data-cart-toggle aria-label="Open shopping bag">
                    <span class="commerce-action__icon">
                        <i class="ph ph-shopping-cart" aria-hidden="true"></i>
                        <b data-cart-count>0</b>
                    </span>
                    <span class="desktop-only">Cart</span>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <nav class="mobile-nav commerce-mobile-nav" data-mobile-menu aria-label="Mobile navigation">
            <form class="mobile-search-form" action="products" method="get">
                <input type="search" name="search" placeholder="Search Gawdee products..." aria-label="Search products">
                <button type="submit"><i class="ph ph-magnifying-glass"></i></button>
            </form>
            <a href="products">All products <i class="ph ph-arrow-right"></i></a>
            <a href="products?category=ghee">A2 Gir Cow Ghee <i class="ph ph-arrow-right"></i></a>
            <a href="products?category=honey">Raw Forest Honey <i class="ph ph-arrow-right"></i></a>
            <a href="products?category=nutrition">Mix Me Nutrition <i class="ph ph-arrow-right"></i></a>
            <a href="products?category=sugar">Natural Sugar <i class="ph ph-arrow-right"></i></a>
            <a href="reels">Watch Reels &amp; Videos <i class="ph ph-arrow-right"></i></a>
            <a href="index#offers">Special offers <i class="ph ph-arrow-right"></i></a>
            <a href="blog">Wellness stories <i class="ph ph-arrow-right"></i></a>
            <a href="<?= $headerCustomer ? 'account' : 'login' ?>"><?= $headerCustomer ? 'My account & orders' : 'Sign in / register' ?>
                <i class="ph ph-arrow-right"></i></a>
        </nav>
    </header>

    <div class="drawer-backdrop" data-drawer-backdrop></div>
    <aside class="cart-drawer" data-cart-drawer aria-labelledby="cart-title" aria-hidden="true">
        <div class="cart-drawer__header">
            <div><span class="eyebrow">Your selection</span>
                <h2 id="cart-title">Shopping bag</h2>
            </div>
            <button class="icon-button" type="button" data-cart-close aria-label="Close shopping bag"><i
                    class="ph ph-x"></i></button>
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
            <a class="button button--primary button--full" href="checkout.php">Secure checkout <i
                    class="ph ph-arrow-right"></i></a>
        </div>
    </aside>

    <main id="main-content">