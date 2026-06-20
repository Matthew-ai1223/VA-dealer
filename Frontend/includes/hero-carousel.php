<?php
/**
 * Hero carousel slides — fixed premium car photography
 */
function getHeroCarouselSlides(Car $carModel, array $featuredCars): array
{
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

    // Merge featured listing data when available (price + detail link)
    if (!empty($featuredCars)) {
        foreach ($slides as $index => &$slide) {
            if (isset($featuredCars[$index])) {
                $car = $featuredCars[$index];
                $slide['title'] = $car['title'];
                $slide['price'] = $car['price_formatted'];
                $slide['link']  = 'car.php?id=' . (int) $car['id'];
                $slide['year']   = (int) $car['year'];
                $slide['brand']  = $car['brand'];
            }
        }
        unset($slide);
    }

    return $slides;
}
