<?php
/*
 * Aperçus de partage : les métadonnées d'une liste privée restent anonymes, même pour ses gestionnaires.
 * Aucun cadeau, nom, titre personnel ou jeton d'invitation n'est utilisé pour son aperçu.
 */
function social_metadata($ctx, $theme, $isHowItWorks)
{
    $base = 'http://datcharrye.free.fr/listeKdo/';
    $owner = $ctx['owner'];
    $image = 'home';
    $title = 'Liste de Kdo · Vos envies, leurs belles attentions';
    $description = 'Créez votre liste de cadeaux et partagez-la avec vos proches. Anniversaire, Noël, naissance, mariage ou simples envies : gardez le plaisir de la surprise.';
    $url = $base;
    $noIndex = false;
    $alt = 'Liste de Kdo : le plaisir de faire plaisir, avec des cadeaux à partager entre proches.';

    if ($isHowItWorks) {
        $title = 'Comment ça marche ? · Liste de Kdo';
        $description = 'Découvrez comment créer et partager une liste de cadeaux, réserver une idée ou participer à plusieurs, sans gâcher la surprise.';
        $url .= '?page=comment-ca-marche';
    } elseif ($owner && is_private_list($owner)) {
        $title = 'Une liste privée vous attend · Liste de Kdo';
        $description = 'Une liste de cadeaux partagée en toute discrétion. Les personnes invitées peuvent la découvrir après connexion ou création d’un compte.';
        $image = 'private';
        $url = share_url($owner);
        $alt = 'Liste de Kdo : une liste privée, une belle attention.';
        $noIndex = true;
    } elseif ($owner) {
        $themes = themes();
        $key = isset($theme['key']) && isset($themes[$theme['key']]) ? $theme['key'] : 'wishlist';
        $image = $key;
        $title = theme_text($theme, 'heading', $owner) . ' · Liste de Kdo';
        $descriptions = array(
            'birthday' => 'Découvrez les idées de cette liste d’anniversaire. Réservez un cadeau, seul ou à plusieurs, et gardez la surprise jusqu’au grand jour.',
            'noel' => 'Découvrez les envies de cette liste de Noël. Réservez vos cadeaux entre proches pour éviter les doublons et garder la surprise.',
            'naissance' => 'Découvrez les petites et grandes envies pour accueillir bébé. Choisissez une attention utile ou participez à un cadeau à plusieurs.',
            'mariage' => 'Découvrez les envies des futurs mariés. Réservez une belle attention ou participez à plusieurs pour célébrer leur nouvelle vie.',
            'wishlist' => 'Découvrez les envies de cette liste de cadeaux. Trouvez une belle attention à offrir, réservez-la et gardez le plaisir de la surprise.',
        );
        $description = $descriptions[$key];
        $url = share_url($owner);
        $alt = 'Liste de Kdo · ' . $themes[$key]['title'] . ' : des envies à partager entre proches.';
    } elseif ('' !== input('user')) {
        $title = 'Liste introuvable · Liste de Kdo';
        $description = 'Cette liste n’est plus disponible. Retrouvez vos proches ou créez votre propre liste de cadeaux sur Liste de Kdo.';
        $noIndex = true;
    }

    return array(
        'title' => $title,
        'description' => $description,
        'url' => $url,
        'image' => $base . asset('img/social/' . $image . '.jpg'),
        'imageAlt' => $alt,
        'noIndex' => $noIndex,
    );
}
