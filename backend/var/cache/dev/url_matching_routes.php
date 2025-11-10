<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/health' => [[['_route' => 'health_check', '_controller' => 'App\\Controller\\HealthController::health'], null, ['GET' => 0], null, false, false, null]],
        '/api/auth/login' => [[['_route' => 'api_auth_login', '_controller' => 'App\\Controller\\AuthController::login'], null, ['POST' => 0], null, false, false, null]],
        '/api/dashboard' => [[['_route' => 'api_dashboard_overview', '_controller' => 'App\\Controller\\DashboardController::overview'], null, ['GET' => 0], null, false, false, null]],
        '/api/products' => [[['_route' => 'api_products_list', '_controller' => 'App\\Controller\\ProductController::list'], null, ['GET' => 0], null, false, false, null]],
        '/api/users' => [
            [['_route' => 'api_users_list', '_controller' => 'App\\Controller\\UserController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_users_create', '_controller' => 'App\\Controller\\UserManagementController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/books' => [
            [['_route' => 'api_books_list', '_controller' => 'App\\Controller\\BookController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_books_create', '_controller' => 'App\\Controller\\BookController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/books/filters' => [[['_route' => 'api_books_filters', '_controller' => 'App\\Controller\\BookController::filters'], null, ['GET' => 0], null, false, false, null]],
        '/api/loans' => [
            [['_route' => 'api_loans_list', '_controller' => 'App\\Controller\\LoanController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_loans_create', '_controller' => 'App\\Controller\\LoanController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/reservations' => [
            [['_route' => 'api_reservations_list', '_controller' => 'App\\Controller\\ReservationController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_reservations_create', '_controller' => 'App\\Controller\\ReservationController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/fines' => [[['_route' => 'api_fines_list', '_controller' => 'App\\Controller\\FineController::list'], null, ['GET' => 0], null, false, false, null]],
        '/api/notifications' => [[['_route' => 'api_notifications_list', '_controller' => 'App\\Controller\\NotificationController::list'], null, ['GET' => 0], null, false, false, null]],
        '/api/notifications/test' => [[['_route' => 'api_notifications_test', '_controller' => 'App\\Controller\\NotificationController::triggerTest'], null, ['POST' => 0], null, false, false, null]],
        '/api/reports/usage' => [[['_route' => 'api_reports_usage', '_controller' => 'App\\Controller\\ReportController::usage'], null, ['GET' => 0], null, false, false, null]],
        '/api/reports/export' => [[['_route' => 'api_reports_export', '_controller' => 'App\\Controller\\ReportController::export'], null, ['GET' => 0], null, false, false, null]],
        '/api/settings' => [
            [['_route' => 'api_settings_get', '_controller' => 'App\\Controller\\SettingsController::getSettings'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_settings_update', '_controller' => 'App\\Controller\\SettingsController::updateSettings'], null, ['PATCH' => 0], null, false, false, null],
        ],
        '/api/docs.json' => [[['_route' => 'api_docs_json', '_format' => 'json', '_controller' => 'nelmio_api_doc.controller.swagger'], null, null, null, false, false, null]],
        '/api/docs' => [[['_route' => 'api_docs_ui', '_controller' => 'nelmio_api_doc.controller.swagger_ui'], null, null, null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/api/(?'
                    .'|products/(\\\\d+)(*:30)'
                    .'|users/(\\\\d+)(?'
                        .'|(*:52)'
                    .')'
                    .'|books/(\\\\d+)(?'
                        .'|(*:75)'
                    .')'
                    .'|loans/(?'
                        .'|(\\\\d+)(*:98)'
                        .'|user/(\\\\d+)(*:116)'
                        .'|(\\\\d+)/return(*:137)'
                        .'|(\\\\d+)(*:151)'
                    .')'
                    .'|reservations/(\\\\d+)(*:179)'
                    .'|fines/(\\\\d+)/pay(*:203)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        30 => [[['_route' => 'api_products_get', '_controller' => 'App\\Controller\\ProductController::getProduct'], ['id'], ['GET' => 0], null, false, true, null]],
        52 => [
            [['_route' => 'api_users_get', '_controller' => 'App\\Controller\\UserController::getUserById'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_users_update', '_controller' => 'App\\Controller\\UserManagementController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_users_delete', '_controller' => 'App\\Controller\\UserManagementController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        75 => [
            [['_route' => 'api_books_get', '_controller' => 'App\\Controller\\BookController::getBook'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_books_update', '_controller' => 'App\\Controller\\BookController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_books_delete', '_controller' => 'App\\Controller\\BookController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        98 => [[['_route' => 'api_loans_get', '_controller' => 'App\\Controller\\LoanController::getLoan'], ['id'], ['GET' => 0], null, false, true, null]],
        116 => [[['_route' => 'api_loans_by_user', '_controller' => 'App\\Controller\\LoanController::listByUser'], ['id'], ['GET' => 0], null, false, true, null]],
        137 => [[['_route' => 'api_loans_return', '_controller' => 'App\\Controller\\LoanController::returnLoan'], ['id'], ['PUT' => 0], null, false, false, null]],
        151 => [[['_route' => 'api_loans_delete', '_controller' => 'App\\Controller\\LoanController::delete'], ['id'], ['DELETE' => 0], null, false, true, null]],
        179 => [[['_route' => 'api_reservations_cancel', '_controller' => 'App\\Controller\\ReservationController::cancel'], ['id'], ['DELETE' => 0], null, false, true, null]],
        203 => [
            [['_route' => 'api_fines_pay', '_controller' => 'App\\Controller\\FineController::pay'], ['id'], ['POST' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
