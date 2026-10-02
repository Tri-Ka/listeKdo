<?php
/*
 * Envoi d'images (avatars et idées).
 *
 * Le navigateur redimensionne déjà les photos avant l'envoi (js/app.js).
 * Ici, on vérifie que le fichier est bien une image, on lui donne un nom aléatoire
 * avec une extension imposée par son vrai type, et on la ré-encode avec GD quand
 * c'est possible. Le ré-encodage supprime aussi tout contenu caché dans le fichier.
 * La mémoire est limitée à 32 Mo chez Free : au-delà de 3 Mpx, l'image est gardée telle quelle.
 */

define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024);
define('UPLOAD_MAX_PIXELS_TO_PROCESS', 3000000);

/**
 * Indique si un fichier a été envoyé dans ce champ.
 */
function upload_present($field)
{
    return isset($_FILES[$field]) && UPLOAD_ERR_NO_FILE !== $_FILES[$field]['error'] && '' !== $_FILES[$field]['name'];
}

/**
 * Enregistre l'image du champ $field dans $directory (relatif à la racine du site).
 * Retourne array('file' => nom) ou array('error' => message).
 */
function upload_image($field, $directory, $maxSide)
{
    $file = $_FILES[$field];

    if (UPLOAD_ERR_OK !== $file['error']) {
        if (UPLOAD_ERR_INI_SIZE === $file['error'] || UPLOAD_ERR_FORM_SIZE === $file['error']) {
            return array('error' => "L'image ne doit pas dépasser 2 Mo.");
        }

        return array('error' => "L'image n'a pas pu être envoyée.");
    }

    if ($file['size'] > UPLOAD_MAX_BYTES) {
        return array('error' => "L'image ne doit pas dépasser 2 Mo.");
    }

    $info = @getimagesize($file['tmp_name']);
    $extensions = array(IMAGETYPE_GIF => 'gif', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');

    if (!$info || !isset($extensions[$info[2]])) {
        return array('error' => "Le fichier n'est pas une image (JPEG, PNG ou GIF).");
    }

    $path = KDO_ROOT . '/' . trim($directory, '/');
    if (!is_dir($path) && !@mkdir($path, 0755)) {
        return array('error' => "Le dossier d'images n'est pas accessible.");
    }

    $name = substr(random_token(), 0, 24) . '.' . $extensions[$info[2]];
    $target = $path . '/' . $name;

    if (!upload_reencode($file['tmp_name'], $target, $info, $maxSide)) {
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return array('error' => "L'image n'a pas pu être enregistrée.");
        }
    }

    @chmod($target, 0644);

    return array('file' => $name);
}

/**
 * Ré-encode (et réduit si besoin) une image JPEG ou PNG. Retourne false si ce n'est pas possible.
 */
function upload_reencode($source, $target, $info, $maxSide)
{
    $width = $info[0];
    $height = $info[1];
    $type = $info[2];

    if (IMAGETYPE_GIF === $type || $width * $height > UPLOAD_MAX_PIXELS_TO_PROCESS || !function_exists('imagecreatetruecolor')) {
        return false;
    }

    $image = IMAGETYPE_JPEG === $type ? @imagecreatefromjpeg($source) : @imagecreatefrompng($source);
    if (!$image) {
        return false;
    }

    if (IMAGETYPE_JPEG === $type) {
        $image = upload_fix_orientation($image, $source);
        $width = imagesx($image);
        $height = imagesy($image);
    }

    $ratio = min(1, $maxSide / max($width, $height));
    $newWidth = max(1, (int) round($width * $ratio));
    $newHeight = max(1, (int) round($height * $ratio));

    if ($ratio < 1) {
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if (IMAGETYPE_PNG === $type) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    } elseif (IMAGETYPE_PNG === $type) {
        imagesavealpha($image, true);
    }

    $ok = IMAGETYPE_JPEG === $type ? imagejpeg($image, $target, 85) : imagepng($image, $target);
    imagedestroy($image);

    return $ok;
}

/**
 * Les photos de téléphone sont souvent stockées tournées, avec l'orientation dans les données EXIF.
 */
function upload_fix_orientation($image, $source)
{
    if (!function_exists('exif_read_data') || !function_exists('imagerotate')) {
        return $image;
    }

    $exif = @exif_read_data($source);
    $angles = array(3 => 180, 6 => 270, 8 => 90);

    if (!$exif || !isset($exif['Orientation']) || !isset($angles[$exif['Orientation']])) {
        return $image;
    }

    $rotated = imagerotate($image, $angles[$exif['Orientation']], 0);
    if (!$rotated) {
        return $image;
    }

    imagedestroy($image);

    return $rotated;
}

/**
 * Supprime les images d'idées (uploads/img/) que plus aucune idée n'utilise.
 * Appelé à chaque suppression d'idée : on balaie tout le dossier pour rattraper aussi l'existant.
 *
 * Les anciens noms de fichiers n'ont pas le même encodage en base et sur le disque
 * (accents ré-encodés, antislashs des magic quotes…). On compare donc une clé réduite
 * aux lettres et chiffres ASCII : en cas de doute, le fichier est gardé.
 * Les fichiers de moins d'un jour sont gardés, au cas où une idée serait en cours d'enregistrement.
 * Retourne le nombre de fichiers supprimés, ou false si la base n'a pas pu être lue.
 */
function upload_purge_orphan_images()
{
    $result = db_query('SELECT file, image_url FROM liste_noel');
    if (!$result) {
        return false;
    }

    $used = array();
    while ($row = mysql_fetch_assoc($result)) {
        if ('' !== (string) $row['file']) {
            $used[upload_loose_name($row['file'])] = true;
        }
        if ('uploads/img/' === substr((string) $row['image_url'], 0, 12)) {
            $used[upload_loose_name(substr($row['image_url'], 12))] = true;
        }
    }

    $directory = KDO_ROOT . '/uploads/img';
    $handle = @opendir($directory);
    if (!$handle) {
        return 0;
    }

    $limit = time() - 86400;
    $deleted = 0;
    while (false !== ($name = readdir($handle))) {
        $path = $directory . '/' . $name;
        $key = upload_loose_name($name);

        if ('.' === substr($name, 0, 1) || '' === $key || isset($used[$key]) || !is_file($path) || filemtime($path) > $limit) {
            continue;
        }

        if (@unlink($path)) {
            $deleted++;
        }
    }
    closedir($handle);

    return $deleted;
}

/**
 * Nom de fichier réduit à ses lettres et chiffres ASCII, en minuscules.
 */
function upload_loose_name($name)
{
    return preg_replace('/[^a-z0-9]/', '', strtolower($name));
}
