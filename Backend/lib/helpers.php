<?php
/**
 * Shared utility functions
 */

/**
 * Application root path relative to web document root (empty string if at domain root)
 */
function basePath(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configFile = __DIR__ . '/../config/app.php';
    if (is_file($configFile)) {
        $cfg = require $configFile;
        if (array_key_exists('base_path_override', $cfg) && $cfg['base_path_override'] !== null) {
            $override = trim(str_replace('\\', '/', (string) $cfg['base_path_override']), '/');
            $base = $override;
            return $base;
        }
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = realpath(__DIR__ . '/../..');

    if ($docRoot && $appRoot) {
        $docRoot = str_replace('\\', '/', rtrim($docRoot, '/'));
        $appRoot = str_replace('\\', '/', rtrim($appRoot, '/'));
        if (str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
            $base = $base === '' ? '' : trim($base, '/');
            return $base;
        }
    }

    // Fallback for shared hosts where realpath() fails (common on InfinityFree)
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptName !== '' && preg_match('#^/([^/]+)/(?:Frontend/|Backend/)#', $scriptName, $m)) {
        $base = $m[1];
        return $base;
    }

    $base = '';
    return $base;
}

/** Build a root-relative URL path */
function url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = basePath();
    if ($path === '') {
        return $base ?: '/';
    }
    return ($base ? $base . '/' : '/') . $path;
}

/** Build a full absolute URL including scheme and host */
function fullUrl(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $relative = url($path);
    if ($relative === '/') {
        return $scheme . '://' . $host;
    }
    return $scheme . '://' . $host . $relative;
}

function appConfig(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';

        // Optional local Groq override (keeps API key out of main config)
        $groqLocal = __DIR__ . '/../config/groq.local.php';
        if (is_file($groqLocal)) {
            $localGroq = require $groqLocal;
            $config['groq'] = array_merge($config['groq'] ?? [], $localGroq);
        }

        // Auto-resolve paths (works in subfolder or domain root)
        $config['base_path']   = basePath();
        $config['site_url']    = fullUrl();
        $config['uploads_url'] = url('Backend/uploads/cars');
    }
    return $config;
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function formatPrice(float $price): string
{
    return '₦' . number_format($price, 0, '.', ',');
}

/**
 * Build WhatsApp contact URL with prefilled message
 * Stage 2: hook into lead tracking before redirect
 */
function whatsappLink(string $carTitle, float $price, ?string $carId = null): string
{
    $config = appConfig();
    $message = sprintf(
        "I'm interested in %s priced at %s",
        $carTitle,
        formatPrice($price)
    );

    if ($carId !== null) {
        $message .= " (Listing #%s)";
        $message = sprintf($message, $carId);
    }

    return 'https://wa.me/' . $config['whatsapp_number'] . '?text=' . rawurlencode($message);
}

function getImageUrl(?string $filename): string
{
    $config = appConfig();
    $placeholder = url('Frontend/assets/images/car-placeholder.svg');

    if (empty($filename)) {
        return $placeholder;
    }

    $path = $config['uploads_path'] . '/' . $filename;
    if (!is_file($path)) {
        return $placeholder;
    }

    return $config['uploads_url'] . '/' . rawurlencode($filename);
}

function parseJsonField(?string $json, array $default = []): array
{
    if (empty($json)) {
        return $default;
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : $default;
}

function getCarImageUrls(array $filenames): array
{
    return array_map(function ($filename) {
        return getImageUrl($filename);
    }, $filenames);
}

function handleImageUpload(array $file, array $existingImages = []): array
{
    $config = appConfig();
    $errors = [];
    $uploaded = $existingImages;
    $maxImages = (int) ($config['max_images_per_car'] ?? 5);

    if (!isset($file['name']) || !is_array($file['name'])) {
        return ['images' => $uploaded, 'errors' => $errors];
    }

    $uploadDir = $config['uploads_path'];
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    foreach ($file['name'] as $index => $name) {
        if ($file['error'][$index] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (count($uploaded) >= $maxImages) {
            $errors[] = "Maximum {$maxImages} images allowed per listing. Extra files were skipped.";
            break;
        }

        if ($file['error'][$index] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload failed for {$name}";
            continue;
        }

        if ($file['size'][$index] > $config['max_upload_size']) {
            $errors[] = "{$name} exceeds max file size";
            continue;
        }

        $mime = mime_content_type($file['tmp_name'][$index]);
        if (!in_array($mime, $config['allowed_images'], true)) {
            $errors[] = "{$name} is not a valid image type";
            continue;
        }

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $newName = uniqid('car_', true) . '.' . strtolower($ext);
        $destination = $uploadDir . '/' . $newName;

        if (move_uploaded_file($file['tmp_name'][$index], $destination)) {
            $uploaded[] = $newName;
        } else {
            $errors[] = "Could not save {$name}";
        }
    }

    return ['images' => array_slice($uploaded, 0, $maxImages), 'errors' => $errors];
}

function deleteImageFiles(array $images): void
{
    $config = appConfig();
    foreach ($images as $image) {
        $path = $config['uploads_path'] . '/' . $image;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
