<?php
/**
 * Frank-Adm – Bild-Upload.
 * Nimmt eine hochgeladene Bilddatei entgegen und legt sie
 * unter assets/img/ ab. Liefert den öffentlichen Pfad zurück.
 */
declare(strict_types=1);

const ADM_IMG_DIR = '/assets/img/';

/**
 * @param array $file  Ein $_FILES['file']-Eintrag
 * @return array [bool, string (Pfad oder Fehlermeldung)]
 */
function adm_handle_upload(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'Kein Bild erhalten (' . ($file['error'] ?? '?') . ').'];
    }

    $size = $file['size'] ?? 0;
    if ($size <= 0 || $size > 8 * 1024 * 1024) {
        return [false, 'Bild zu groß (max. 8 MB).'];
    }

    // Nur echte Bilddateien akzeptieren (prüft den Inhalt, nicht den Namen).
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return [false, 'Die Datei ist kein gültiges Bild.'];
    }

    $mime = $info['mime'] ?? '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/svg+xml' => 'svg',
    ];
    if (!isset($allowed[$mime])) {
        return [false, 'Bildformat nicht erlaubt (' . $mime . ').'];
    }

    $ext = $allowed[$mime];
    $origName = pathinfo($file['name'] ?? 'bild', PATHINFO_FILENAME);
    $base = strtolower(preg_replace('/[^a-z0-9_-]+/i', '-', $origName) ?: 'bild');
    $base = trim($base, '-') ?: 'bild';

    $absDir = adm_root() . ADM_IMG_DIR;
    if (!is_dir($absDir)) {
        @mkdir($absDir, 0775, true);
    }

    $target = $base . '.' . $ext;
    $n = 1;
    while (is_file($absDir . $target)) {
        $target = $base . '-' . ($n++) . '.' . $ext;
    }

    $absFile = $absDir . $target;
    if (!@move_uploaded_file($file['tmp_name'], $absFile)) {
        return [false, 'Bild konnte nicht gespeichert werden (Rechte auf assets/img prüfen).'];
    }
    @chmod($absFile, 0664);

    return [true, ADM_IMG_DIR . $target];
}

/** Liste vorhandener Bilder in assets/img als [path, name]. */
function adm_image_list(): array {
    $absDir = adm_root() . '/assets/img/';
    $out = [];
    if (!is_dir($absDir)) {
        return $out;
    }
    $files = scandir($absDir);
    if ($files === false) {
        return $out;
    }
    $names = array_filter($files, function ($f) {
        return preg_match('/\.(jpe?g|png|gif|avif|svg)$/i', $f);
    });
    sort($names, SORT_STRING);
    foreach ($names as $name) {
        $out[] = ['path' => '/assets/img/' . $name, 'name' => $name];
    }
    return $out;
}
