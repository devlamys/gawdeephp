<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance | Gawdee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --bg-cream: #f6f5ef;
            --top-bar-bg: #073827;
            --card-bg: #ffffff;
            --text-dark: #073827;
            --text-body: #4b5563;
            --wa-green: #22c55e;
            --wa-green-hover: #16a34a;
            --badge-bg: #fef3c7;
            --badge-text: #92400e;
            --icon-bg: #d1fae5;
            --icon-color: #047857;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-cream);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Top Announcement Bar */
        .top-bar {
            width: 100%;
            background-color: var(--top-bar-bg);
            color: #ffffff;
            text-align: center;
            padding: 14px 20px;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.5;
        }

        .top-bar a {
            color: #ffffff;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        /* Main Container */
        .main-container {
            width: 100%;
            max-width: 480px;
            padding: 36px 20px 48px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Logo */
        .logo-wrapper {
            margin-bottom: 32px;
            text-align: center;
        }

        .logo-wrapper img {
            max-width: 220px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        /* Maintenance Card */
        .card {
            background-color: var(--card-bg);
            width: 100%;
            border-radius: 28px;
            padding: 40px 28px;
            text-align: center;
            box-shadow: 0 12px 36px rgba(7, 56, 39, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Tools Icon Circle */
        .icon-circle {
            width: 56px;
            height: 56px;
            background-color: var(--icon-bg);
            color: var(--icon-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 20px;
        }

        /* Status Pill Badge */
        .time-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: var(--badge-bg);
            color: var(--badge-text);
            font-size: 0.875rem;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 50px;
            margin-bottom: 20px;
        }

        /* Card Title */
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.25;
            margin-bottom: 16px;
        }

        /* Card Description */
        .card-description {
            font-size: 0.975rem;
            color: var(--text-body);
            line-height: 1.6;
            margin-bottom: 28px;
            max-width: 380px;
        }

        /* WhatsApp Button */
        .whatsapp-button {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background-color: var(--wa-green);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 24px;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3);
        }

        .whatsapp-button:hover {
            background-color: var(--wa-green-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }

        .whatsapp-button i {
            font-size: 1.6rem;
        }
    </style>
</head>
<body>

    <!-- Top Announcement Bar -->
    <header class="top-bar">
        This site is under maintenance (coming back in 2-3 hours). Continue order on WhatsApp: <a href="https://wa.me/917055207030?text=Hi%20Gawdee,%20I%20want%20to%20place%20an%20order" target="_blank" rel="noopener">70552 07030</a>
    </header>

    <!-- Main Content Container -->
    <main class="main-container">

        <!-- Gawdee Logo -->
        <div class="logo-wrapper">
            <img src="assets/images/logo.png" alt="Gawdee - The Mother of Organic Nutrition" onerror="this.src='https://mixmepowder.in/gawdee/assets/images/logo.png'">
        </div>

        <!-- Maintenance Card -->
        <article class="card">
            <!-- Icon -->
            <div class="icon-circle">
                <i class="ph ph-wrench"></i>
            </div>

            <!-- Time Badge -->
            <div class="time-badge">
                ⏱️ Coming back in 2-3 hours
            </div>

            <!-- Title -->
            <h1 class="card-title">This site is under maintenance</h1>

            <!-- Description -->
            <p class="card-description">
                Our website is currently undergoing maintenance and will be back in 2-3 hours. You can continue placing your orders directly on WhatsApp.
            </p>

            <!-- WhatsApp Order Button -->
            <a href="https://wa.me/917055207030?text=Hi%20Gawdee,%20I%20want%20to%20place%20an%20order" class="whatsapp-button" target="_blank" rel="noopener">
                <i class="ph ph-whatsapp-logo"></i>
                <span>Order on WhatsApp: 70552 07030</span>
            </a>
        </article>

    </main>

</body>
</html>
