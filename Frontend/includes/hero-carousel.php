<?php
/**
 * Hero carousel slides — pulled from live car inventory
 */
function getHeroCarouselSlides(Car $carModel, array $featuredCars): array
{
    $slides = [];

    // --- 1. Try to build slides from actual car inventory ---
    try {
        $db = Database::getConnection();

        // Prefer featured cars first, then fall back to any available car with images
        // Use RAND() to keep the carousel fresh on each page load
        $stmt = $db->query("
            SELECT id, title, brand, year, price, images
            FROM cars
            WHERE status = 'available'
              AND JSON_LENGTH(images) > 0
            ORDER BY featured DESC, RAND()
            LIMIT 3
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $images = json_decode($row['images'], true) ?: [];
            if (empty($images)) continue;

            $slides[] = [
                'id'    => (int) $row['id'],
                'image' => getImageUrl($images[0]),
                'title' => $row['title'],
                'price' => formatPrice((float) $row['price']),
                'link'  => 'car.php?id=' . (int) $row['id'],
                'year'  => (int) $row['year'],
                'brand' => $row['brand'],
            ];
        }
    } catch (Throwable $e) {
        // Fall through to static defaults
    }

    // --- 2. Static fallback if no cars with images exist yet ---
    if (empty($slides)) {
        $base = url('Frontend/assets/images/hero');
        $slides = [
            [
                'image' => $base . '/images%20(1).jpg',
                'title' => 'Lexus RX 350',
                'price' => '',
                'link'  => 'listings.php',
                'year'  => 2019,
                'brand' => 'Lexus',
            ],
            [
                'image' => $base . '/car-2.jpg',
                'title' => 'BMW M5',
                'price' => '',
                'link'  => 'listings.php',
                'year'  => 2022,
                'brand' => 'BMW',
            ],
            [
                'image' => $base . '/images%20(2).jpg',
                'title' => 'Mercedes-Benz G-Class',
                'price' => '',
                'link'  => 'listings.php',
                'year'  => 2021,
                'brand' => 'Mercedes-Benz',
            ],
        ];
    }

    return $slides;
}
