<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pageUrl'])) {
    $url = $_POST['pageUrl'];

    // Function to retrieve HTML content from any URL
    function getPageContent($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    // Fetch the HTML content of the page
    $htmlContent = getPageContent($url);

    // Function to extract metadata
    function extractMetaTagContent($htmlContent, $property)
    {
        preg_match('/<meta[^>]*property="'.$property.'"[^>]*content="([^"]*)"/i', $htmlContent, $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }

    // Extract title, image, description, and other meta tags using Open Graph or Twitter Card standards
    $metaTitle = extractMetaTagContent($htmlContent, 'og:title');
    if (!$metaTitle) {
        $metaTitle = extractMetaTagContent($htmlContent, 'twitter:title');
    }

    $metaImage = extractMetaTagContent($htmlContent, 'og:image');
    if (!$metaImage) {
        $metaImage = extractMetaTagContent($htmlContent, 'twitter:image');
    }

    $metaDescription = extractMetaTagContent($htmlContent, 'og:description');
    if (!$metaDescription) {
        $metaDescription = extractMetaTagContent($htmlContent, 'twitter:description');
    }

    // Handle default values for missing metadata
    if (!$metaTitle) {
        $metaTitle = 'No title available';
    }
    if (!$metaImage) {
        $metaImage = 'No image available';
    }
    if (!$metaDescription) {
        $metaDescription = 'No description available';
    }

    // Manually build the JSON response
    $metadata = '{"title": "' . addslashes($metaTitle) . '", "image": "' . addslashes($metaImage) . '", "description": "' . addslashes($metaDescription) . '"}';

    // Return the JSON response
    echo $metadata;
}
