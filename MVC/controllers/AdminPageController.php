<?php

require_once __DIR__ . '/AdminController.php';

class AdminPageController
{
    public function render(): void
    {
        require_once __DIR__ . '/../../includes/config.php';

        try {
            $controller = new AdminController(new AdminStoreModel(getDatabaseConnection()));
            $viewData = $controller->handleRequest();
            extract($viewData, EXTR_SKIP);
            require __DIR__ . '/../views/' . ($loginRequired ? 'admin-login.php' : 'admin.php');
        } catch (PDOException $exception) {
            error_log('Admin database error: ' . $exception->getMessage());
            http_response_code(503);
            require __DIR__ . '/../views/admin-error.php';
        }
    }
}
