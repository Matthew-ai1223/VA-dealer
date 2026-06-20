<?php
require_once __DIR__ . '/../Backend/lib/helpers.php';
require_once __DIR__ . '/../Backend/models/Car.php';

$config = appConfig();
$carModel = new Car();
$featuredCars = $carModel->getAll(['featured' => 1], false);
$brands = $carModel->getBrands();
require_once __DIR__ . '/includes/hero-carousel.php';
$heroSlides = getHeroCarouselSlides($carModel, $featuredCars);
$pageTitle = $config['site_name'] . ' - ' . $config['site_tagline'];
$pageDescription = 'Browse premium pre-owned cars at ' . $config['site_name'] . '. Quality vehicles with transparent pricing. Contact us on WhatsApp today.';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero__inner">
        <div class="hero__content animate-fade-up">
            <span class="hero__badge">Trusted Dealership</span>
            <h1>Find Your Perfect <span class="text-accent">Drive</span></h1>
            <p><?= sanitize($config['site_tagline']) ?>. Browse our curated selection of quality pre-owned vehicles.</p>
            <div class="hero__actions">
                <a href="listings.php" class="btn btn--primary btn--lg">Browse All Cars</a>
                <a href="#featured" class="btn btn--outline btn--lg">Featured Listings</a>
            </div>
        </div>
        <div class="hero__visual animate-fade-up delay-1">
            <div class="hero-carousel" id="hero-carousel" aria-label="Featured vehicles carousel">
                <div class="hero-carousel__track">
                    <?php foreach ($heroSlides as $index => $slide): ?>
                    <div class="hero-carousel__slide<?= $index === 0 ? ' is-active' : '' ?>" data-index="<?= $index ?>">
                        <img
                            src="<?= sanitize($slide['image']) ?>"
                            alt="<?= sanitize($slide['title']) ?>"
                            loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                            class="hero-carousel__img"
                        >
                        <div class="hero-carousel__overlay"></div>
                        <div class="hero-carousel__caption">
                            <?php if (!empty($slide['year'])): ?>
                                <span class="hero-carousel__tag"><?= (int) $slide['year'] ?> · <?= sanitize($slide['brand'] ?? '') ?></span>
                            <?php endif; ?>
                            <h3><?= sanitize($slide['title']) ?></h3>
                            <?php if (!empty($slide['price'])): ?>
                                <p class="hero-carousel__price"><?= sanitize($slide['price']) ?></p>
                            <?php endif; ?>
                            <a href="<?= sanitize($slide['link']) ?>" class="hero-carousel__link">View Details &rarr;</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($heroSlides) > 1): ?>
                <button type="button" class="hero-carousel__btn hero-carousel__btn--prev" aria-label="Previous slide">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button type="button" class="hero-carousel__btn hero-carousel__btn--next" aria-label="Next slide">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </button>
                <div class="hero-carousel__dots" role="tablist">
                    <?php foreach ($heroSlides as $index => $slide): ?>
                    <button
                        type="button"
                        class="hero-carousel__dot<?= $index === 0 ? ' is-active' : '' ?>"
                        aria-label="Go to slide <?= $index + 1 ?>"
                        data-index="<?= $index ?>"
                        role="tab"
                    ></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="search-bar reveal-on-scroll">
    <div class="container">
        <div class="filter-wrap filter-wrap--home">
            <div class="filter-mobile-bar">
                <button
                    type="button"
                    class="filter-mobile-toggle"
                    id="home-filter-toggle"
                    aria-controls="home-search-panel"
                    aria-expanded="false"
                >
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 6h16M7 12h10M10 18h4"/>
                    </svg>
                    <span>Quick Search</span>
                </button>
            </div>

            <form action="listings.php" method="GET" class="search-form search-form--home" id="home-search-panel">
                <p class="search-form__heading search-form__heading--desktop">Quick Search</p>
                <div class="search-form__grid">
                    <div class="search-form__field search-form__field--wide">
                        <label for="home-search" class="search-form__label">Search</label>
                        <input type="search" id="home-search" name="search" placeholder="Search by name, brand, model..." aria-label="Search cars" autocomplete="off">
                    </div>
                    <div class="search-form__field">
                        <label for="home-brand" class="search-form__label">Brand</label>
                        <select name="brand" id="home-brand" aria-label="Brand">
                            <option value="">All Brands</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= sanitize($brand) ?>"><?= sanitize($brand) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-form__field search-form__field--half">
                        <label for="home-min-price" class="search-form__label">Min Price</label>
                        <input type="number" id="home-min-price" name="min_price" placeholder="₦ Min" aria-label="Minimum price" inputmode="numeric">
                    </div>
                    <div class="search-form__field search-form__field--half">
                        <label for="home-max-price" class="search-form__label">Max Price</label>
                        <input type="number" id="home-max-price" name="max_price" placeholder="₦ Max" aria-label="Maximum price" inputmode="numeric">
                    </div>
                    <div class="search-form__field search-form__field--action">
                        <span class="search-form__label search-form__label--hidden" aria-hidden="true">Search</span>
                        <button type="submit" class="btn btn--primary btn--block-mobile">
                            <svg class="search-form__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
                            Search Cars
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<section id="featured" class="section reveal-on-scroll">
    <div class="container">
        <div class="section__header">
            <h2>Featured Cars</h2>
            <a href="listings.php" class="link-arrow">View all listings &rarr;</a>
        </div>

        <?php if (empty($featuredCars)): ?>
            <div class="empty-state">
                <p>No featured cars at the moment. Check back soon!</p>
                <a href="listings.php" class="btn btn--primary">Browse All Cars</a>
            </div>
        <?php else: ?>
            <div class="car-grid">
                <?php foreach ($featuredCars as $car): ?>
                    <?php include __DIR__ . '/includes/car-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--alt reveal-on-scroll">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card animate-fade-up">
                <div class="feature-card__icon">✓</div>
                <h3>Quality Inspected</h3>
                <p>Every vehicle is thoroughly inspected before listing.</p>
            </div>
            <div class="feature-card animate-fade-up delay-1">
                <div class="feature-card__icon">₦</div>
                <h3>Transparent Pricing</h3>
                <p>No hidden fees. Clear prices on every listing.</p>
            </div>
            <div class="feature-card animate-fade-up delay-2">
                <div class="feature-card__icon">💬</div>
                <h3>WhatsApp Support</h3>
                <p>Contact us instantly via WhatsApp for any car.</p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
