<?php
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get parameters
$code = $_GET['code'] ?? 'ABCD12';
$groupName = $_GET['group'] ?? 'My Group';
$groupImageUrl = $_GET['image'] ?? null;

// Debug log
error_log("Generating preview for code: $code, group: $groupName, image: $groupImageUrl");

try {
    // Create a new ImageMagick object for the main canvas
    $image = new Imagick();
    $image->newImage(1200, 630, new ImagickPixel('white'));
    $image->setImageFormat('png');
    
    // Load and prepare group image if provided
    if ($groupImageUrl) {
        try {
            error_log("Attempting to load group image from: $groupImageUrl");
            
            // Create a stream context with specific options
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: PHP/Cru-Preview-Generator',
                        'Accept: image/jpeg,image/png,image/*'
                    ],
                    'timeout' => 15,
                    'follow_location' => 1
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];
            $context = stream_context_create($opts);
            
            // Try to load the image with the custom context
            $imageData = file_get_contents($groupImageUrl, false, $context);
            if ($imageData === false) {
                error_log("Failed to download image with context");
                throw new Exception("Failed to download image");
            }
            
            error_log("Successfully downloaded image data: " . strlen($imageData) . " bytes");
            
            // Create Imagick object directly from the downloaded data
            $groupImage = new Imagick();
            $groupImage->readImageBlob($imageData);
            
            // Resize the image
            $groupImage->resizeImage(240, 240, Imagick::FILTER_LANCZOS, 1);
            
            // Create a new canvas for the rounded image
            $roundedImage = new Imagick();
            $roundedImage->newImage(240, 240, new ImagickPixel('transparent'), 'png');
            
            // Create rounded rectangle path
            $draw = new ImagickDraw();
            $draw->setFillColor(new ImagickPixel('white'));
            $draw->roundRectangle(0, 0, 239, 239, 32, 32);
            
            // Draw the rounded rectangle mask
            $mask = new Imagick();
            $mask->newImage(240, 240, new ImagickPixel('transparent'), 'png');
            $mask->drawImage($draw);
            
            // Apply the mask to the group image
            $groupImage->compositeImage($mask, Imagick::COMPOSITE_COPYOPACITY, 0, 0);
            
            // Draw the image onto the rounded canvas
            $roundedImage->compositeImage($groupImage, Imagick::COMPOSITE_OVER, 0, 0);
            
            // Add blue stroke
            $stroke = new ImagickDraw();
            $stroke->setStrokeColor(new ImagickPixel('rgb(0, 122, 255)'));
            $stroke->setStrokeWidth(2);
            $stroke->setFillColor('none');
            $stroke->roundRectangle(1, 1, 238, 238, 32, 32);
            $roundedImage->drawImage($stroke);
            
            // Use the rounded image instead of the original
            $groupImage = $roundedImage;
            
            error_log("Successfully processed group image");
        } catch (Exception $e) {
            error_log("Error processing group image: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            // If group image fails, fall back to default Cru icon
            $groupImage = new Imagick(__DIR__ . '/Cru_icon.png');
            $groupImage->resizeImage(240, 240, Imagick::FILTER_LANCZOS, 1);
        }
    } else {
        $groupImage = new Imagick(__DIR__ . '/Cru_icon.png');
        $groupImage->resizeImage(240, 240, Imagick::FILTER_LANCZOS, 1);
    }
    
    // Composite group image onto main image (centered horizontally, slightly above center vertically)
    $image->compositeImage($groupImage, Imagick::COMPOSITE_OVER, (1200 - 240) / 2, 100);
    
    // Create text settings
    $text = new ImagickDraw();
    $text->setFont('Helvetica-Bold');
    $text->setTextAlignment(Imagick::ALIGN_CENTER);
    
    // Draw "Join my Crü" with improved styling
    $text->setFontSize(56);  // Increased font size
    $text->setFillColor('rgb(10, 132, 255)');  // Brighter iOS blue
    $image->annotateImage($text, 600, 420, 0, "Join my Cr\u{00FC}");
    
    // Draw group name with improved styling
    $text->setFontSize(42);  // Increased font size
    $text->setFillColor('rgb(28, 28, 30)');  // Darker color for better contrast
    $groupNameTruncated = strlen($groupName) > 25 ? substr($groupName, 0, 25) . '...' : $groupName;
    $image->annotateImage($text, 600, 480, 0, $groupNameTruncated);
    
    // Improved box styling for invite code
    $boxWidth = 70;  // Slightly larger boxes
    $boxHeight = 90;
    $boxSpacing = 12;  // Tighter spacing
    $startX = 600 - (($boxWidth * 6 + $boxSpacing * 5) / 2);
    $y = 520;
    
    // Draw boxes and code characters with improved styling
    $characters = str_split(strtoupper($code));
    for ($i = 0; $i < 6; $i++) {
        // Draw box with softer background
        $box = new ImagickDraw();
        $box->setFillColor(new ImagickPixel('rgb(245, 245, 247)'));  // Lighter background
        $box->setStrokeColor(new ImagickPixel('rgb(10, 132, 255)'));  // Matching blue
        $box->setStrokeWidth(2.5);  // Slightly thicker border
        $box->roundRectangle(
            $startX + ($boxWidth + $boxSpacing) * $i,
            $y,
            $startX + ($boxWidth + $boxSpacing) * $i + $boxWidth,
            $y + $boxHeight,
            16, 16  // More rounded corners
        );
        $image->drawImage($box);
        
        // Draw character with improved styling
        $text->setFontSize(48);  // Larger characters
        $text->setFillColor('rgb(10, 132, 255)');  // Matching blue
        $text->setTextKerning(0);
        $image->annotateImage(
            $text,
            $startX + ($boxWidth + $boxSpacing) * $i + ($boxWidth/2),
            $y + 60,
            0,
            $characters[$i]
        );
    }
    
    // Add subtle shadow to the group image
    if (isset($groupImage)) {
        $shadow = clone $groupImage;
        $shadow->shadowImage(80, 3, 3, 1);  // opacity, sigma, x-offset, y-offset (using integer 1 instead of 0.5)
        $image->compositeImage($shadow, Imagick::COMPOSITE_OVER, (1200 - 240) / 2, 100);
        $image->compositeImage($groupImage, Imagick::COMPOSITE_OVER, (1200 - 240) / 2, 100);
    }
    
    // Draw website with improved styling
    $text->setFontSize(32);  // Larger website text
    $text->setFillColor('rgb(142, 142, 147)');  // iOS system gray
    $image->annotateImage($text, 600, 660, 0, "meetcru.com");
    
    // Output image
    $image->setImageCompressionQuality(95);
    echo $image->getImageBlob();
    
    // Clean up
    $image->clear();
    $image->destroy();
    $groupImage->destroy();
    if (isset($roundedImage)) {
        $roundedImage->destroy();
    }
    if (isset($mask)) {
        $mask->destroy();
    }
    
} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    // If ImageMagick fails, create a simple error image
    $image = imagecreatetruecolor(1200, 630);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    imagefilledrectangle($image, 0, 0, 1200, 630, $white);
    imagestring($image, 5, 500, 300, "Error generating preview", $black);
    imagepng($image);
    imagedestroy($image);
}
?>