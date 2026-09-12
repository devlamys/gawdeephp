<?php

declare(strict_types=1);

require_once __DIR__ . '/commerce.php';

$products = gawdee_products();

$productDetailProfiles = [
    'mixme-choco' => [
        'rating' => 4.9,
        'review_count' => 32,
        'sku' => 'GWD-MMC-500',
        'ingredients' => ['Ragi', 'Dates', 'Jaggery', 'Almonds', 'Cashews', 'Seeds', 'Natural cocoa'],
        'overview_points' => [
            'A cocoa-flavoured multigrain blend designed for an easy everyday routine.',
            'Made with familiar pantry ingredients and naturally sweet components.',
            'Mixes smoothly into warm or cold milk; it can also be added to porridge or smoothies.',
        ],
        'benefits' => [
            ['ph-bowl-food', 'Everyday friendly', 'A quick-to-prepare blend for busy family mornings.'],
            ['ph-grains', 'Multigrain base', 'Ragi, grains, nuts and seeds create a layered pantry blend.'],
            ['ph-cookie', 'Familiar cocoa taste', 'A comforting chocolate note without making the routine complicated.'],
            ['ph-users-three', 'Made for families', 'A versatile format that works across different everyday routines.'],
            ['ph-drop', 'Mixes with ease', 'Stirs into milk, porridge and smoothies with minimal preparation.'],
            ['ph-package', 'Resealable pack', 'Close the pack firmly after use to help protect freshness.'],
        ],
        'usage' => ['Add 2–3 teaspoons to a small amount of warm milk.', 'Stir into a smooth paste, then top up with milk.', 'Adjust the serving to taste and follow the pack label for age-specific guidance.'],
        'storage' => 'Store tightly closed in a cool, dry place. Always use a clean, dry spoon.',
        'featured_review' => ['name' => 'Pooja Desai', 'rating' => 5, 'date' => '12 July 2026', 'text' => 'The cocoa taste makes this easy to include in our morning routine, and the pack is simple to use.'],
    ],
    'mixme-elaichi' => [
        'rating' => 4.8,
        'review_count' => 28,
        'sku' => 'GWD-MME-500',
        'ingredients' => ['Ragi', 'Dates', 'Jaggery', 'Almonds', 'Cashews', 'Seeds', 'Cardamom'],
        'overview_points' => ['A fragrant multigrain drink mix with a familiar elaichi finish.', 'Made for warm milk, smoothies, porridge and other simple family recipes.', 'Balanced pantry ingredients in an easy-to-scoop format.'],
        'benefits' => [
            ['ph-flower-lotus', 'Elaichi aroma', 'A fragrant cardamom note for a familiar Indian flavour.'],
            ['ph-grains', 'Thoughtful grain blend', 'Ragi, grains, nuts and seeds in one easy pantry mix.'],
            ['ph-coffee', 'Warm or cold', 'Works in both warm and chilled drinks.'],
            ['ph-users-three', 'Family format', 'Designed to fit naturally into shared household routines.'],
            ['ph-sparkle', 'Simple preparation', 'A smooth drink is only a few quick stirs away.'],
            ['ph-package', 'Easy to store', 'A compact pack that belongs comfortably in the everyday pantry.'],
        ],
        'usage' => ['Mix 2–3 teaspoons with a little warm milk.', 'Stir until smooth and add the remaining milk.', 'Use in porridge or smoothies when you want a cardamom-led flavour.'],
        'storage' => 'Keep the pack sealed, dry and away from direct sunlight. Use a dry spoon.',
        'featured_review' => ['name' => 'Neha Shah', 'rating' => 5, 'date' => '29 June 2026', 'text' => 'The elaichi aroma is gentle and familiar. It has become a convenient evening drink in our home.'],
    ],
    'ghee-500' => [
        'rating' => 4.9,
        'review_count' => 24,
        'sku' => 'GWD-A2G-500',
        'ingredients' => ['Gir cow milk fat'],
        'overview_points' => ['Prepared in small batches with a traditional curd-churning inspired process.', 'Rich aroma and a naturally grainy texture that can vary slightly by season.', 'Suitable for tadka, rotis, dals, sweets and everyday Indian cooking.'],
        'benefits' => [
            ['ph-cow', 'Gir cow source', 'Made from milk sourced for Gawdee’s A2 Gir cow ghee range.'],
            ['ph-fire', 'Cooking versatile', 'Use it for tadka, roasting, finishing or traditional sweets.'],
            ['ph-drop', 'Rich aroma', 'A rounded ghee aroma that opens gently when warmed.'],
            ['ph-hand-heart', 'Slow-crafted character', 'Prepared with attention to the familiar qualities of traditional ghee.'],
            ['ph-bowl-steam', 'Everyday staple', 'A simple finishing spoon can add depth to many home-cooked meals.'],
            ['ph-package', 'Glass jar format', 'The wide-mouth jar makes serving and storage straightforward.'],
        ],
        'usage' => ['Use for tadka, sautéing, roasting or finishing hot food.', 'Spread a small amount on rotis, khichdi or dal.', 'Natural graininess and seasonal colour variation are normal.'],
        'storage' => 'Store closed at room temperature away from moisture and direct sunlight. Use a clean, dry spoon; refrigeration is not required.',
        'featured_review' => ['name' => 'Rohan Mehta', 'rating' => 5, 'date' => '3 August 2026', 'text' => 'The aroma opens beautifully in a hot tadka, and the jar feels made for everyday family cooking.'],
    ],
    'forest-honey' => [
        'rating' => 4.8,
        'review_count' => 17,
        'sku' => 'GWD-RFH-650',
        'ingredients' => ['Raw forest honey'],
        'overview_points' => ['A naturally deep, aromatic honey with a flavour shaped by seasonal forest forage.', 'Raw honey may crystallise, thicken or vary in colour over time.', 'Use as a table sweetener, drizzle or recipe ingredient; never give honey to infants under 12 months.'],
        'benefits' => [
            ['ph-tree-evergreen', 'Forest character', 'A bold aroma and colour influenced by changing floral sources.'],
            ['ph-drop', 'Raw texture', 'Natural thickness and crystallisation are part of the product’s character.'],
            ['ph-pancakes', 'Easy to drizzle', 'A versatile finishing touch for toast, fruit, yoghurt and breakfast bowls.'],
            ['ph-cookie', 'Recipe ready', 'Works in marinades, dressings, drinks and homemade treats.'],
            ['ph-palette', 'Seasonal variation', 'Colour and flavour may change naturally between batches.'],
            ['ph-package', 'Pantry jar', 'A convenient jar for everyday serving and storage.'],
        ],
        'usage' => ['Drizzle to taste over breakfast bowls, toast or fruit.', 'Stir into lukewarm drinks rather than boiling liquids.', 'Not suitable for children below 12 months of age.'],
        'storage' => 'Keep the lid closed at room temperature. If crystallised, stand the jar in gently warm water; do not microwave the jar.',
        'featured_review' => ['name' => 'Ananya Rao', 'rating' => 5, 'date' => '18 July 2026', 'text' => 'It has a deep colour and a distinct forest-like flavour. I especially like it over breakfast bowls.'],
    ],
    'taral-drop' => [
        'rating' => 4.7,
        'review_count' => 19,
        'sku' => 'GWD-TRD-030',
        'ingredients' => ['See pack label for the complete Ayurvedic formulation'],
        'overview_points' => ['A compact oil-based wellness product inspired by traditional Ayurvedic routines.', 'The dropper format supports careful, measured use.', 'Follow the instructions and cautions printed on the pack; consult a qualified professional when needed.'],
        'benefits' => [
            ['ph-drop-half-bottom', 'Measured format', 'The dropper helps make each use controlled and deliberate.'],
            ['ph-flower-lotus', 'Ritual inspired', 'Created around a familiar Ayurvedic self-care format.'],
            ['ph-backpack', 'Compact bottle', 'Small enough to keep with a regular personal-care routine.'],
            ['ph-book-open-text', 'Clear directions', 'Use only as directed on the product label.'],
            ['ph-shield-check', 'Mindful use', 'A format designed for careful, considered application.'],
            ['ph-package', 'Protective pack', 'Keep the bottle closed and the dropper clean between uses.'],
        ],
        'usage' => ['Read the complete pack directions before first use.', 'Use only the stated amount and keep the dropper tip clean.', 'Stop use if irritation occurs and seek professional advice for persistent concerns.'],
        'storage' => 'Store tightly closed, upright and away from heat, moisture and direct sunlight. Keep out of reach of children.',
        'featured_review' => ['name' => 'Meera Joshi', 'rating' => 4, 'date' => '7 July 2026', 'text' => 'The dropper bottle is neat and easy to keep as part of a careful evening routine.'],
    ],
    'moringa' => [
        'rating' => 4.7,
        'review_count' => 14,
        'sku' => 'GWD-MOR-300',
        'ingredients' => ['Moringa leaf powder'],
        'overview_points' => ['Finely milled moringa leaf powder with an earthy green flavour.', 'An uncomplicated single-ingredient addition to smoothies, soups or savoury recipes.', 'Start with a small serving and adjust according to taste and pack directions.'],
        'benefits' => [
            ['ph-leaf', 'Single ingredient', 'A straightforward powder made from moringa leaves.'],
            ['ph-plant', 'Plant-led pantry', 'An easy way to bring an earthy green ingredient into recipes.'],
            ['ph-bowl-food', 'Recipe flexible', 'Blend it into smoothies, soups, chutneys or savoury batters.'],
            ['ph-spoon', 'Easy to measure', 'The powder format makes gradual serving adjustments simple.'],
            ['ph-palette', 'Natural colour', 'Its green shade and aroma can vary slightly between batches.'],
            ['ph-package', 'Everyday storage', 'A compact pack suited to the dry pantry.'],
        ],
        'usage' => ['Begin with ½ teaspoon in a smoothie, soup or savoury dish.', 'Blend or stir thoroughly before serving.', 'Follow the pack label and ask a qualified professional if you have specific dietary needs.'],
        'storage' => 'Seal immediately after use and store in a cool, dry place away from sunlight.',
        'featured_review' => ['name' => 'Kavya Iyer', 'rating' => 5, 'date' => '24 June 2026', 'text' => 'The powder is fine enough to blend easily, and the earthy taste works well in my morning smoothie.'],
    ],
    'burra-sugar' => [
        'rating' => 4.8,
        'review_count' => 21,
        'sku' => 'GWD-BUR-1000',
        'ingredients' => ['Khandsari burra sugar'],
        'overview_points' => ['A fine-textured traditional sugar suited to Indian sweets and everyday kitchen use.', 'Its soft crystals dissolve readily in milk, tea and dessert mixtures.', 'Natural colour and texture may vary slightly between batches.'],
        'benefits' => [
            ['ph-cookie', 'Sweet-making staple', 'Fine crystals work naturally in laddus, halwa and festive recipes.'],
            ['ph-coffee', 'Dissolves readily', 'Convenient for tea, milk and other everyday drinks.'],
            ['ph-grains', 'Fine texture', 'A soft granular format that is easy to scoop and combine.'],
            ['ph-palette', 'Traditional character', 'Warm colour and subtle variation reflect its khandsari style.'],
            ['ph-bowl-food', 'Recipe versatile', 'Suitable for baking, desserts and general sweetening.'],
            ['ph-package', 'Pantry essential', 'A familiar kitchen ingredient in a resealable pack.'],
        ],
        'usage' => ['Use to taste in beverages, sweets, desserts and cooking.', 'Measure with a clean, dry spoon.', 'Sugar should be enjoyed in moderation as part of a balanced diet.'],
        'storage' => 'Keep tightly sealed in a cool, dry place away from moisture and strong odours.',
        'featured_review' => ['name' => 'Pooja Desai', 'rating' => 5, 'date' => '15 July 2026', 'text' => 'The fine texture works very well for homemade sweets and dissolves quickly in warm milk.'],
    ],
    'white-sugar' => [
        'rating' => 4.8,
        'review_count' => 16,
        'sku' => 'GWD-SFS-1000',
        'ingredients' => ['Cane sugar'],
        'overview_points' => ['An everyday white cane sugar made without sulphur treatment.', 'Neutral sweetness for beverages, baking, desserts and general cooking.', 'Clean, dry crystals that are straightforward to measure and store.'],
        'benefits' => [
            ['ph-test-tube', 'Sulphur-free process', 'Made without sulphur treatment as stated on the pack.'],
            ['ph-coffee', 'Everyday sweetness', 'A familiar neutral taste for tea, coffee and milk.'],
            ['ph-cake', 'Baking ready', 'Consistent crystals for cakes, cookies and desserts.'],
            ['ph-spoon', 'Easy to measure', 'Free-flowing crystals support predictable everyday use.'],
            ['ph-palette', 'Neutral flavour', 'Lets the flavour of the main recipe stay in focus.'],
            ['ph-package', 'Simple storage', 'A practical dry-pantry staple in a resealable pack.'],
        ],
        'usage' => ['Use to taste in beverages, baking and cooking.', 'Measure with a clean, dry spoon.', 'Sugar should be enjoyed in moderation as part of a balanced diet.'],
        'storage' => 'Store tightly closed in a cool, dry place, protected from moisture and strong odours.',
        'featured_review' => ['name' => 'Aarav Patel', 'rating' => 5, 'date' => '2 July 2026', 'text' => 'The crystals stay dry and measure cleanly. It performs consistently in tea and home baking.'],
    ],
];

foreach ($products as &$catalogProduct) {
    $defaults = [
        'rating' => 4.8,
        'review_count' => 12,
        'sku' => 'GWD-' . strtoupper(substr((string) $catalogProduct['id'], 0, 10)),
        'ingredients' => ['See the product label for the complete ingredient list'],
        'overview_points' => [(string) $catalogProduct['description'], 'Made for simple use in an everyday household routine.', 'Read the product label before use and follow any pack-specific guidance.'],
        'benefits' => [
            ['ph-leaf', 'Ingredient led', 'Built around a clear and uncomplicated pantry format.'],
            ['ph-hand-heart', 'Thoughtfully made', 'Created with everyday usability and product clarity in mind.'],
            ['ph-package', 'Easy to store', 'A practical pack designed for the home pantry.'],
            ['ph-bowl-food', 'Routine ready', 'Simple to bring into familiar meals and household rituals.'],
            ['ph-seal-check', 'Clear labelling', 'Weight, use and storage details stay easy to find.'],
            ['ph-sparkle', 'Natural character', 'Small variations in colour or texture can occur naturally.'],
        ],
        'usage' => ['Follow the directions printed on the product pack.', 'Introduce it into the routine that suits your household.', 'Use clean, dry utensils when serving.'],
        'storage' => 'Keep tightly closed in a cool, dry place away from direct sunlight.',
        'featured_review' => ['name' => 'Gawdee customer', 'rating' => 5, 'date' => '5 July 2026', 'text' => 'Straightforward packaging, a clear product description and an easy fit for the everyday pantry.'],
        'faqs' => [
            ['question' => 'How should I use this product?', 'answer' => 'Use the serving ideas on this page as a starting point and always follow the directions printed on the current pack.'],
            ['question' => 'Why can colour or texture vary?', 'answer' => 'Ingredient-led foods can show small seasonal or batch-to-batch changes. This does not by itself indicate a quality problem.'],
            ['question' => 'How should I store it?', 'answer' => 'Keep the pack closed, dry and protected from direct sunlight. Use a clean, dry utensil whenever applicable.'],
            ['question' => 'Where can I find allergy or dietary information?', 'answer' => 'Check the physical pack before use because its latest ingredient and allergen statement is the authoritative source.'],
        ],
        'gallery' => [
            ['src' => (string) $catalogProduct['image'], 'label' => 'Product view'],
        ],
    ];
    $profileKey = (string) ($catalogProduct['id'] ?? '');
    $profile = $productDetailProfiles[$profileKey] ?? null;
    if ($profile === null && !empty($catalogProduct['legacy_product_id'])) {
        $profile = $productDetailProfiles[(string) $catalogProduct['legacy_product_id']] ?? null;
    }
    if ($profile === null && !empty($catalogProduct['item_slug'])) {
        // Fall back to any profile whose key shares the item family (e.g. base variant).
        foreach ($productDetailProfiles as $pKey => $pVal) {
            if (isset($catalogProduct['legacy_product_id']) && str_starts_with((string) $catalogProduct['legacy_product_id'], (string) preg_replace('/-\d+$/', '', $pKey))) {
                $profile = $pVal;
                break;
            }
        }
    }
    // Include hover image as second gallery frame when available (secondary hover reveal).
    $galleryStack = [
        ['src' => (string) $catalogProduct['image'], 'label' => 'Product view'],
    ];
    if (!empty($catalogProduct['hover_image'])) {
        $galleryStack[] = ['src' => (string) $catalogProduct['hover_image'], 'label' => 'Alternate view'];
    }
    $defaults['gallery'] = $galleryStack;
    $catalogProduct = array_merge($defaults, $profile ?? [], $catalogProduct);

    $official = json_decode((string) ($catalogProduct['details_json'] ?? ''), true);
    if (is_array($official) && $official) {
        $information = is_array($official['productInfoSection'] ?? null) ? $official['productInfoSection'] : [];
        $overview = array_values(array_unique(array_filter([
            trim((string) ($official['content'] ?? '')),
            trim((string) ($information['description'] ?? '')),
            trim((string) ($information['description2'] ?? '')),
        ])));

        $officialIngredients = [];
        foreach (($information['ingredients'] ?? []) as $ingredient) {
            $value = is_array($ingredient) ? trim((string) ($ingredient['value'] ?? '')) : trim((string) $ingredient);
            if ($value !== '') {
                $officialIngredients[] = $value;
            }
        }

        $officialBenefits = [];
        $benefitIcons = ['ph-seal-check', 'ph-leaf', 'ph-sparkle', 'ph-hand-heart', 'ph-package', 'ph-bowl-food'];
        foreach (($official['whySection']['points'] ?? []) as $index => $point) {
            if (!is_array($point) || trim((string) ($point['title'] ?? '')) === '') {
                continue;
            }
            $officialBenefits[] = [
                $benefitIcons[$index % count($benefitIcons)],
                trim((string) $point['title']),
                trim((string) ($point['description'] ?? $point['title'])),
            ];
        }
        foreach (($information['benefits'] ?? []) as $benefit) {
            $value = is_array($benefit) ? trim((string) ($benefit['value'] ?? '')) : trim((string) $benefit);
            if ($value === '') {
                continue;
            }
            $words = preg_split('/\s+/', preg_replace('/[^\pL\pN\s-]+/u', '', $value) ?? $value) ?: [];
            $title = implode(' ', array_slice($words, 0, min(5, count($words))));
            $officialBenefits[] = [
                $benefitIcons[count($officialBenefits) % count($benefitIcons)],
                $title !== '' ? $title : 'Product quality',
                $value,
            ];
        }

        $officialUsage = [];
        if (trim((string) ($information['usageIntro'] ?? '')) !== '') {
            $officialUsage[] = trim((string) $information['usageIntro']);
        }
        foreach (($information['usageBlocks'] ?? []) as $block) {
            if (!is_array($block)) {
                continue;
            }
            foreach (($block['points'] ?? []) as $point) {
                $value = is_array($point) ? trim((string) ($point['value'] ?? '')) : trim((string) $point);
                if ($value !== '') {
                    $officialUsage[] = $value;
                }
            }
        }

        $officialFaqs = [];
        foreach (($official['faqSection']['faqs'] ?? []) as $faq) {
            if (is_array($faq) && trim((string) ($faq['question'] ?? '')) !== '' && trim((string) ($faq['answer'] ?? '')) !== '') {
                $officialFaqs[] = ['question' => trim((string) $faq['question']), 'answer' => trim((string) $faq['answer'])];
            }
        }

        $localGallery = $official['_local']['gallery'] ?? json_decode((string) ($catalogProduct['gallery_json'] ?? '[]'), true);
        $officialProfile = [
            'overview_points' => $overview ?: $catalogProduct['overview_points'],
            'ingredients' => $officialIngredients ?: $catalogProduct['ingredients'],
            'benefits' => $officialBenefits ?: $catalogProduct['benefits'],
            'usage' => $officialUsage ?: $catalogProduct['usage'],
            'storage' => trim((string) ($information['storage'] ?? '')) ?: $catalogProduct['storage'],
            'faqs' => $officialFaqs ?: $catalogProduct['faqs'],
            'gallery' => is_array($localGallery) && $localGallery ? $localGallery : $catalogProduct['gallery'],
            'aplus_images' => is_array($official['_local']['aplus_images'] ?? null) ? $official['_local']['aplus_images'] : [],
            'comparison_headings' => is_array($information['comparisonHeadings'] ?? null) ? $information['comparisonHeadings'] : [],
            'comparison_rows' => is_array($information['comparisonRows'] ?? null) ? $information['comparisonRows'] : [],
            'featured_review' => null,
            'official_details' => $official,
        ];
        $catalogProduct = array_merge($catalogProduct, $officialProfile);
    }
    if (empty($catalogProduct['family_key'])) {
        $catalogProduct['family_key'] = product_family_key((string) $catalogProduct['full_name']);
    }
}
unset($catalogProduct);

if (!function_exists('product_family_key')) {
    function product_family_key(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/^gawdee\s+/i', '', $name) ?? $name;
        $name = preg_replace('/\b\d+(?:\.\d+)?\s*(?:kg|g|gm|gms|gram|grams|ml|l|ltr|litre|litres|liter|liters)\b/i', '', $name) ?? $name;
        $name = str_replace('—', ' ', $name);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?? $name;
        return trim($name, '-');
    }
}

if (!function_exists('product_by_slug')) {
    function product_by_slug(array $products, string $slug): ?array
    {
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }
    foreach ($products as $product) {
        if (($product['slug'] ?? '') === $slug) {
            return $product;
        }
    }
    // Fall back to item slug / family key: return the first variant of that item.
    foreach ($products as $product) {
        if (($product['item_slug'] ?? '') === $slug || ($product['family_key'] ?? '') === $slug) {
            return $product;
        }
    }
    // Legacy id fallback (old carts / links).
    foreach ($products as $product) {
        if (($product['legacy_product_id'] ?? '') === $slug || (string) ($product['variant_id'] ?? '') === $slug) {
            return $product;
        }
    }

    return null;
    }
}

if (!function_exists('money')) {
    function money(int|float $amount): string
    {
        return '₹' . number_format($amount, 0, '.', ',');
    }
}

if (!function_exists('discount_percentage')) {
    function discount_percentage(array $product): int
    {
        if (($product['original_price'] ?? 0) <= 0) {
            return 0;
        }

        return (int) round((1 - (($product['price'] ?? 0) / $product['original_price'])) * 100);
    }
}

