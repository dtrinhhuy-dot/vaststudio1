<?php

class FrontController
{
    private const STOREFRONT_VIEWS = [
        'index' => 'index.php',
        'products' => 'products.php',
        'product-detail' => 'product-detail.php',
        'order-tracking' => 'order-tracking.php',
        'about' => 'about.php',
        'contact' => 'contact.php',
        'auth' => 'auth.php',
        'checkout' => 'checkout.php',
    ];

    public function dispatch(string $route): void
    {
        $route = strtolower($route);

        if ($route === 'home' || $route === 'shop') {
            $destination = $route === 'home' ? 'index.php' : 'products.php';
            header('Location: ' . $destination, true, 302);
            return;
        }

        if ($route === 'login' || $route === 'register') {
            $destination = 'auth.php?tab=' . $route;
            $redirect = (string)($_GET['redirect'] ?? '');
            if ($redirect !== '') {
                $destination .= '&redirect=' . rawurlencode($redirect);
            }
            header('Location: ' . $destination, true, 302);
            return;
        }

        if ($route === 'api') {
            require_once __DIR__ . '/ApiController.php';
            (new ApiController())->handle();
            return;
        }

        if ($route === 'auth') {
            require_once __DIR__ . '/AuthPageController.php';
            (new AuthPageController())->render();
            return;
        }

        if ($route === 'admin') {
            require_once __DIR__ . '/AdminPageController.php';
            (new AdminPageController())->render();
            return;
        }

        if (!isset(self::STOREFRONT_VIEWS[$route])) {
            http_response_code(404);
            echo 'Không tìm thấy trang.';
            return;
        }

        require __DIR__ . '/../views/storefront/' . self::STOREFRONT_VIEWS[$route];
    }
}
