<?php
header('Content-Type: application/json');

$dir = './img/mapIcons/';
$files = array_diff(scandir($dir), array('..', '.'));

$icons = array_values(array_filter($files, function ($file) {
    return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'png';
}));

$result = [];

foreach ($icons as $icon) {
    $name = pathinfo($icon, PATHINFO_FILENAME);
    $result[] = [
        'name' => $name,
        'filename' => $icon,
    ];
}

echo json_encode($result);
