<?php
/**
 * VA Auto Sales — PWA Asset Generator
 * Generates Android/iOS-compatible app icons and splash screens.
 */

// Define execution constraints
set_time_limit(180);
ini_set('memory_limit', '512M');

// Check GD library availability
if (!extension_loaded('gd')) {
    echo "CRITICAL ERROR: PHP GD library is not enabled in your PHP installation. Please enable it in php.ini to generate PWA assets.\n";
    exit(1);
}

// Target Directories
$baseDir = dirname(__DIR__);
$imageDir = $baseDir . '/Frontend/assets/images';
$splashDir = $imageDir . '/splash';

// Create directories if they do not exist
if (!is_dir($imageDir)) {
    mkdir($imageDir, 0755, true);
}
if (!is_dir($splashDir)) {
    mkdir($splashDir, 0755, true);
}

// Configurable Styling
$primaryColor = ['r' => 30, 'g' => 64, 'b' => 175]; // #1e40af (Blue)
$textColor = ['r' => 255, 'g' => 255, 'b' => 255];   // #ffffff (White)

// Font file path (Windows Arial Bold is standard)
$fontFile = 'C:\Windows\Fonts\arialbd.ttf';
$useTTF = is_file($fontFile) && function_exists('imagettftext');

if ($useTTF) {
    echo "Using system font: {$fontFile}\n";
} else {
    echo "System font not found or TTF not supported. Falling back to built-in GD fonts.\n";
}

/**
 * Draw a filled rounded rectangle in GD
 */
function drawFilledRoundedRect($im, $x1, $y1, $x2, $y2, $radius, $color) {
    // Make sure radius is within bounds
    $radius = min($radius, ($x2 - $x1) / 2, ($y2 - $y1) / 2);
    
    // Draw body rectangles
    imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    
    // Draw 4 corner circles
    imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
}

/**
 * Render Logo (Icon) onto a canvas
 */
function renderLogoMark($im, $width, $height, $isMaskable, $primaryColor, $textColor, $fontFile, $useTTF) {
    // Colors
    $bgCol = imagecolorallocate($im, $primaryColor['r'], $primaryColor['g'], $primaryColor['b']);
    $txtCol = imagecolorallocate($im, $textColor['r'], $textColor['g'], $textColor['b']);
    
    if ($isMaskable) {
        // Maskable icon must fill the entire canvas with background
        imagefill($im, 0, 0, $bgCol);
        
        // Draw slightly smaller logo block in the center (safe zone is inner 60%)
        $logoSize = min($width, $height) * 0.55;
        $x1 = ($width - $logoSize) / 2;
        $y1 = ($height - $logoSize) / 2;
        $x2 = $x1 + $logoSize;
        $y2 = $y1 + $logoSize;
        $radius = $logoSize * 0.1875;
        
        // White outline border for maskable logo block
        $outlineCol = imagecolorallocate($im, 255, 255, 255);
        // We will just draw a rounded white text in the center
    } else {
        // Standard Icon: transparent background, rounded rectangle filling the image
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);
        
        imagealphablending($im, true);
        $radius = min($width, $height) * 0.1875; // 6px for 32px standard
        drawFilledRoundedRect($im, 0, 0, $width, $height, $radius, $bgCol);
    }
    
    // Draw "VA" Text in the center
    $text = "VA";
    if ($useTTF) {
        // Dynamically compute font size based on height
        $fontSize = min($width, $height) * 0.45;
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        
        $x = ($width - $textWidth) / 2 - $bbox[0];
        $y = ($height - $textHeight) / 2 - $bbox[7];
        
        imagettftext($im, $fontSize, 0, $x, $y, $txtCol, $fontFile, $text);
    } else {
        // Fallback using built-in font
        $font = 5; // largest built-in font
        $fw = imagefontwidth($font);
        $fh = imagefontheight($font);
        $tw = $fw * strlen($text);
        $th = $fh;
        
        $x = ($width - $tw) / 2;
        $y = ($height - $th) / 2;
        
        imagestring($im, $font, $x, $y, $text, $txtCol);
    }
}

// -------------------------------------------------------------
// STEP 1: Generate Standard and Maskable Icons
// -------------------------------------------------------------
$icons = [
    ['file' => 'icon-192.png', 'size' => 192, 'maskable' => false],
    ['file' => 'icon-512.png', 'size' => 512, 'maskable' => false],
    ['file' => 'icon-192-maskable.png', 'size' => 192, 'maskable' => true],
    ['file' => 'icon-512-maskable.png', 'size' => 512, 'maskable' => true],
    ['file' => 'apple-touch-icon.png', 'size' => 180, 'maskable' => true] // iOS home screen prefers full-square / pre-masked icons
];

echo "Generating PWA Icons...\n";
foreach ($icons as $icon) {
    $im = imagecreatetruecolor($icon['size'], $icon['size']);
    renderLogoMark($im, $icon['size'], $icon['size'], $icon['maskable'], $primaryColor, $textColor, $fontFile, $useTTF);
    
    $outputPath = $imageDir . '/' . $icon['file'];
    imagepng($im, $outputPath);
    imagedestroy($im);
    
    echo "  [Created] {$icon['file']} ({$icon['size']}x{$icon['size']})\n";
}

// -------------------------------------------------------------
// STEP 2: Generate iOS Launch Screens (Splash Screens)
// -------------------------------------------------------------
$splashScreens = [
    '1290-2796' => [1290, 2796, 'iPhone 15 Pro Max, 14 Pro Max'],
    '1179-2556' => [1179, 2556, 'iPhone 15 Pro, 15, 14 Pro'],
    '1284-2778' => [1284, 2778, 'iPhone 14 Plus, 13 Pro Max, 12 Pro Max'],
    '1170-2532' => [1170, 2532, 'iPhone 14, 13 Pro, 13, 12 Pro, 12'],
    '1125-2436' => [1125, 2436, 'iPhone X, XS, 11 Pro'],
    '1242-2688' => [1242, 2688, 'iPhone XS Max, 11 Pro Max'],
    '828-1792'  => [828, 1792,  'iPhone XR, 11'],
    '1242-2208' => [1242, 2208, 'iPhone 8 Plus, 7 Plus, 6s Plus'],
    '750-1334'  => [750, 1334,  'iPhone 8, 7, 6s, SE (2nd gen)']
];

echo "\nGenerating iOS Splash Screens...\n";
foreach ($splashScreens as $resKey => $details) {
    list($width, $height, $desc) = $details;
    
    $im = imagecreatetruecolor($width, $height);
    
    // Background fill (Primary Blue Color)
    $bgCol = imagecolorallocate($im, $primaryColor['r'], $primaryColor['g'], $primaryColor['b']);
    imagefill($im, 0, 0, $bgCol);
    
    // Center Logo dimensions
    $logoSize = min($width, $height) * 0.18; // 18% of smallest dimension
    $logoSize = max($logoSize, 150); // Min 150px
    $logoSize = min($logoSize, 260); // Max 260px
    
    // Draw a rounded rectangle logo mark container in the center
    $logoX1 = ($width - $logoSize) / 2;
    $logoY1 = ($height - $logoSize) / 2 - ($height * 0.05); // slightly shifted up for visual balance
    $logoX2 = $logoX1 + $logoSize;
    $logoY2 = $logoY1 + $logoSize;
    
    // White logo box container (hollow or solid)
    $logoBorderCol = imagecolorallocate($im, 255, 255, 255);
    // Draw rounded background block for logo
    drawFilledRoundedRect($im, $logoX1, $logoY1, $logoX2, $logoY2, $logoSize * 0.1875, $logoBorderCol);
    
    // Draw "VA" Text in the center of the logo container
    $txtCol = imagecolorallocate($im, $primaryColor['r'], $primaryColor['g'], $primaryColor['b']); // blue text on white block
    $text = "VA";
    
    if ($useTTF) {
        $fontSize = $logoSize * 0.45;
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        
        $x = $logoX1 + ($logoSize - $textWidth) / 2 - $bbox[0];
        $y = $logoY1 + ($logoSize - $textHeight) / 2 - $bbox[7];
        imagettftext($im, $fontSize, 0, $x, $y, $txtCol, $fontFile, $text);
        
        // Draw "VA Auto Sales" Site Name below the Logo Box
        $siteText = "VA Auto Sales";
        $siteTextCol = imagecolorallocate($im, 255, 255, 255); // white text
        $siteFontSize = min($width, $height) * 0.035; // proportional size
        $siteFontSize = max($siteFontSize, 18);
        $siteFontSize = min($siteFontSize, 28);
        
        $siteBbox = imagettfbbox($siteFontSize, 0, $fontFile, $siteText);
        $siteTextWidth = $siteBbox[2] - $siteBbox[0];
        $siteTextHeight = $siteBbox[1] - $siteBbox[7];
        
        $siteX = ($width - $siteTextWidth) / 2 - $siteBbox[0];
        $siteY = $logoY2 + 50 + $siteTextHeight; // 50px below the logo box
        imagettftext($im, $siteFontSize, 0, $siteX, $siteY, $siteTextCol, $fontFile, $siteText);
        
        // Draw tagline
        $tagline = "Premium Pre-Owned Vehicles";
        $taglineCol = imagecolorallocatealpha($im, 255, 255, 255, 30); // semi-transparent white
        $taglineFontSize = $siteFontSize * 0.65;
        
        $tagBbox = imagettfbbox($taglineFontSize, 0, $fontFile, $tagline);
        $tagWidth = $tagBbox[2] - $tagBbox[0];
        
        $tagX = ($width - $tagWidth) / 2 - $tagBbox[0];
        $tagY = $siteY + 30; // 30px below the site title
        imagettftext($im, $taglineFontSize, 0, $tagX, $tagY, $taglineCol, $fontFile, $tagline);
        
    } else {
        // Simple fallback
        $font = 5;
        $fw = imagefontwidth($font);
        $fh = imagefontheight($font);
        
        $x = $logoX1 + ($logoSize - ($fw * strlen($text))) / 2;
        $y = $logoY1 + ($logoSize - $fh) / 2;
        imagestring($im, $font, $x, $y, $text, $txtCol);
        
        $siteText = "VA Auto Sales";
        $siteX = ($width - ($fw * strlen($siteText))) / 2;
        $siteY = $logoY2 + 30;
        $siteTextCol = imagecolorallocate($im, 255, 255, 255);
        imagestring($im, $font, $siteX, $siteY, $siteText, $siteTextCol);
    }
    
    $outputPath = $splashDir . "/apple-splash-{$resKey}.png";
    imagepng($im, $outputPath);
    imagedestroy($im);
    
    echo "  [Created] apple-splash-{$resKey}.png ({$width}x{$height}) — {$desc}\n";
}

echo "\nAll assets generated successfully!\n";
exit(0);
