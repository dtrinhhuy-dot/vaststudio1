<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (preg_match('#^/MVC(?:/|$)#i', $requestPath)) {
    http_response_code(404);
    exit;
}

if (PHP_SAPI === 'cli-server' && $requestPath !== '/') {
    $routedPaths = '#^/(?:index|products|product-detail|order-tracking|about|contact|auth|auth-api|checkout|admin|home|shop)\.php$#i';
    if (!preg_match($routedPaths, $requestPath)) {
        $documentRoot = realpath(dirname(__DIR__));
        $requestedFile = realpath(
            $documentRoot . DIRECTORY_SEPARATOR .
            ltrim(str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($requestPath)), DIRECTORY_SEPARATOR)
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

$route = $requestPath === '/'
    ? 'index'
    : pathinfo(basename($requestPath), PATHINFO_FILENAME);
if ($route === '' || $route === 'router') {
    $route = 'index';
}

require_once __DIR__ . '/controllers/FrontController.php';
(new FrontController())->dispatch($route);
