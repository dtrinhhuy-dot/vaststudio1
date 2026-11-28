<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = '';

if (preg_match('#^(.*)/MVC/router\.php$#i', $scriptName, $matches)) {
    $basePath = $matches[1];
}

$relativePath = $requestPath;
if ($basePath !== '' && strncasecmp($relativePath, $basePath, strlen($basePath)) === 0) {
    $afterBase = substr($relativePath, strlen($basePath));
    if ($afterBase === '' || $afterBase[0] === '/') {
        $relativePath = $afterBase === '' ? '/' : $afterBase;
    }
}

if (preg_match('#^/MVC(?:/|$)#i', $relativePath)) {
    http_response_code(404);
    exit;
}

if (PHP_SAPI === 'cli-server' && $relativePath !== '/') {
    $routedPaths = '#^/(?:index|products|product-detail|order-tracking|about|contact|auth|auth-api|login|register|checkout|admin|api|sepay-webhook|home|shop)\.php$#i';
    if (!preg_match($routedPaths, $relativePath)) {
        $documentRoot = realpath(dirname(__DIR__));
        $requestedFile = realpath(
            $documentRoot . DIRECTORY_SEPARATOR .
            ltrim(str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($relativePath)), DIRECTORY_SEPARATOR)
        );

        if (
            $requestedFile !== false &&
            str_starts_with($requestedFile, $documentRoot . DIRECTORY_SEPARATOR) &&
            is_file($requestedFile)
        ) {
            return false;
        }
    }
}

$route = $relativePath === '/'
    ? 'index'
    : pathinfo(basename($relativePath), PATHINFO_FILENAME);
if ($route === '' || $route === 'router') {
    $route = 'index';
}

require_once __DIR__ . '/controllers/FrontController.php';
(new FrontController())->dispatch($route);
