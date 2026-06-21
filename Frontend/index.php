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

<section id="location" class="section reveal-on-scroll" style="background: var(--color-bg);">
    <div class="container">
        <div class="section__header">
            <div>
                <h2>Find Us</h2>
                <p style="color: var(--color-text-muted); margin-top: 4px; font-size: 0.9375rem;">Visit our showroom and see the cars in person.</p>
            </div>
        </div>

        <div class="location-grid">
            <!-- Map Embed -->
            <div class="location-map">
                <div class="location-map__frame">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.5!2d7.491302!3d9.072264!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zOcKwMDQnMjAuMiJOIDfCsDI5JzI4LjciRQ!5e0!3m2!1sen!2sng!4v1&markers=color:red%7C9.072264,7.491302"
                        width="100%"
                        height="100%"
                        style="border:0; border-radius: 12px;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="VA Auto Sales Location on Google Maps"
                    ></iframe>
                </div>
                <a 
                    href="https://maps.app.goo.gl/zEWoCGeWBX3KnqCE6?g_st=aw" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="location-map__open-btn"
                    id="open-google-maps"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Open in Google Maps
                </a>
            </div>

            <!-- Location Info Cards -->
            <div class="location-info">
                <div class="location-card location-card--address">
                    <div class="location-card__icon">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="location-card__content">
                        <h3>Our Showroom</h3>
                        <p><?= sanitize($config['site_name']) ?></p>
                        <p>Nigeria</p>
                        <a 
                            href="https://maps.app.goo.gl/zEWoCGeWBX3KnqCE6?g_st=aw" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="location-card__link"
                        >
                            Get Directions &rarr;
                        </a>
                    </div>
                </div>

                <div class="location-card location-card--hours">
                    <div class="location-card__icon">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="location-card__content">
                        <h3>Business Hours</h3>
                        <ul class="location-hours">
                            <li>
                                <span>Mon – Fri</span>
                                <span>8:00 AM – 6:00 PM</span>
                            </li>
                            <li>
                                <span>Saturday</span>
                                <span>9:00 AM – 5:00 PM</span>
                            </li>
                            <li>
                                <span>Sunday</span>
                                <span class="location-hours__closed">Closed</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="location-card location-card--contact">
                    <div class="location-card__icon" style="background: rgba(37, 211, 102, 0.1); color: var(--color-whatsapp);">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </div>
                    <div class="location-card__content">
                        <h3>Contact & WhatsApp</h3>
                        <p>Speak directly with our sales team — available for inquiries, test drives, and negotiations.</p>
                        <a 
                            href="https://wa.me/<?= sanitize($config['whatsapp_number']) ?>" 
                            target="_blank" 
                            rel="noopener"
                            class="btn btn--whatsapp btn--sm" 
                            style="margin-top: 10px; display: inline-flex;"
                            id="location-whatsapp-btn"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Chat on WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.location-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 32px;
    align-items: start;
}

.location-map {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.location-map__frame {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow);
    height: 420px;
    background: #e2e8f0;
}

.location-map__frame iframe {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: none;
}

.location-map__open-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-sm);
    color: var(--color-primary);
    font-weight: 600;
    font-size: 0.9375rem;
    transition: all var(--transition);
    text-decoration: none;
    width: 100%;
}
.location-map__open-btn:hover {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(30, 64, 175, 0.2);
}

.location-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.location-card {
    background: var(--color-surface);
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: var(--shadow);
    display: flex;
    gap: 18px;
    align-items: flex-start;
    border: 1px solid var(--color-border);
    transition: transform var(--transition), box-shadow var(--transition);
}
.location-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.location-card__icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    background: rgba(30, 64, 175, 0.08);
    color: var(--color-primary);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
}

.location-card__content h3 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 6px;
    color: var(--color-text);
}
.location-card__content p {
    font-size: 0.9375rem;
    color: var(--color-text-muted);
    line-height: 1.5;
    margin-bottom: 2px;
}

.location-card__link {
    display: inline-block;
    margin-top: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-primary);
}
.location-card__link:hover {
    color: var(--color-primary-dark);
    text-decoration: underline;
}

.location-hours {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.location-hours li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    gap: 12px;
}
.location-hours li span:first-child {
    color: var(--color-text-muted);
    font-weight: 500;
}
.location-hours li span:last-child {
    font-weight: 600;
    color: var(--color-text);
}
.location-hours__closed {
    color: #ef4444 !important;
}

@media (max-width: 900px) {
    .location-grid {
        grid-template-columns: 1fr;
    }
    .location-map__frame {
        height: 300px;
    }
}

@media (max-width: 600px) {
    .location-map__frame {
        height: 240px;
    }
    .location-card {
        flex-direction: column;
        gap: 12px;
    }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>

