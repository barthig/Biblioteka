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
        '/api/auth/register' => [[['_route' => 'api_auth_register', '_controller' => 'App\\Controller\\AuthController::register'], null, ['POST' => 0], null, false, false, null]],
        '/api/books' => [
            [['_route' => 'api_books_list', '_controller' => 'App\\Controller\\BookController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_books_create', '_controller' => 'App\\Controller\\BookController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/books/filters' => [[['_route' => 'api_books_filters', '_controller' => 'App\\Controller\\BookController::filters'], null, ['GET' => 0], null, false, false, null]],
        '/api/admin/catalog/export' => [[['_route' => 'api_admin_catalog_export', '_controller' => 'App\\Controller\\CatalogAdminController::export'], null, ['GET' => 0], null, false, false, null]],
        '/api/admin/catalog/import' => [[['_route' => 'api_admin_catalog_import', '_controller' => 'App\\Controller\\CatalogAdminController::import'], null, ['POST' => 0], null, false, false, null]],
        '/api/me' => [
            [['_route' => 'api_account_me', '_controller' => 'App\\Controller\\AccountController::me'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_account_update', '_controller' => 'App\\Controller\\AccountController::update'], null, ['PUT' => 0], null, false, false, null],
        ],
        '/api/me/password' => [[['_route' => 'api_account_password', '_controller' => 'App\\Controller\\AccountController::changePassword'], null, ['PUT' => 0], null, false, false, null]],
        '/api/loans' => [
            [['_route' => 'api_loans_list', '_controller' => 'App\\Controller\\LoanController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_loans_create', '_controller' => 'App\\Controller\\LoanController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/reservations' => [
            [['_route' => 'api_reservations_list', '_controller' => 'App\\Controller\\ReservationController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_reservations_create', '_controller' => 'App\\Controller\\ReservationController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/orders' => [
            [['_route' => 'api_orders_list', '_controller' => 'App\\Controller\\OrderController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_orders_create', '_controller' => 'App\\Controller\\OrderController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/fines' => [
            [['_route' => 'api_fines_list', '_controller' => 'App\\Controller\\FineController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_fines_create', '_controller' => 'App\\Controller\\FineController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/favorites' => [
            [['_route' => 'api_favorites_list', '_controller' => 'App\\Controller\\FavoriteController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_favorites_add', '_controller' => 'App\\Controller\\FavoriteController::add'], null, ['POST' => 0], null, false, false, null],
        ],
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
                    .'|products/([0-9]+)(*:32)'
                    .'|users/(?'
                        .'|([0-9]+)(?'
                            .'|(*:59)'
                        .')'
                        .'|([0-9]+)/block(?'
                            .'|(*:84)'
                        .')'
                        .'|([0-9]+)/permissions(*:112)'
                    .')'
                    .'|books/(?'
                        .'|([0-9]+)(?'
                            .'|(*:141)'
                        .')'
                        .'|([0-9]+)/reviews(?'
                            .'|(*:169)'
                        .')'
                    .')'
                    .'|admin/books/(?'
                        .'|([0-9]+)/copies(?'
                            .'|(*:212)'
                        .')'
                        .'|([0-9]+)/copies/([0-9]+)(?'
                            .'|(*:248)'
                        .')'
                        .'|([0-9]+)/assets(?'
                            .'|(*:275)'
                        .')'
                        .'|([0-9]+)/assets/([0-9]+)(?'
                            .'|(*:311)'
                        .')'
                    .')'
                    .'|loans/(?'
                        .'|([0-9]+)(*:338)'
                        .'|user/([0-9]+)(*:359)'
                        .'|([0-9]+)/return(*:382)'
                        .'|([0-9]+)/extend(*:405)'
                        .'|([0-9]+)(*:421)'
                    .')'
                    .'|reservations/([0-9]+)(*:451)'
                    .'|orders/([0-9]+)(*:474)'
                    .'|f(?'
                        .'|ines/(?'
                            .'|([0-9]+)(*:502)'
                            .'|([0-9]+)/pay(*:522)'
                        .')'
                        .'|avorites/([0-9]+)(*:548)'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        32 => [[['_route' => 'api_products_get', '_controller' => 'App\\Controller\\ProductController::getProduct'], ['id'], ['GET' => 0], null, false, true, null]],
        59 => [
            [['_route' => 'api_users_get', '_controller' => 'App\\Controller\\UserController::getUserById'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_users_update', '_controller' => 'App\\Controller\\UserManagementController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_users_delete', '_controller' => 'App\\Controller\\UserManagementController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        84 => [
            [['_route' => 'api_users_block', '_controller' => 'App\\Controller\\UserManagementController::block'], ['id'], ['POST' => 0], null, false, false, null],
            [['_route' => 'api_users_unblock', '_controller' => 'App\\Controller\\UserManagementController::unblock'], ['id'], ['DELETE' => 0], null, false, false, null],
        ],
        112 => [[['_route' => 'api_users_permissions_update', '_controller' => 'App\\Controller\\UserManagementController::updatePermissions'], ['id'], ['PUT' => 0], null, false, false, null]],
        141 => [
            [['_route' => 'api_books_get', '_controller' => 'App\\Controller\\BookController::getBook'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_books_update', '_controller' => 'App\\Controller\\BookController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_books_delete', '_controller' => 'App\\Controller\\BookController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        169 => [
            [['_route' => 'api_reviews_list', '_controller' => 'App\\Controller\\ReviewController::list'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_reviews_upsert', '_controller' => 'App\\Controller\\ReviewController::upsert'], ['id'], ['POST' => 0], null, false, false, null],
            [['_route' => 'api_reviews_delete', '_controller' => 'App\\Controller\\ReviewController::delete'], ['id'], ['DELETE' => 0], null, false, false, null],
        ],
        212 => [
            [['_route' => 'api_admin_book_copies_list', '_controller' => 'App\\Controller\\BookInventoryController::list'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_book_copies_create', '_controller' => 'App\\Controller\\BookInventoryController::create'], ['id'], ['POST' => 0], null, false, false, null],
        ],
        248 => [
            [['_route' => 'api_admin_book_copy_update', '_controller' => 'App\\Controller\\BookInventoryController::update'], ['id', 'copyId'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_admin_book_copy_delete', '_controller' => 'App\\Controller\\BookInventoryController::delete'], ['id', 'copyId'], ['DELETE' => 0], null, false, true, null],
        ],
        275 => [
            [['_route' => 'api_admin_book_assets_list', '_controller' => 'App\\Controller\\BookAssetController::list'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_book_assets_upload', '_controller' => 'App\\Controller\\BookAssetController::upload'], ['id'], ['POST' => 0], null, false, false, null],
        ],
        311 => [
            [['_route' => 'api_admin_book_assets_download', '_controller' => 'App\\Controller\\BookAssetController::download'], ['id', 'assetId'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_admin_book_assets_delete', '_controller' => 'App\\Controller\\BookAssetController::delete'], ['id', 'assetId'], ['DELETE' => 0], null, false, true, null],
        ],
        338 => [[['_route' => 'api_loans_get', '_controller' => 'App\\Controller\\LoanController::getLoan'], ['id'], ['GET' => 0], null, false, true, null]],
        359 => [[['_route' => 'api_loans_by_user', '_controller' => 'App\\Controller\\LoanController::listByUser'], ['id'], ['GET' => 0], null, false, true, null]],
        382 => [[['_route' => 'api_loans_return', '_controller' => 'App\\Controller\\LoanController::returnLoan'], ['id'], ['PUT' => 0], null, false, false, null]],
        405 => [[['_route' => 'api_loans_extend', '_controller' => 'App\\Controller\\LoanController::extend'], ['id'], ['PUT' => 0], null, false, false, null]],
        421 => [[['_route' => 'api_loans_delete', '_controller' => 'App\\Controller\\LoanController::delete'], ['id'], ['DELETE' => 0], null, false, true, null]],
        451 => [[['_route' => 'api_reservations_cancel', '_controller' => 'App\\Controller\\ReservationController::cancel'], ['id'], ['DELETE' => 0], null, false, true, null]],
        474 => [[['_route' => 'api_orders_cancel', '_controller' => 'App\\Controller\\OrderController::cancel'], ['id'], ['DELETE' => 0], null, false, true, null]],
        502 => [[['_route' => 'api_fines_cancel', '_controller' => 'App\\Controller\\FineController::cancel'], ['id'], ['DELETE' => 0], null, false, true, null]],
        522 => [[['_route' => 'api_fines_pay', '_controller' => 'App\\Controller\\FineController::pay'], ['id'], ['POST' => 0], null, false, false, null]],
        548 => [
            [['_route' => 'api_favorites_remove', '_controller' => 'App\\Controller\\FavoriteController::remove'], ['bookId'], ['DELETE' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
