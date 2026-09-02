<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/integrations.php';

$admin = gawdee_require_admin();
$expiredPaymentOrders = gawdee_expire_stale_payment_orders();
$allowedViews = ['dashboard', 'products', 'orders', 'banners', 'cms', 'section-items', 'testimonials', 'video-testimonials', 'reviews', 'media', 'blog', 'ai', 'integrations', 'settings'];
$view = in_array($_GET['view'] ?? 'dashboard', $allowedViews, true) ? (string) ($_GET['view'] ?? 'dashboard') : 'dashboard';

function admin_redirect(string $view, string $message, string $type = 'success', array $query = []): never
{
    $_SESSION['admin_flash'] = ['message' => $message, 'type' => $type];
    header('Location: index.php?' . http_build_query(['view' => $view] + $query));
    exit;
}

function admin_upload_media(string $field, string $folder, string $existing = '', array $allowedKinds = ['image']): string
{
    if (empty($_FILES[$field]['tmp_name']) || (int) $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    if ((int) $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        if (in_array((int) $_FILES[$field]['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('The media file exceeds the server upload limit. Use a smaller file or an external video URL.');
        }
        throw new RuntimeException('The media upload did not complete.');
    }
    $maximumBytes = in_array('video', $allowedKinds, true) ? 60 * 1024 * 1024 : 10 * 1024 * 1024;
    if ((int) $_FILES[$field]['size'] > $maximumBytes) {
        throw new RuntimeException(in_array('video', $allowedKinds, true) ? 'Videos must be smaller than 60 MB.' : 'Images must be smaller than 10 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
    $types = [
        'image/jpeg' => ['jpg', 'image'], 'image/png' => ['png', 'image'], 'image/webp' => ['webp', 'image'],
        'video/mp4' => ['mp4', 'video'], 'video/webm' => ['webm', 'video'], 'video/ogg' => ['ogv', 'video'],
    ];
    if (!isset($types[$mime]) || !in_array($types[$mime][1], $allowedKinds, true)) {
        throw new RuntimeException('Upload a supported JPG, PNG, WebP, MP4, WebM or Ogg file.');
    }
    if ($types[$mime][1] === 'image' && @getimagesize($_FILES[$field]['tmp_name']) === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }
    $folder = preg_replace('/[^a-z0-9-]/', '', strtolower($folder)) ?: 'media';
    $directory = GAWDEE_ROOT . '/assets/uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the media directory.');
    }
    $filename = $folder . '-' . bin2hex(random_bytes(9)) . '.' . $types[$mime][0];
    $target = $directory . '/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('Unable to store the uploaded media.');
    }
    return 'assets/uploads/' . $folder . '/' . $filename;
}

function admin_upload_banner(string $field, string $existing = ''): string
{
    return admin_upload_media($field, 'banners', $existing, ['image']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    try {
        gawdee_verify_csrf($_POST['csrf_token'] ?? null);

        if ($action === 'save_product') {
            $id = preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string) ($_POST['id'] ?? '')))) ?: gawdee_slug((string) $_POST['name']);
            $values = [
                'id' => $id,
                'slug' => gawdee_slug((string) $_POST['slug']),
                'name' => trim((string) $_POST['name']),
                'full_name' => trim((string) $_POST['full_name']),
                'category' => trim((string) $_POST['category']),
                'category_key' => gawdee_slug((string) $_POST['category_key']),
                'tag' => trim((string) ($_POST['tag'] ?? '')),
                'price' => max(0, (int) $_POST['price']),
                'original_price' => max(0, (int) $_POST['original_price']),
                'weight' => trim((string) ($_POST['weight'] ?? '')),
                'image' => trim((string) ($_POST['image'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'accent' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($_POST['accent'] ?? '')) ? (string) $_POST['accent'] : '#0a7540',
                'stock' => max(0, (int) ($_POST['stock'] ?? 0)),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ];
            if ($values['name'] === '' || $values['full_name'] === '' || $values['slug'] === '') {
                throw new RuntimeException('Product name, full name and slug are required.');
            }
            $db = gawdee_db();
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $statement = $db->prepare(<<<'SQL'
INSERT INTO products (id, slug, name, full_name, category, category_key, tag, price, original_price, weight, image, description, accent, stock, is_active, updated_at)
VALUES (:id, :slug, :name, :full_name, :category, :category_key, :tag, :price, :original_price, :weight, :image, :description, :accent, :stock, :is_active, NOW())
ON DUPLICATE KEY UPDATE slug=VALUES(slug), name=VALUES(name), full_name=VALUES(full_name), category=VALUES(category), category_key=VALUES(category_key), tag=VALUES(tag), price=VALUES(price), original_price=VALUES(original_price), weight=VALUES(weight), image=VALUES(image), description=VALUES(description), accent=VALUES(accent), stock=VALUES(stock), is_active=VALUES(is_active), updated_at=NOW()
SQL);
            } else {
                $statement = $db->prepare(<<<'SQL'
INSERT INTO products (id, slug, name, full_name, category, category_key, tag, price, original_price, weight, image, description, accent, stock, is_active, updated_at)
VALUES (:id, :slug, :name, :full_name, :category, :category_key, :tag, :price, :original_price, :weight, :image, :description, :accent, :stock, :is_active, CURRENT_TIMESTAMP)
ON CONFLICT(id) DO UPDATE SET slug=excluded.slug, name=excluded.name, full_name=excluded.full_name, category=excluded.category, category_key=excluded.category_key, tag=excluded.tag, price=excluded.price, original_price=excluded.original_price, weight=excluded.weight, image=excluded.image, description=excluded.description, accent=excluded.accent, stock=excluded.stock, is_active=excluded.is_active, updated_at=CURRENT_TIMESTAMP
SQL);
            }
            $statement->execute($values);
            gawdee_db()->prepare("UPDATE products SET stock_status=CASE WHEN stock > 0 THEN 'in_stock' ELSE 'out_of_stock' END WHERE id=?")->execute([$id]);
            admin_redirect('products', 'Product saved successfully.');
        }

        if ($action === 'toggle_product') {
            gawdee_db()->prepare('UPDATE products SET is_active = CASE is_active WHEN 1 THEN 0 ELSE 1 END, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([(string) $_POST['id']]);
            admin_redirect('products', 'Product visibility updated.');
        }

        if ($action === 'save_banner') {
            $id = (int) ($_POST['id'] ?? 0);
            $desktop = admin_upload_banner('desktop_image', trim((string) ($_POST['existing_desktop'] ?? '')));
            $mobile = admin_upload_banner('mobile_image', trim((string) ($_POST['existing_mobile'] ?? '')));
            if ($desktop === '') {
                throw new RuntimeException('A desktop banner image is required.');
            }
            $values = [
                trim((string) $_POST['title']), $desktop, $mobile, trim((string) ($_POST['link_url'] ?? '#shop')),
                trim((string) ($_POST['alt_text'] ?? '')), (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0,
            ];
            if ($id > 0) {
                $statement = gawdee_db()->prepare('UPDATE banners SET title=?, desktop_image=?, mobile_image=?, link_url=?, alt_text=?, sort_order=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
                $statement->execute([...$values, $id]);
            } else {
                $statement = gawdee_db()->prepare('INSERT INTO banners (title, desktop_image, mobile_image, link_url, alt_text, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $statement->execute($values);
            }
            admin_redirect('banners', 'Homepage banner saved.');
        }

        if ($action === 'delete_banner') {
            gawdee_db()->prepare('DELETE FROM banners WHERE id = ?')->execute([(int) $_POST['id']]);
            admin_redirect('banners', 'Banner removed from the CMS.');
        }

        if ($action === 'save_cms') {
            $sections = gawdee_sections();
            $statement = gawdee_db()->prepare('UPDATE cms_sections SET eyebrow=?, title=?, subtitle=?, body=?, image=?, mobile_image=?, video_url=?, button_label=?, button_url=?, sort_order=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE section_key=?');
            foreach ($sections as $key => $section) {
                $image = admin_upload_media('section_image_' . $key, 'sections', trim((string) ($_POST['image'][$key] ?? '')), ['image']);
                $mobileImage = admin_upload_media('section_mobile_image_' . $key, 'sections', trim((string) ($_POST['mobile_image'][$key] ?? '')), ['image']);
                $statement->execute([
                    trim((string) ($_POST['eyebrow'][$key] ?? '')),
                    trim((string) ($_POST['title'][$key] ?? '')),
                    trim((string) ($_POST['subtitle'][$key] ?? '')),
                    trim((string) ($_POST['body'][$key] ?? '')),
                    $image,
                    $mobileImage,
                    trim((string) ($_POST['video_url'][$key] ?? '')),
                    trim((string) ($_POST['button_label'][$key] ?? '')),
                    trim((string) ($_POST['button_url'][$key] ?? '')),
                    (int) ($_POST['sort_order'][$key] ?? $section['sort_order']),
                    isset($_POST['active'][$key]) ? 1 : 0,
                    $key,
                ]);
            }
            admin_redirect('cms', 'Homepage sections updated.');
        }

        if ($action === 'save_typography') {
            $fontChoices = ['system', 'arial', 'dm-sans'];
            $bodyFont = in_array($_POST['site_body_font'] ?? '', $fontChoices, true) ? (string) $_POST['site_body_font'] : 'system';
            $headingFont = in_array($_POST['site_heading_font'] ?? '', $fontChoices, true) ? (string) $_POST['site_heading_font'] : 'system';
            $baseSize = min(20, max(14, (int) ($_POST['site_base_font_size'] ?? 16)));
            gawdee_set_setting('site_body_font', $bodyFont);
            gawdee_set_setting('site_heading_font', $headingFont);
            gawdee_set_setting('site_base_font_size', (string) $baseSize);
            admin_redirect('cms', 'Website typography updated across every page.');
        }

        if ($action === 'save_section_item') {
            $id = (int) ($_POST['id'] ?? 0);
            $allowedSections = ['benefits', 'process', 'assurance', 'why', 'newsletter-perks'];
            $sectionKey = in_array($_POST['section_key'] ?? '', $allowedSections, true) ? (string) $_POST['section_key'] : 'benefits';
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException('A section item title is required.');
            }
            $icon = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['icon'] ?? 'ph-leaf'))) ?: 'ph-leaf';
            if (!str_starts_with($icon, 'ph-')) {
                $icon = 'ph-' . $icon;
            }
            $image = admin_upload_media('image', 'section-items', trim((string) ($_POST['existing_image'] ?? '')), ['image']);
            $values = [$sectionKey, $icon, $title, trim((string) ($_POST['subtitle'] ?? '')), $image, trim((string) ($_POST['link_url'] ?? '')), (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
            if ($id > 0) {
                gawdee_db()->prepare('UPDATE cms_section_items SET section_key=?, icon=?, title=?, subtitle=?, image=?, link_url=?, sort_order=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([...$values, $id]);
            } else {
                gawdee_db()->prepare('INSERT INTO cms_section_items (section_key, icon, title, subtitle, image, link_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute($values);
            }
            admin_redirect('section-items', 'Homepage section item saved.', 'success', ['section' => $sectionKey]);
        }

        if ($action === 'delete_section_item') {
            $sectionKey = trim((string) ($_POST['section_key'] ?? 'benefits'));
            gawdee_db()->prepare('DELETE FROM cms_section_items WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('section-items', 'Homepage section item removed.', 'success', ['section' => $sectionKey]);
        }

        if ($action === 'toggle_section_item') {
            $sectionKey = trim((string) ($_POST['section_key'] ?? 'benefits'));
            gawdee_db()->prepare('UPDATE cms_section_items SET is_active=CASE is_active WHEN 1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('section-items', 'Homepage section item visibility updated.', 'success', ['section' => $sectionKey]);
        }

        if ($action === 'save_testimonial') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $quote = trim((string) ($_POST['quote'] ?? ''));
            if ($name === '' || mb_strlen($quote) < 12) {
                throw new RuntimeException('Customer name and a meaningful testimonial are required.');
            }
            $initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) ($_POST['initials'] ?? '')), 0, 3));
            if ($initials === '') {
                $parts = preg_split('/\s+/', $name) ?: [];
                $initials = strtoupper(implode('', array_map(static fn (string $part): string => substr($part, 0, 1), array_slice($parts, 0, 2))));
            }
            $avatar = admin_upload_media('avatar', 'testimonials', trim((string) ($_POST['existing_avatar'] ?? '')), ['image']);
            $values = [
                $name, $initials, $avatar, trim((string) ($_POST['product_name'] ?? '')),
                trim((string) ($_POST['product_slug'] ?? '')), $quote,
                min(5, max(1, (int) ($_POST['rating'] ?? 5))),
                preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['theme'] ?? 'ghee'))) ?: 'ghee',
                (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0,
            ];
            if ($id > 0) {
                gawdee_db()->prepare('UPDATE testimonials SET name=?, initials=?, avatar=?, product_name=?, product_slug=?, quote=?, rating=?, theme=?, sort_order=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([...$values, $id]);
            } else {
                gawdee_db()->prepare('INSERT INTO testimonials (name, initials, avatar, product_name, product_slug, quote, rating, theme, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($values);
            }
            admin_redirect('testimonials', 'Customer testimonial saved.');
        }

        if ($action === 'delete_testimonial') {
            gawdee_db()->prepare('DELETE FROM testimonials WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('testimonials', 'Testimonial removed.');
        }

        if ($action === 'toggle_testimonial') {
            gawdee_db()->prepare('UPDATE testimonials SET is_active=CASE is_active WHEN 1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('testimonials', 'Testimonial visibility updated.');
        }

        if ($action === 'save_video_testimonial') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $quote = trim((string) ($_POST['quote'] ?? ''));
            $videoType = ($_POST['video_type'] ?? '') === 'external_video' ? 'external_video' : 'upload';
            $videoPath = trim((string) ($_POST['existing_video_path'] ?? ''));
            if ($videoType === 'upload') {
                $videoPath = admin_upload_media('video_file', 'video-testimonials', $videoPath, ['video']);
            }
            $posterPath = admin_upload_media('poster_file', 'video-testimonials', trim((string) ($_POST['existing_poster_path'] ?? '')), ['image']);
            $externalUrl = trim((string) ($_POST['external_url'] ?? ''));
            if ($name === '' || mb_strlen($quote) < 12) {
                throw new RuntimeException('Customer name and a meaningful testimonial are required.');
            }
            if ($videoType === 'upload' && $videoPath === '') {
                throw new RuntimeException('Upload an MP4, WebM or Ogg testimonial video.');
            }
            if ($videoType === 'external_video' && (!filter_var($externalUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $externalUrl))) {
                throw new RuntimeException('Enter a valid YouTube, Vimeo or other HTTPS video URL.');
            }
            $values = [
                $name, trim((string) ($_POST['role_location'] ?? '')), $quote,
                min(5, max(1, (int) ($_POST['rating'] ?? 5))), $videoType,
                $videoType === 'upload' ? $videoPath : '', $posterPath,
                $videoType === 'external_video' ? $externalUrl : '',
                (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0,
            ];
            if ($id > 0) {
                gawdee_db()->prepare('UPDATE video_testimonials SET name=?, role_location=?, quote=?, rating=?, video_type=?, video_path=?, poster_path=?, external_url=?, sort_order=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([...$values, $id]);
            } else {
                gawdee_db()->prepare('INSERT INTO video_testimonials (name, role_location, quote, rating, video_type, video_path, poster_path, external_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($values);
            }
            admin_redirect('video-testimonials', 'Video testimonial saved.');
        }

        if ($action === 'delete_video_testimonial') {
            gawdee_db()->prepare('DELETE FROM video_testimonials WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('video-testimonials', 'Video testimonial removed.');
        }

        if ($action === 'toggle_video_testimonial') {
            gawdee_db()->prepare('UPDATE video_testimonials SET is_active=CASE is_active WHEN 1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('video-testimonials', 'Video testimonial visibility updated.');
        }

        if ($action === 'save_product_review') {
            $id = (int) ($_POST['id'] ?? 0);
            $productId = trim((string) ($_POST['product_id'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $review = trim((string) ($_POST['review'] ?? ''));
            $status = ($_POST['status'] ?? '') === 'pending' ? 'pending' : 'approved';
            if (!gawdee_product_by_id($productId)) {
                throw new RuntimeException('Choose a valid product.');
            }
            if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($review) < 12) {
                throw new RuntimeException('Enter a customer name, valid email and meaningful review.');
            }
            $values = [$productId, min(5, max(1, (int) ($_POST['rating'] ?? 5))), $review, $name, $email, $status];
            if ($id > 0) {
                gawdee_db()->prepare('UPDATE product_reviews SET product_id=?, rating=?, review=?, name=?, email=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([...$values, $id]);
            } else {
                gawdee_db()->prepare('INSERT INTO product_reviews (product_id, rating, review, name, email, status) VALUES (?, ?, ?, ?, ?, ?)')->execute($values);
            }
            admin_redirect('reviews', 'Product review saved.');
        }

        if ($action === 'delete_product_review') {
            gawdee_db()->prepare('DELETE FROM product_reviews WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('reviews', 'Product review removed.');
        }

        if ($action === 'toggle_product_review') {
            gawdee_db()->prepare("UPDATE product_reviews SET status=CASE status WHEN 'approved' THEN 'pending' ELSE 'approved' END, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('reviews', 'Product review status updated.');
        }

        if ($action === 'save_homepage_media') {
            $id = (int) ($_POST['id'] ?? 0);
            $mediaType = in_array($_POST['media_type'] ?? '', ['image', 'video', 'external_video'], true) ? (string) $_POST['media_type'] : 'image';
            $filePath = trim((string) ($_POST['existing_file_path'] ?? ''));
            if ($mediaType !== 'external_video') {
                $filePath = admin_upload_media('media_file', 'homepage', $filePath, [$mediaType]);
            }
            $posterPath = admin_upload_media('poster_file', 'homepage', trim((string) ($_POST['existing_poster_path'] ?? '')), ['image']);
            $externalUrl = trim((string) ($_POST['external_url'] ?? ''));
            if ($filePath === '' && $externalUrl === '') {
                throw new RuntimeException('Upload a media file or enter an external video URL.');
            }
            $values = [
                gawdee_slug((string) ($_POST['section_key'] ?? 'reels')) ?: 'reels', $mediaType,
                trim((string) ($_POST['title'] ?? '')), trim((string) ($_POST['subtitle'] ?? '')),
                $filePath, $posterPath, $externalUrl, trim((string) ($_POST['link_url'] ?? '')),
                trim((string) ($_POST['alt_text'] ?? '')), trim((string) ($_POST['product_slug'] ?? '')),
                (int) ($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0,
            ];
            if ($id > 0) {
                gawdee_db()->prepare('UPDATE homepage_media SET section_key=?, media_type=?, title=?, subtitle=?, file_path=?, poster_path=?, external_url=?, link_url=?, alt_text=?, product_slug=?, sort_order=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([...$values, $id]);
            } else {
                gawdee_db()->prepare('INSERT INTO homepage_media (section_key, media_type, title, subtitle, file_path, poster_path, external_url, link_url, alt_text, product_slug, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($values);
            }
            admin_redirect('media', 'Homepage media card saved.');
        }

        if ($action === 'delete_homepage_media') {
            gawdee_db()->prepare('DELETE FROM homepage_media WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('media', 'Homepage media card removed.');
        }

        if ($action === 'toggle_homepage_media') {
            gawdee_db()->prepare('UPDATE homepage_media SET is_active=CASE is_active WHEN 1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('media', 'Homepage media visibility updated.');
        }

        if ($action === 'save_store') {
            foreach (['store_name', 'store_email', 'store_phone', 'free_shipping_threshold', 'shipping_fee', 'cod_enabled', 'promo_text', 'whatsapp_number'] as $key) {
                gawdee_set_setting($key, trim((string) ($_POST[$key] ?? '')));
            }
            admin_redirect('settings', 'Store settings saved.');
        }

        if ($action === 'save_offer') {
            gawdee_set_setting('offer_code', strtoupper(trim((string) ($_POST['offer_code'] ?? 'FREEDOM10'))));
            gawdee_set_setting('offer_percent', (string) min(100, max(0, (int) ($_POST['offer_percent'] ?? 10))));
            gawdee_set_setting('offer_popup_enabled', isset($_POST['offer_popup_enabled']) ? '1' : '0');
            gawdee_set_setting('offer_popup_delay_ms', (string) min(10000, max(0, (int) ($_POST['offer_popup_delay_ms'] ?? 850))));
            $popupImage = admin_upload_media(
                'offer_popup_image',
                'offers',
                trim((string) ($_POST['existing_offer_popup_image'] ?? 'assets/images/independence-offer-popup-v1.webp')),
                ['image']
            );
            gawdee_set_setting('offer_popup_image', $popupImage);
            admin_redirect('settings', 'Homepage offer settings saved.');
        }

        if ($action === 'save_integrations') {
            foreach (['razorpay_key_id', 'dtdc_booking_endpoint', 'dtdc_tracking_endpoint', 'dtdc_customer_code', 'dtdc_service_type', 'dtdc_pickup_pincode', 'dtdc_auth_header', 'dtdc_auth_prefix', 'dtdc_payload_template'] as $key) {
                gawdee_set_setting($key, trim((string) ($_POST[$key] ?? '')));
            }
            foreach (['razorpay_key_secret', 'razorpay_webhook_secret', 'dtdc_api_token', 'dtdc_username', 'dtdc_password'] as $key) {
                if (trim((string) ($_POST[$key] ?? '')) !== '') {
                    gawdee_set_setting($key, trim((string) $_POST[$key]), true);
                }
            }
            admin_redirect('integrations', 'Integration credentials and endpoints saved.');
        }

        if ($action === 'toggle_dtdc') {
            $enable = gawdee_setting('dtdc_enabled', '0') !== '1';
            gawdee_set_setting('dtdc_enabled', $enable ? '1' : '0');
            admin_redirect('integrations', $enable ? 'DTDC booking tools enabled.' : 'DTDC booking tools disabled. Orders remain in manual fulfilment.');
        }

        if ($action === 'save_ai') {
            $provider = in_array($_POST['ai_provider'] ?? '', ['groq', 'openai'], true) ? (string) $_POST['ai_provider'] : 'groq';
            foreach (['ai_provider' => $provider, 'groq_model' => trim((string) $_POST['groq_model']), 'openai_model' => trim((string) $_POST['openai_model']), 'ai_chat_enabled' => isset($_POST['ai_chat_enabled']) ? '1' : '0', 'ai_auto_blog_enabled' => isset($_POST['ai_auto_blog_enabled']) ? '1' : '0', 'ai_blog_frequency_days' => (string) max(1, (int) $_POST['ai_blog_frequency_days']), 'ai_blog_topics' => trim((string) $_POST['ai_blog_topics'])] as $key => $value) {
                gawdee_set_setting($key, $value);
            }
            foreach (['groq_api_key', 'openai_api_key', 'ai_cron_token'] as $key) {
                if (trim((string) ($_POST[$key] ?? '')) !== '') {
                    gawdee_set_setting($key, trim((string) $_POST[$key]), true);
                }
            }
            admin_redirect('ai', 'AI provider and publishing settings saved.');
        }

        if ($action === 'generate_blog') {
            $topic = trim((string) ($_POST['topic'] ?? ''));
            if (mb_strlen($topic) < 8) {
                throw new RuntimeException('Enter a specific blog topic.');
            }
            $post = gawdee_generate_blog($topic, ($_POST['publish_now'] ?? '') === '1' ? 'published' : 'draft');
            admin_redirect('blog', 'AI article created: ' . $post['title']);
        }

        if ($action === 'save_blog') {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) $_POST['title']);
            $slug = gawdee_slug(trim((string) ($_POST['slug'] ?? $title)));
            $status = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
            $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
            $featuredImage = admin_upload_media('featured_image', 'blogs', trim((string) ($_POST['existing_featured_image'] ?? '')), ['image']);
            $values = [$title, $slug, trim((string) $_POST['excerpt']), gawdee_sanitize_article_html((string) $_POST['content']), $status, trim((string) $_POST['meta_description']), $featuredImage, trim((string) ($_POST['category'] ?? 'Wellness')), trim((string) ($_POST['author'] ?? 'Gawdee editorial')), isset($_POST['is_featured']) ? 1 : 0, $publishedAt];
            if ($id > 0) {
                $statement = gawdee_db()->prepare('UPDATE blog_posts SET title=?, slug=?, excerpt=?, content=?, status=?, meta_description=?, featured_image=?, category=?, author=?, is_featured=?, published_at=CASE WHEN ? IS NULL THEN published_at ELSE COALESCE(published_at, ?) END, updated_at=CURRENT_TIMESTAMP WHERE id=?');
                $statement->execute([...array_slice($values, 0, 10), $publishedAt, $publishedAt, $id]);
            } else {
                $statement = gawdee_db()->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, status, source, meta_description, featured_image, category, author, is_featured, published_at) VALUES (?, ?, ?, ?, ?, 'manual', ?, ?, ?, ?, ?, ?)");
                $statement->execute($values);
            }
            admin_redirect('blog', 'Blog post saved.');
        }

        if ($action === 'toggle_blog') {
            gawdee_db()->prepare("UPDATE blog_posts SET status = CASE status WHEN 'published' THEN 'draft' ELSE 'published' END, published_at = CASE status WHEN 'published' THEN published_at ELSE CURRENT_TIMESTAMP END, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([(int) $_POST['id']]);
            admin_redirect('blog', 'Blog publication status updated.');
        }

        if ($action === 'delete_blog') {
            gawdee_db()->prepare('DELETE FROM blog_posts WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
            admin_redirect('blog', 'Blog post deleted.');
        }

        if ($action === 'update_order') {
            $orderId = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? '');
            gawdee_update_order_status($orderId, $status, trim((string) ($_POST['note'] ?? '')));
            admin_redirect('orders', 'Order status updated.');
        }

        if ($action === 'manual_shipment') {
            gawdee_set_manual_shipment(
                (int) ($_POST['id'] ?? 0),
                trim((string) ($_POST['courier_name'] ?? 'Manual delivery')),
                trim((string) ($_POST['tracking_number'] ?? '')),
                trim((string) ($_POST['tracking_url'] ?? ''))
            );
            admin_redirect('orders', 'Manual shipment marked as dispatched.');
        }

        if ($action === 'mark_cod_paid') {
            $orderId = (int) ($_POST['id'] ?? 0);
            $order = gawdee_order_by_id($orderId);
            if (!$order || $order['payment_method'] !== 'cod') {
                throw new RuntimeException('Only a cash-on-delivery order can be marked collected here.');
            }
            gawdee_db()->prepare("UPDATE orders SET payment_status='paid', paid_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$orderId]);
            gawdee_record_order_event($orderId, (string) $order['status'], 'COD payment collected', 'Cash-on-delivery payment was confirmed by the store team.');
            admin_redirect('orders', 'COD payment marked as collected.');
        }

        if ($action === 'create_shipment') {
            if (!gawdee_dtdc_configured()) {
                throw new RuntimeException('DTDC is offline. Use manual dispatch or enable and configure DTDC in Integrations.');
            }
            $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE id=?');
            $statement->execute([(int) $_POST['id']]);
            $order = $statement->fetch();
            if (!$order) {
                throw new RuntimeException('Order not found.');
            }
            if (!in_array($order['status'], ['processing', 'packed'], true)) {
                throw new RuntimeException('Confirm and pack the order before booking a shipment.');
            }
            if ($order['payment_method'] === 'razorpay' && $order['payment_status'] !== 'paid') {
                throw new RuntimeException('Online payment must be confirmed before booking a shipment.');
            }
            $itemsStatement = gawdee_db()->prepare('SELECT * FROM order_items WHERE order_id=?');
            $itemsStatement->execute([$order['id']]);
            $shipment = gawdee_dtdc_create_shipment($order, $itemsStatement->fetchAll());
            gawdee_db()->prepare("UPDATE orders SET dtdc_reference=?, dtdc_tracking_url=?, courier_name='DTDC', tracking_number=?, tracking_url=?, fulfillment_mode='dtdc', shipment_status='booked', status=CASE status WHEN 'pending' THEN 'processing' ELSE status END, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$shipment['reference'], $shipment['tracking_url'], $shipment['reference'], $shipment['tracking_url'], $order['id']]);
            gawdee_record_order_event((int) $order['id'], 'processing', 'DTDC shipment booked', 'Tracking reference ' . $shipment['reference'] . ' is ready.');
            admin_redirect('orders', 'DTDC shipment created: ' . $shipment['reference']);
        }
    } catch (Throwable $exception) {
        admin_redirect($view, $exception->getMessage(), 'error');
    }
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$viewTitles = ['dashboard' => 'Dashboard', 'products' => 'Products', 'orders' => 'Orders', 'banners' => 'Hero banners', 'cms' => 'Homepage CMS', 'section-items' => 'Section items', 'testimonials' => 'Homepage reviews', 'video-testimonials' => 'Video testimonials', 'reviews' => 'Product reviews', 'media' => 'Homepage media', 'blog' => 'Blog', 'ai' => 'AI studio', 'integrations' => 'Integrations', 'settings' => 'Store settings'];
$navItems = [
    ['dashboard', 'ph-squares-four', 'Dashboard'], ['products', 'ph-package', 'Products'], ['orders', 'ph-receipt', 'Orders'],
    ['banners', 'ph-image', 'Hero banners'], ['cms', 'ph-layout', 'Homepage CMS'], ['section-items', 'ph-list-bullets', 'Section items'], ['testimonials', 'ph-quotes', 'Homepage reviews'],
    ['video-testimonials', 'ph-play-circle', 'Video testimonials'], ['reviews', 'ph-star', 'Product reviews'],
    ['media', 'ph-video-camera', 'Homepage media'], ['blog', 'ph-article', 'Blog'],
    ['ai', 'ph-sparkle', 'AI studio'], ['integrations', 'ph-plugs-connected', 'Integrations'], ['settings', 'ph-sliders-horizontal', 'Settings'],
];

$stats = [
    'orders' => (int) gawdee_db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'revenue' => (int) gawdee_db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn(),
    'today' => (int) gawdee_db()->query("SELECT COUNT(*) FROM orders WHERE date(created_at)=date('now')")->fetchColumn(),
    'attention' => (int) gawdee_db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','on_hold') OR payment_status IN ('initializing','failed')")->fetchColumn(),
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#073c2b">
    <title><?= htmlspecialchars($viewTitles[$view]) ?> · Gawdee Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css"><link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="admin-app" data-admin-app>
    <aside class="admin-sidebar">
        <a class="admin-logo" href="../index.php"><img src="../assets/images/logo.png" alt="Gawdee"></a>
        <nav class="admin-nav" aria-label="Admin navigation">
            <?php foreach ($navItems as [$key, $icon, $label]): ?><a class="<?= $view === $key ? 'is-active' : '' ?>" href="?view=<?= $key ?>"><i class="ph <?= $icon ?>"></i><?= $label ?></a><?php endforeach; ?>
        </nav>
        <div class="admin-sidebar__footer"><strong><?= htmlspecialchars($admin['name']) ?></strong><span><?= htmlspecialchars($admin['email']) ?></span><a href="logout.php"><i class="ph ph-sign-out"></i> Sign out</a></div>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <div style="display:flex;align-items:center;gap:12px"><button class="admin-action-icon mobile-admin-toggle" type="button" data-admin-menu><i class="ph ph-list"></i></button><div><h1><?= htmlspecialchars($viewTitles[$view]) ?></h1><p>Gawdee commerce control centre</p></div></div>
            <div class="admin-topbar__actions"><a href="../index.php" target="_blank" title="View storefront"><i class="ph ph-arrow-square-out"></i></a><a class="admin-button admin-button--primary" href="?view=orders"><i class="ph ph-receipt"></i> View orders</a></div>
        </header>
        <div class="admin-content">
            <?php if ($flash): ?><div class="admin-alert admin-flash <?= $flash['type'] === 'error' ? 'admin-alert--error' : 'admin-alert--success' ?>"><i class="ph <?= $flash['type'] === 'error' ? 'ph-warning-circle' : 'ph-check-circle' ?>"></i><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

            <?php if ($view === 'dashboard'): ?>
                <div class="admin-grid admin-grid--stats">
                    <article class="stat-card"><i class="ph ph-receipt"></i><span>Total orders</span><strong><?= $stats['orders'] ?></strong></article>
                    <article class="stat-card"><i class="ph ph-currency-inr"></i><span>Paid revenue</span><strong>₹<?= number_format($stats['revenue']) ?></strong></article>
                    <article class="stat-card"><i class="ph ph-calendar-check"></i><span>Orders today</span><strong><?= $stats['today'] ?></strong></article>
                    <article class="stat-card"><i class="ph ph-warning-circle"></i><span>Needs attention</span><strong><?= $stats['attention'] ?></strong></article>
                </div>
                <div class="admin-grid" style="grid-template-columns:minmax(0,1.4fr) minmax(280px,.6fr)">
                    <section class="admin-card"><div class="admin-card__header"><div><h2>Recent orders</h2><p>Latest checkout activity</p></div><a class="admin-button admin-button--ghost" href="?view=orders">All orders</a></div>
                        <?php $recentOrders = gawdee_db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 7')->fetchAll(); if ($recentOrders): ?>
                        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead><tbody><?php foreach ($recentOrders as $order): ?><tr><td><strong><?= htmlspecialchars($order['order_number']) ?></strong><br><small><?= htmlspecialchars($order['created_at']) ?></small></td><td><?= htmlspecialchars($order['customer_name']) ?></td><td>₹<?= number_format((int) $order['total']) ?></td><td><span class="status-pill status-pill--<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
                        <?php else: ?><div class="empty-state"><i class="ph ph-basket"></i><h3>No orders yet</h3><p>Completed checkouts will appear here.</p></div><?php endif; ?>
                    </section>
                    <section class="admin-card"><div class="admin-card__header"><div><h2>Launch checklist</h2><p>Integration readiness</p></div></div><div class="admin-card__body" style="display:grid;gap:12px">
                        <?php foreach ([['Razorpay', gawdee_razorpay_configured()], ['DTDC', gawdee_dtdc_configured()], ['AI provider', gawdee_ai_configured()], ['Hero banners', count(gawdee_banners()) > 0]] as [$label, $ready]): ?><div style="display:flex;align-items:center;justify-content:space-between;font-size:.72rem"><span><?= $label ?></span><span class="status-pill <?= $ready ? '' : 'status-pill--pending' ?>"><i class="ph <?= $ready ? 'ph-check' : 'ph-clock' ?>"></i><?= $ready ? 'Ready' : 'Setup needed' ?></span></div><?php endforeach; ?>
                        <a class="admin-button admin-button--secondary" href="?view=integrations">Configure integrations</a>
                    </div></section>
                </div>

            <?php elseif ($view === 'products'): ?>
                <?php $allProducts = gawdee_products(true); $editId = (string) ($_GET['edit'] ?? ''); $editProduct = null; foreach ($allProducts as $candidate) { if ($candidate['id'] === $editId) $editProduct = $candidate; } ?>
                <div class="admin-section-title"><div><h2>Product catalogue</h2><p>Prices, stock, descriptions and storefront visibility</p></div><a class="admin-button admin-button--primary" href="?view=products&edit=new"><i class="ph ph-plus"></i> New product</a></div>
                <?php if ($editId): $p = $editProduct ?? ['id'=>'','slug'=>'','name'=>'','full_name'=>'','category'=>'','category_key'=>'','tag'=>'','price'=>0,'original_price'=>0,'weight'=>'','image'=>'','description'=>'','accent'=>'#0a7540','stock'=>100,'is_active'=>1]; ?>
                    <section class="admin-card" style="margin-bottom:20px"><div class="admin-card__header"><div><h2><?= $editProduct ? 'Edit product' : 'Create product' ?></h2><p>Storefront data is updated immediately</p></div><a href="?view=products" class="admin-action-icon"><i class="ph ph-x"></i></a></div><div class="admin-card__body"><form method="post" class="admin-form form-grid form-grid--3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="save_product"><label><span>Product ID</span><input name="id" value="<?= htmlspecialchars($p['id']) ?>" <?= $editProduct ? 'readonly' : '' ?> placeholder="mixme-choco"></label><label><span>Slug</span><input name="slug" required value="<?= htmlspecialchars($p['slug']) ?>"></label><label><span>Short name</span><input name="name" required value="<?= htmlspecialchars($p['name']) ?>"></label><label class="form-span-2"><span>Full name</span><input name="full_name" required value="<?= htmlspecialchars($p['full_name']) ?>"></label><label><span>Tag</span><input name="tag" value="<?= htmlspecialchars($p['tag']) ?>"></label><label><span>Category</span><input name="category" value="<?= htmlspecialchars($p['category']) ?>"></label><label><span>Category key</span><input name="category_key" value="<?= htmlspecialchars($p['category_key']) ?>"></label><label><span>Weight</span><input name="weight" value="<?= htmlspecialchars($p['weight']) ?>"></label><label><span>Price ₹</span><input type="number" min="0" name="price" value="<?= (int) $p['price'] ?>"></label><label><span>MRP ₹</span><input type="number" min="0" name="original_price" value="<?= (int) $p['original_price'] ?>"></label><label><span>Stock</span><input type="number" min="0" name="stock" value="<?= (int) $p['stock'] ?>"></label><label class="form-span-2"><span>Image path</span><input name="image" value="<?= htmlspecialchars($p['image']) ?>" placeholder="assets/images/products/product.webp"></label><label><span>Accent colour</span><input type="color" name="accent" value="<?= htmlspecialchars($p['accent']) ?>"></label><label class="form-span-3"><span>Description</span><textarea name="description"><?= htmlspecialchars($p['description']) ?></textarea></label><label class="form-switch form-span-2"><input type="checkbox" name="is_active" <?= (int) $p['is_active'] ? 'checked' : '' ?>><span>Visible on storefront</span></label><div style="display:flex;justify-content:flex-end;align-items:end"><button class="admin-button admin-button--primary" type="submit">Save product <i class="ph ph-check"></i></button></div></form></div></section>
                <?php endif; ?>
                <section class="admin-card"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Visibility</th><th>Actions</th></tr></thead><tbody><?php foreach ($allProducts as $product): ?><tr><td><div class="admin-table__product"><img src="../<?= htmlspecialchars($product['image']) ?>" alt=""><div><strong><?= htmlspecialchars($product['name']) ?></strong><span><?= htmlspecialchars($product['weight']) ?></span></div></div></td><td><?= htmlspecialchars($product['category']) ?></td><td>₹<?= number_format((int) $product['price']) ?></td><td><?= (int) $product['stock'] ?></td><td><span class="status-pill <?= $product['is_active'] ? '' : 'status-pill--draft' ?>"><?= $product['is_active'] ? 'Active' : 'Hidden' ?></span></td><td><div class="admin-actions"><a class="admin-action-icon" href="?view=products&edit=<?= rawurlencode($product['id']) ?>"><i class="ph ph-pencil-simple"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="toggle_product"><input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>"><button class="admin-action-icon" type="submit"><i class="ph <?= $product['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i></button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>

            <?php elseif ($view === 'banners'): ?>
                <?php $allBanners = gawdee_banners(true); $bannerEditId = (int) ($_GET['edit'] ?? 0); $editBanner = null; foreach ($allBanners as $candidate) { if ((int) $candidate['id'] === $bannerEditId) $editBanner = $candidate; } ?>
                <div class="admin-section-title"><div><h2>Hero banner manager</h2><p>Upload desktop and mobile slides, change links and drag order with sort numbers</p></div><a class="admin-button admin-button--primary" href="?view=banners&edit=-1"><i class="ph ph-plus"></i> Add banner</a></div>
                <?php if (isset($_GET['edit'])): $b = $editBanner ?? ['id'=>0,'title'=>'','desktop_image'=>'','mobile_image'=>'','link_url'=>'#shop','alt_text'=>'','sort_order'=>count($allBanners)*10+10,'is_active'=>1]; ?><section class="admin-card" style="margin-bottom:20px"><div class="admin-card__header"><div><h2><?= $editBanner ? 'Edit banner' : 'New banner' ?></h2><p>Recommended desktop ratio 16:6 and mobile ratio 4:5</p></div><a href="?view=banners" class="admin-action-icon"><i class="ph ph-x"></i></a></div><div class="admin-card__body"><form method="post" enctype="multipart/form-data" class="admin-form form-grid"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="save_banner"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="existing_desktop" value="<?= htmlspecialchars($b['desktop_image']) ?>"><input type="hidden" name="existing_mobile" value="<?= htmlspecialchars($b['mobile_image']) ?>"><label><span>Banner title</span><input name="title" required value="<?= htmlspecialchars($b['title']) ?>"></label><label><span>Destination link</span><input name="link_url" value="<?= htmlspecialchars($b['link_url']) ?>"></label><label><span>Desktop image</span><input type="file" name="desktop_image" accept="image/jpeg,image/png,image/webp"><small class="help-text"><?= htmlspecialchars($b['desktop_image']) ?></small></label><label><span>Mobile image</span><input type="file" name="mobile_image" accept="image/jpeg,image/png,image/webp"><small class="help-text"><?= htmlspecialchars($b['mobile_image']) ?></small></label><label class="form-span-2"><span>Accessible alt text</span><input name="alt_text" value="<?= htmlspecialchars($b['alt_text']) ?>"></label><label><span>Sort order</span><input type="number" name="sort_order" value="<?= (int) $b['sort_order'] ?>"></label><label class="form-switch"><input type="checkbox" name="is_active" <?= (int) $b['is_active'] ? 'checked' : '' ?>><span>Active slide</span></label><div class="form-span-2" style="display:flex;justify-content:flex-end"><button class="admin-button admin-button--primary">Save banner</button></div></form></div></section><?php endif; ?>
                <div class="banner-grid"><?php foreach ($allBanners as $banner): ?><article class="banner-card"><img src="../<?= htmlspecialchars($banner['desktop_image']) ?>" alt=""><div class="banner-card__body"><h3><?= htmlspecialchars($banner['title']) ?></h3><p>Order <?= (int) $banner['sort_order'] ?> · <?= $banner['is_active'] ? 'Active' : 'Hidden' ?></p><div class="admin-actions"><a class="admin-button admin-button--secondary" href="?view=banners&edit=<?= (int) $banner['id'] ?>"><i class="ph ph-pencil-simple"></i> Edit</a><form method="post" onsubmit="return confirm('Remove this banner from the CMS?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="delete_banner"><input type="hidden" name="id" value="<?= (int) $banner['id'] ?>"><button class="admin-button admin-button--danger"><i class="ph ph-trash"></i></button></form></div></div></article><?php endforeach; ?></div>

            <?php elseif ($view === 'cms'): ?>
                <?php require __DIR__ . '/partials/cms.php'; ?>

            <?php elseif ($view === 'section-items'): ?>
                <?php require __DIR__ . '/partials/section-items.php'; ?>

            <?php elseif ($view === 'testimonials'): ?>
                <?php require __DIR__ . '/partials/testimonials.php'; ?>

            <?php elseif ($view === 'video-testimonials'): ?>
                <?php require __DIR__ . '/partials/video-testimonials.php'; ?>

            <?php elseif ($view === 'reviews'): ?>
                <?php require __DIR__ . '/partials/reviews.php'; ?>

            <?php elseif ($view === 'media'): ?>
                <?php require __DIR__ . '/partials/media.php'; ?>

            <?php elseif ($view === 'orders'): ?>
                <?php require __DIR__ . '/partials/orders.php'; ?>
                <?php if (false): ?>
                <?php $orders = gawdee_db()->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll(); $detailOrder=null; $detailItems=[]; if((int)($_GET['order']??0)>0){$detailStatement=gawdee_db()->prepare('SELECT * FROM orders WHERE id=?');$detailStatement->execute([(int)$_GET['order']]);$detailOrder=$detailStatement->fetch()?:null;if($detailOrder){$detailItemsStatement=gawdee_db()->prepare('SELECT * FROM order_items WHERE order_id=?');$detailItemsStatement->execute([$detailOrder['id']]);$detailItems=$detailItemsStatement->fetchAll();}} ?>
                <div class="admin-section-title"><div><h2>Orders & fulfilment</h2><p>Payment, packing and DTDC shipment workflow</p></div></div>
                <?php if($detailOrder): ?><section class="admin-card" style="margin-bottom:20px"><div class="admin-card__header"><div><h2><?=htmlspecialchars($detailOrder['order_number'])?></h2><p><?=htmlspecialchars($detailOrder['customer_name'].' · '.$detailOrder['email'].' · '.$detailOrder['phone'])?></p></div><a class="admin-action-icon" href="?view=orders"><i class="ph ph-x"></i></a></div><div class="admin-card__body"><div class="form-grid"><div><p class="help-text">DELIVERY ADDRESS</p><strong style="font-size:.75rem"><?=htmlspecialchars($detailOrder['address1'].($detailOrder['address2']?', '.$detailOrder['address2']:'').', '.$detailOrder['city'].', '.$detailOrder['state'].' '.$detailOrder['pincode'])?></strong></div><div><p class="help-text">PAYMENT & TOTAL</p><strong style="font-size:.75rem"><?=htmlspecialchars(strtoupper($detailOrder['payment_method']).' · '.$detailOrder['payment_status'])?> · ₹<?=number_format((int)$detailOrder['total'])?></strong></div></div><div class="admin-table-wrap" style="margin-top:18px"><table class="admin-table"><thead><tr><th>Item</th><th>Quantity</th><th>Unit price</th><th>Total</th></tr></thead><tbody><?php foreach($detailItems as $item):?><tr><td><div class="admin-table__product"><img src="../<?=htmlspecialchars($item['image'])?>" alt=""><strong><?=htmlspecialchars($item['product_name'])?></strong></div></td><td><?=(int)$item['quantity']?></td><td>₹<?=number_format((int)$item['unit_price'])?></td><td>₹<?=number_format((int)$item['unit_price']*(int)$item['quantity'])?></td></tr><?php endforeach;?></tbody></table></div></div></section><?php endif; ?>
                <section class="admin-card"><?php if ($orders): ?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Payment</th><th>Total</th><th>Shipment</th><th>Workflow</th></tr></thead><tbody><?php foreach ($orders as $order): ?><tr><td><strong><?= htmlspecialchars($order['order_number']) ?></strong><br><small><?= htmlspecialchars($order['created_at']) ?></small></td><td><?= htmlspecialchars($order['customer_name']) ?><br><small><?= htmlspecialchars($order['phone']) ?></small></td><td><span class="status-pill status-pill--<?= htmlspecialchars($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_method'] . ' · ' . $order['payment_status']) ?></span></td><td>₹<?= number_format((int) $order['total']) ?></td><td><?php if ($order['dtdc_reference']): ?><strong><?= htmlspecialchars($order['dtdc_reference']) ?></strong><br><?php if ($order['dtdc_tracking_url']): ?><a href="<?= htmlspecialchars($order['dtdc_tracking_url']) ?>" target="_blank">Track <i class="ph ph-arrow-up-right"></i></a><?php endif; ?><?php else: ?><span class="status-pill status-pill--draft"><?= htmlspecialchars($order['shipment_status']) ?></span><?php endif; ?></td><td><div class="admin-actions"><a class="admin-action-icon" href="?view=orders&order=<?=(int)$order['id']?>" title="Order details"><i class="ph ph-eye"></i></a><form method="post" style="display:flex;gap:6px"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="update_order"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><select name="status" style="padding:7px;border:1px solid #dfe7e1;border-radius:9px"><?php foreach (['pending','processing','packed','shipped','delivered','cancelled','refunded'] as $status): ?><option <?= $order['status']===$status?'selected':'' ?>><?= $status ?></option><?php endforeach; ?></select><button class="admin-action-icon"><i class="ph ph-check"></i></button></form><?php if (!$order['dtdc_reference']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="create_shipment"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><button class="admin-button admin-button--secondary"><i class="ph ph-truck"></i> Book DTDC</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><i class="ph ph-receipt"></i><h3>No orders yet</h3><p>New Razorpay and cash-on-delivery orders will appear here.</p></div><?php endif; ?></section>

                <?php endif; ?>
            <?php elseif ($view === 'blog'): ?>
                <?php require __DIR__ . '/partials/blog.php'; ?>
                <?php if (false): ?>
                <?php $posts = gawdee_db()->query('SELECT * FROM blog_posts ORDER BY id DESC')->fetchAll(); $postEditId = (int) ($_GET['edit'] ?? 0); $editPost = null; foreach ($posts as $candidate) if ((int)$candidate['id']===$postEditId) $editPost=$candidate; ?>
                <div class="admin-section-title"><div><h2>Stories & publishing</h2><p>Write manually or let the selected AI provider create a responsible first draft</p></div><div class="admin-actions"><a class="admin-button admin-button--secondary" href="?view=ai"><i class="ph ph-sparkle"></i> Generate with AI</a><a class="admin-button admin-button--primary" href="?view=blog&edit=-1"><i class="ph ph-plus"></i> New post</a></div></div>
                <?php if (isset($_GET['edit'])): $bp=$editPost??['id'=>0,'title'=>'','slug'=>'','excerpt'=>'','content'=>'','status'=>'draft','meta_description'=>'']; ?><section class="admin-card" style="margin-bottom:20px"><div class="admin-card__header"><div><h2><?= $editPost?'Edit article':'New article' ?></h2><p>Safe HTML formatting is preserved</p></div><a href="?view=blog" class="admin-action-icon"><i class="ph ph-x"></i></a></div><div class="admin-card__body"><form method="post" class="admin-form form-grid"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>"><input type="hidden" name="action" value="save_blog"><input type="hidden" name="id" value="<?= (int)$bp['id'] ?>"><label><span>Title</span><input name="title" required value="<?= htmlspecialchars($bp['title']) ?>"></label><label><span>Slug</span><input name="slug" value="<?= htmlspecialchars($bp['slug']) ?>"></label><label class="form-span-2"><span>Excerpt</span><textarea name="excerpt" style="min-height:75px"><?= htmlspecialchars($bp['excerpt']) ?></textarea></label><label class="form-span-2"><span>Article HTML</span><textarea name="content" style="min-height:310px" required><?= htmlspecialchars($bp['content']) ?></textarea></label><label><span>Meta description</span><textarea name="meta_description" style="min-height:80px"><?= htmlspecialchars($bp['meta_description']) ?></textarea></label><label><span>Status</span><select name="status"><option value="draft" <?= $bp['status']==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= $bp['status']==='published'?'selected':'' ?>>Published</option></select></label><div class="form-span-2" style="display:flex;justify-content:flex-end"><button class="admin-button admin-button--primary">Save article</button></div></form></div></section><?php endif; ?>
                <section class="admin-card"><?php if($posts):?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Article</th><th>Source</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody><?php foreach($posts as $post):?><tr><td><strong><?=htmlspecialchars($post['title'])?></strong><br><small>/blog-post.php?slug=<?=htmlspecialchars($post['slug'])?></small></td><td><?=htmlspecialchars($post['source'].($post['ai_provider']?' · '.$post['ai_provider']:''))?></td><td><span class="status-pill status-pill--<?=htmlspecialchars($post['status'])?>"><?=htmlspecialchars($post['status'])?></span></td><td><?=htmlspecialchars($post['created_at'])?></td><td><div class="admin-actions"><a class="admin-action-icon" href="?view=blog&edit=<?=(int)$post['id']?>"><i class="ph ph-pencil-simple"></i></a><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(gawdee_csrf_token())?>"><input type="hidden" name="action" value="toggle_blog"><input type="hidden" name="id" value="<?=(int)$post['id']?>"><button class="admin-action-icon"><i class="ph <?=$post['status']==='published'?'ph-eye-slash':'ph-paper-plane-tilt'?>"></i></button></form></div></td></tr><?php endforeach;?></tbody></table></div><?php else:?><div class="empty-state"><i class="ph ph-article"></i><h3>No stories yet</h3><p>Create a manual post or generate a draft in AI Studio.</p></div><?php endif;?></section>

                <?php endif; ?>
            <?php elseif ($view === 'ai'): ?>
                <div class="admin-section-title"><div><h2>AI studio</h2><p>Choose Groq or OpenAI for homepage chat and scheduled blog generation</p></div></div><div class="integration-grid"><section class="integration-card"><div class="integration-card__title"><i class="ph ph-sparkle"></i><div><h3>Provider & API keys</h3><p>Secrets are encrypted at rest and never rendered back</p></div></div><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(gawdee_csrf_token())?>"><input type="hidden" name="action" value="save_ai"><label><span>Active AI mode</span><select name="ai_provider"><option value="groq" <?=gawdee_setting('ai_provider')==='groq'?'selected':''?>>Groq</option><option value="openai" <?=gawdee_setting('ai_provider')==='openai'?'selected':''?>>OpenAI / ChatGPT models</option></select></label><label><span>Groq API key</span><input type="password" name="groq_api_key" autocomplete="new-password" placeholder="Leave blank to keep stored key"><div class="secret-state"><i class="ph ph-lock"></i> <?=gawdee_setting('groq_api_key')!==''?'Key configured':'Not configured'?></div></label><label><span>Groq model</span><input name="groq_model" value="<?=htmlspecialchars(gawdee_setting('groq_model'))?>"></label><label><span>OpenAI API key</span><input type="password" name="openai_api_key" autocomplete="new-password" placeholder="Leave blank to keep stored key"><div class="secret-state"><i class="ph ph-lock"></i> <?=gawdee_setting('openai_api_key')!==''?'Key configured':'Not configured'?></div></label><label><span>OpenAI model</span><input name="openai_model" value="<?=htmlspecialchars(gawdee_setting('openai_model'))?>"></label><label class="form-switch"><input type="checkbox" name="ai_chat_enabled" <?=gawdee_setting('ai_chat_enabled')==='1'?'checked':''?>><span>Enable homepage AI assistant</span></label><label class="form-switch"><input type="checkbox" name="ai_auto_blog_enabled" <?=gawdee_setting('ai_auto_blog_enabled')==='1'?'checked':''?>><span>Enable scheduled auto-blog</span></label><label><span>Frequency in days</span><input type="number" min="1" name="ai_blog_frequency_days" value="<?=htmlspecialchars(gawdee_setting('ai_blog_frequency_days','7'))?>"></label><label><span>Approved topic pool</span><textarea name="ai_blog_topics"><?=htmlspecialchars(gawdee_setting('ai_blog_topics'))?></textarea></label><label><span>Scheduler token</span><input type="password" name="ai_cron_token" autocomplete="new-password" placeholder="Set a long random token or leave unchanged"><div class="secret-state"><i class="ph ph-lock"></i> <?=gawdee_setting('ai_cron_token')!==''?'Token configured':'Set a token before scheduling'?></div></label><button class="admin-button admin-button--primary">Save AI settings</button></form></section><section class="integration-card"><div class="integration-card__title"><i class="ph ph-magic-wand"></i><div><h3>Create an article</h3><p>Generate a safe editorial draft or publish immediately</p></div></div><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(gawdee_csrf_token())?>"><input type="hidden" name="action" value="generate_blog"><label><span>Specific topic</span><textarea name="topic" required placeholder="Example: A practical guide to choosing traditional ghee for everyday Indian cooking"></textarea></label><label><span>Publishing mode</span><select name="publish_now"><option value="0">Create as draft for review</option><option value="1">Generate and publish</option></select></label><button class="admin-button admin-button--primary"><i class="ph ph-sparkle"></i> Generate article</button></form><div style="margin-top:24px"><p class="help-text">Call this from cron or Windows Task Scheduler using the same secret token you entered:</p><div class="code-box"><?=htmlspecialchars(gawdee_base_url().'/cron/auto-blog.php?token=YOUR_CRON_TOKEN')?></div></div></section></div>

            <?php elseif ($view === 'integrations'): ?>
                <section class="integration-mode-card <?= gawdee_dtdc_configured() ? 'is-online' : 'is-offline' ?>"><div><span class="integration-mode-card__icon"><i class="ph ph-truck"></i></span><div><strong>DTDC <?= gawdee_dtdc_configured() ? 'online' : 'offline — manual fulfilment active' ?></strong><p>Orders are always saved in Admin. Turning DTDC off only disables courier booking; it never hides or rejects an order.</p></div></div><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(gawdee_csrf_token())?>"><input type="hidden" name="action" value="toggle_dtdc"><button class="admin-button <?= gawdee_setting('dtdc_enabled','0')==='1' ? 'admin-button--danger' : 'admin-button--primary' ?>"><?= gawdee_setting('dtdc_enabled','0')==='1' ? 'Turn DTDC off' : 'Enable DTDC tools' ?></button></form></section>
                <div class="admin-section-title"><div><h2>Commerce integrations</h2><p>Production credentials stay on the server and are encrypted</p></div></div><form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(gawdee_csrf_token())?>"><input type="hidden" name="action" value="save_integrations"><div class="integration-grid"><section class="integration-card"><div class="integration-card__title"><i class="ph ph-credit-card"></i><div><h3>Razorpay</h3><p>Orders API, Checkout signature verification and webhooks</p></div></div><div class="admin-form"><label><span>Key ID</span><input name="razorpay_key_id" value="<?=htmlspecialchars(gawdee_setting('razorpay_key_id'))?>"></label><label><span>Key Secret</span><input type="password" name="razorpay_key_secret" placeholder="Leave blank to keep stored secret"><div class="secret-state"><i class="ph ph-lock"></i> <?=gawdee_setting('razorpay_key_secret')!==''?'Secret configured':'Not configured'?></div></label><label><span>Webhook Secret</span><input type="password" name="razorpay_webhook_secret" placeholder="Leave blank to keep stored secret"><div class="secret-state"><i class="ph ph-lock"></i> <?=gawdee_setting('razorpay_webhook_secret')!==''?'Secret configured':'Not configured'?></div></label><div><span class="help-text">Webhook URL</span><div class="code-box"><?=htmlspecialchars(gawdee_base_url().'/api/razorpay-webhook.php')?></div></div></div></section><section class="integration-card"><div class="integration-card__title"><i class="ph ph-truck"></i><div><h3>DTDC merchant adapter</h3><p>Uses the endpoint and request schema issued with your DTDC account</p></div></div><div class="admin-form form-grid"><label class="form-span-2"><span>Booking endpoint</span><input type="url" name="dtdc_booking_endpoint" value="<?=htmlspecialchars(gawdee_setting('dtdc_booking_endpoint'))?>" placeholder="https://merchant-issued-endpoint"></label><label class="form-span-2"><span>Tracking URL template</span><input name="dtdc_tracking_endpoint" value="<?=htmlspecialchars(gawdee_setting('dtdc_tracking_endpoint'))?>" placeholder="https://tracking.example/{awb}"></label><label><span>Customer code</span><input name="dtdc_customer_code" value="<?=htmlspecialchars(gawdee_setting('dtdc_customer_code'))?>"></label><label><span>Pickup pincode</span><input name="dtdc_pickup_pincode" value="<?=htmlspecialchars(gawdee_setting('dtdc_pickup_pincode'))?>"></label><label><span>Service type</span><input name="dtdc_service_type" value="<?=htmlspecialchars(gawdee_setting('dtdc_service_type'))?>"></label><label><span>API token</span><input type="password" name="dtdc_api_token" placeholder="Keep stored token"><div class="secret-state"><?=gawdee_setting('dtdc_api_token')!==''?'Token configured':'Not configured'?></div></label><label><span>Auth header</span><input name="dtdc_auth_header" value="<?=htmlspecialchars(gawdee_setting('dtdc_auth_header','Authorization'))?>"></label><label><span>Auth prefix</span><input name="dtdc_auth_prefix" value="<?=htmlspecialchars(gawdee_setting('dtdc_auth_prefix','Bearer'))?>"></label><label><span>Basic auth username</span><input type="password" name="dtdc_username" placeholder="Optional / keep stored"></label><label><span>Basic auth password</span><input type="password" name="dtdc_password" placeholder="Optional / keep stored"></label><label class="form-span-2"><span>Optional JSON payload template</span><textarea name="dtdc_payload_template" placeholder='{"reference":"{{order_number}}","pincode":"{{pincode}}"}'><?=htmlspecialchars(gawdee_setting('dtdc_payload_template'))?></textarea><p class="help-text">Placeholders: {{order_number}}, {{customer_name}}, {{phone}}, {{email}}, {{address1}}, {{address2}}, {{city}}, {{state}}, {{pincode}}, {{amount}}, {{payment_method}}, {{items_json}}.</p></label></div></section></div><div style="display:flex;justify-content:flex-end"><button class="admin-button admin-button--primary">Save integrations <i class="ph ph-check"></i></button></div></form>

            <?php elseif ($view === 'settings'): ?>
                <div class="admin-section-title"><div><h2>Store settings</h2><p>Contact, delivery, announcement and checkout preferences</p></div></div><section class="admin-card"><div class="admin-card__body"><form method="post" class="admin-form form-grid"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(gawdee_csrf_token())?>"><input type="hidden" name="action" value="save_store"><label><span>Store name</span><input name="store_name" value="<?=htmlspecialchars(gawdee_setting('store_name'))?>"></label><label><span>Store email</span><input type="email" name="store_email" value="<?=htmlspecialchars(gawdee_setting('store_email'))?>"></label><label><span>Store phone</span><input name="store_phone" value="<?=htmlspecialchars(gawdee_setting('store_phone'))?>"></label><label><span>WhatsApp number</span><input name="whatsapp_number" value="<?=htmlspecialchars(gawdee_setting('whatsapp_number','917055207030'))?>"></label><label><span>Free shipping threshold ₹</span><input type="number" min="0" name="free_shipping_threshold" value="<?=htmlspecialchars(gawdee_setting('free_shipping_threshold'))?>"></label><label><span>Shipping fee ₹</span><input type="number" min="0" name="shipping_fee" value="<?=htmlspecialchars(gawdee_setting('shipping_fee'))?>"></label><label class="form-span-2"><span>Announcement strip</span><input name="promo_text" value="<?=htmlspecialchars(gawdee_setting('promo_text','Independence Day Specials — Flat 10% OFF on all orders | Use Code: FREEDOM10'))?>"></label><label class="form-switch"><input type="checkbox" name="cod_enabled" value="1" <?=gawdee_setting('cod_enabled')==='1'?'checked':''?>><span>Enable cash on delivery</span></label><div style="display:flex;justify-content:flex-end;align-items:end"><button class="admin-button admin-button--primary">Save store settings</button></div></form></div></section>
                <section class="admin-card" style="margin-top:18px"><div class="admin-card__header"><div><h2>Homepage offer popup</h2><p>Control the Independence Day artwork shown once per visitor session</p></div></div><div class="admin-card__body"><form method="post" enctype="multipart/form-data" class="admin-form form-grid"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars(gawdee_csrf_token())?>"><input type="hidden" name="action" value="save_offer"><input type="hidden" name="existing_offer_popup_image" value="<?=htmlspecialchars(gawdee_setting('offer_popup_image','assets/images/independence-offer-popup-v1.webp'))?>"><label><span>Offer code</span><input name="offer_code" value="<?=htmlspecialchars(gawdee_setting('offer_code','FREEDOM10'))?>"></label><label><span>Discount percent</span><input type="number" min="0" max="100" name="offer_percent" value="<?=htmlspecialchars(gawdee_setting('offer_percent','10'))?>"></label><label><span>Popup image</span><input type="file" name="offer_popup_image" accept="image/jpeg,image/png,image/webp"><small class="help-text">JPG, PNG or WebP up to 10 MB. A vertical 4:5 image works best.</small></label><label><span>Open delay (milliseconds)</span><input type="number" min="0" max="10000" step="50" name="offer_popup_delay_ms" value="<?=htmlspecialchars(gawdee_setting('offer_popup_delay_ms','850'))?>"></label><label class="form-switch"><input type="checkbox" name="offer_popup_enabled" value="1" <?=gawdee_setting('offer_popup_enabled','1')==='1'?'checked':''?>><span>Show offer popup on homepage</span></label><div style="display:flex;justify-content:flex-end;align-items:end"><button class="admin-button admin-button--primary">Save popup settings</button></div><?php $offerPopupPreview=gawdee_setting('offer_popup_image','assets/images/independence-offer-popup-v1.webp'); if($offerPopupPreview!==''):?><div class="form-span-2" style="padding:14px;border:1px solid #e1e8e3;border-radius:18px;background:#f8fbf9"><span class="help-text">CURRENT POPUP ARTWORK</span><img src="../<?=htmlspecialchars($offerPopupPreview)?>" alt="Current offer popup" style="display:block;width:min(100%,230px);margin-top:10px;border-radius:16px;box-shadow:0 14px 30px rgba(9,54,33,.14)"></div><?php endif;?></form></div></section>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>document.querySelector('[data-admin-menu]')?.addEventListener('click',()=>document.querySelector('[data-admin-app]')?.classList.toggle('is-menu-open'));</script>
</body>
</html>
