<?php
require_once __DIR__ . '/../Backend/lib/helpers.php';
require_once __DIR__ . '/../Backend/models/Car.php';

$config = appConfig();
$carModel = new Car();

$filters = [
    'brand'     => $_GET['brand'] ?? '',
    'model'     => $_GET['model'] ?? '',
    'year'      => $_GET['year'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'search'    => $_GET['search'] ?? '',
];

$cars = $carModel->getAll($filters, false);
$brands = $carModel->getBrands();
$years = $carModel->getYears();

$activeFilters = array_filter($filters, fn($v) => $v !== '' && $v !== null);
$activeFilterCount = count($activeFilters);
$hasActiveFilters = $activeFilterCount > 0;

$pageTitle = 'Browse Cars | ' . $config['site_name'];
$pageDescription = 'Browse our full inventory of quality pre-owned vehicles. Filter by brand, price, model, and year.';

// Short cache: 30s (filters can vary per user query)
if (!headers_sent() && !$hasActiveFilters) {
    header('Cache-Control: public, max-age=30, stale-while-revalidate=15');
    header('Vary: Accept-Encoding');
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-header reveal-on-scroll">
    <div class="container">
        <h1>Browse Cars</h1>
        <p><?= count($cars) ?> vehicle<?= count($cars) !== 1 ? 's' : '' ?> available</p>
    </div>
</section>

<section class="section section--compact reveal-on-scroll">
    <div class="container">
        <div class="filter-wrap">
            <div class="filter-mobile-bar">
                <button
                    type="button"
                    class="filter-mobile-toggle"
                    id="filter-toggle"
                    aria-controls="filter-panel"
                    aria-expanded="<?= $hasActiveFilters ? 'true' : 'false' ?>"
                >
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 6h16M7 12h10M10 18h4"/>
                    </svg>
                    <span>Search &amp; Filter</span>
                    <?php if ($hasActiveFilters): ?>
                        <span class="filter-badge"><?= $activeFilterCount ?></span>
                    <?php endif; ?>
                </button>

                <?php if ($hasActiveFilters): ?>
                <div class="active-filters" aria-label="Active filters">
                    <?php if ($filters['search']): ?>
                        <span class="active-filter-chip">"<?= sanitize($filters['search']) ?>"</span>
                    <?php endif; ?>
                    <?php if ($filters['brand']): ?>
                        <span class="active-filter-chip"><?= sanitize($filters['brand']) ?></span>
                    <?php endif; ?>
                    <?php if ($filters['model']): ?>
                        <span class="active-filter-chip"><?= sanitize($filters['model']) ?></span>
                    <?php endif; ?>
                    <?php if ($filters['year']): ?>
                        <span class="active-filter-chip"><?= (int) $filters['year'] ?></span>
                    <?php endif; ?>
                    <?php if ($filters['min_price']): ?>
                        <span class="active-filter-chip">Min ₦<?= number_format((float) $filters['min_price']) ?></span>
                    <?php endif; ?>
                    <?php if ($filters['max_price']): ?>
                        <span class="active-filter-chip">Max ₦<?= number_format((float) $filters['max_price']) ?></span>
                    <?php endif; ?>
                    <a href="listings.php" class="active-filter-clear">Clear all</a>
                </div>
                <?php endif; ?>
            </div>

            <form action="listings.php" method="GET" class="filter-panel<?= $hasActiveFilters ? ' is-open' : '' ?>" id="filter-panel">
                <div class="filter-panel__grid">
                    <div class="form-group form-group--wide">
                        <label for="search">Search</label>
                        <input type="search" id="search" name="search" value="<?= sanitize($filters['search']) ?>" placeholder="Search by name, brand, model..." autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <select id="brand" name="brand">
                            <option value="">All Brands</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= sanitize($brand) ?>" <?= $filters['brand'] === $brand ? 'selected' : '' ?>><?= sanitize($brand) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" value="<?= sanitize($filters['model']) ?>" placeholder="Any model">
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= (int) $year ?>" <?= (string) $filters['year'] === (string) $year ? 'selected' : '' ?>><?= (int) $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-group--half">
                        <label for="min_price">Min Price (₦)</label>
                        <input type="number" id="min_price" name="min_price" value="<?= sanitize($filters['min_price']) ?>" placeholder="0" inputmode="numeric">
                    </div>
                    <div class="form-group form-group--half">
                        <label for="max_price">Max Price (₦)</label>
                        <input type="number" id="max_price" name="max_price" value="<?= sanitize($filters['max_price']) ?>" placeholder="Any" inputmode="numeric">
                    </div>
                </div>
                <div class="filter-panel__actions">
                    <button type="submit" class="btn btn--primary btn--block-mobile">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
                        Apply Filters
                    </button>
                    <a href="listings.php" class="btn btn--outline btn--block-mobile">Clear</a>
                </div>
            </form>
        </div>

        <?php if (empty($cars)): ?>
            <div class="empty-state">
                <p>No cars match your filters.</p>
                <a href="listings.php" class="btn btn--outline">Clear filters</a>
            </div>
        <?php else: ?>
            <!-- Skeleton placeholders: shown instantly, hidden once real cards render -->
            <div id="skeleton-grid" aria-hidden="true" aria-label="Loading car listings">
                <?php $skCount = min(count($cars), 6); for ($i = 0; $i < $skCount; $i++): ?>
                <div class="car-card--skeleton">
                    <div class="sk-image"></div>
                    <div class="sk-body">
                        <div class="sk-title skeleton"></div>
                        <div class="sk-meta skeleton"></div>
                        <div class="sk-price skeleton"></div>
                    </div>
                    <div class="sk-footer">
                        <div class="sk-btn skeleton"></div>
                        <div class="sk-btn skeleton"></div>
                        <div class="sk-btn skeleton"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <!-- Real car cards: hidden until JS swaps them in -->
            <div class="car-grid" id="car-grid" style="display:none;">
                <?php foreach ($cars as $car): ?>
                    <?php include __DIR__ . '/includes/car-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
