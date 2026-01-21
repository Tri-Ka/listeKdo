<?php
    $targetDir = '../uploads/test';
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777);
    }

    $ch = curl_init('https://lh3.googleusercontent.com/a-/AAuE7mB-ChoEUiYyEjBdaK_6dlVQy42SEdcEuUe6cE4-Yic=s96-c');
    $fp = fopen($targetDir.'/googleImage.jpg', 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    var_dump('ok', file_exists($targetDir.'/googleImage.jpg'));exit;
