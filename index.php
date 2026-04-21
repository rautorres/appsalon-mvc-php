<?php

// 1. Obtener la ruta de la petición
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 2. Si el archivo existe físicamente en /public (CSS, JS, Imágenes), servirlo directamente
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// 3. Para todo lo demás, cargar el index real que está en public
require_once __DIR__ . '/public/index.php';