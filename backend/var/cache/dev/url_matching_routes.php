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
        '/api/books/recommended' => [[['_route' => 'api_books_recommended', '_controller' => 'App\\Controller\\BookController::recommended'], null, ['GET' => 0], null, false, false, null]],
        '/api/admin/catalog/export' => [[['_route' => 'api_admin_catalog_export', '_controller' => 'App\\Controller\\CatalogAdminController::export'], null, ['GET' => 0], null, false, false, null]],
        '/api/admin/catalog/import' => [[['_route' => 'api_admin_catalog_import', '_controller' => 'App\\Controller\\CatalogAdminController::import'], null, ['POST' => 0], null, false, false, null]],
        '/api/admin/system/settings' => [
            [['_route' => 'api_admin_system_settings_list', '_controller' => 'App\\Controller\\Admin\\SystemConfigController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_system_settings_create', '_controller' => 'App\\Controller\\Admin\\SystemConfigController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/admin/system/roles' => [
            [['_route' => 'api_admin_system_roles_list', '_controller' => 'App\\Controller\\Admin\\RoleAdminController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_system_roles_create', '_controller' => 'App\\Controller\\Admin\\RoleAdminController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/admin/system/integrations' => [
            [['_route' => 'api_admin_system_integrations_list', '_controller' => 'App\\Controller\\Admin\\IntegrationAdminController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_system_integrations_create', '_controller' => 'App\\Controller\\Admin\\IntegrationAdminController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/admin/system/backups' => [
            [['_route' => 'api_admin_system_backups_list', '_controller' => 'App\\Controller\\Admin\\SecurityAdminController::listBackups'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_system_backups_create', '_controller' => 'App\\Controller\\Admin\\SecurityAdminController::createBackup'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/admin/system/logs' => [[['_route' => 'api_admin_system_logs_view', '_controller' => 'App\\Controller\\Admin\\SecurityAdminController::viewLogs'], null, ['GET' => 0], null, false, false, null]],
        '/api/admin/acquisitions/suppliers' => [
            [['_route' => 'api_acquisitions_suppliers_list', '_controller' => 'App\\Controller\\AcquisitionSupplierController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_acquisitions_suppliers_create', '_controller' => 'App\\Controller\\AcquisitionSupplierController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/admin/acquisitions/orders' => [
            [['_route' => 'api_acquisitions_orders_list', '_controller' => 'App\\Controller\\AcquisitionOrderController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_acquisitions_orders_create', '_controller' => 'App\\Controller\\AcquisitionOrderController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/admin/acquisitions/budgets' => [
            [['_route' => 'api_acquisitions_budgets_list', '_controller' => 'App\\Controller\\AcquisitionBudgetController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_acquisitions_budgets_create', '_controller' => 'App\\Controller\\AcquisitionBudgetController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/admin/acquisitions/weeding' => [
            [['_route' => 'api_acquisitions_weeding_list', '_controller' => 'App\\Controller\\WeedingController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_acquisitions_weeding_create', '_controller' => 'App\\Controller\\WeedingController::create'], null, ['POST' => 0], null, false, false, null],
        ],
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
        '/api/reports/circulation/popular' => [[['_route' => 'api_reports_popular_titles', '_controller' => 'App\\Controller\\ReportController::popularTitles'], null, ['GET' => 0], null, false, false, null]],
        '/api/reports/patrons/segments' => [[['_route' => 'api_reports_patron_segments', '_controller' => 'App\\Controller\\ReportController::patronSegments'], null, ['GET' => 0], null, false, false, null]],
        '/api/reports/financial' => [[['_route' => 'api_reports_financial', '_controller' => 'App\\Controller\\ReportController::financialSummary'], null, ['GET' => 0], null, false, false, null]],
        '/api/reports/inventory' => [[['_route' => 'api_reports_inventory', '_controller' => 'App\\Controller\\ReportController::inventoryOverview'], null, ['GET' => 0], null, false, false, null]],
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
                    .'|admin/(?'
                        .'|books/(?'
                            .'|([0-9]+)/copies(?'
                                .'|(*:215)'
                            .')'
                            .'|([0-9]+)/copies/([0-9]+)(?'
                                .'|(*:251)'
                            .')'
                            .'|([0-9]+)/assets(?'
                                .'|(*:278)'
                            .')'
                            .'|([0-9]+)/assets/([0-9]+)(?'
                                .'|(*:314)'
                            .')'
                        .')'
                        .'|system/(?'
                            .'|settings/([^/]++)(*:351)'
                            .'|roles/([^/]++)(?'
                                .'|(*:376)'
                                .'|/assign(*:391)'
                            .')'
                            .'|integrations/(?'
                                .'|([0-9]+)(*:424)'
                                .'|([0-9]+)/test(*:445)'
                            .')'
                        .')'
                        .'|acquisitions/(?'
                            .'|suppliers/([0-9]+)(?'
                                .'|(*:492)'
                            .')'
                            .'|orders/(?'
                                .'|([0-9]+)/status(*:526)'
                                .'|([0-9]+)/receive(*:550)'
                                .'|([0-9]+)/cancel(*:573)'
                            .')'
                            .'|budgets/(?'
                                .'|([0-9]+)(*:601)'
                                .'|([0-9]+)/expenses(*:626)'
                                .'|([0-9]+)/summary(*:650)'
                            .')'
                        .')'
                    .')'
                    .'|loans/(?'
                        .'|([0-9]+)(*:678)'
                        .'|user/([0-9]+)(*:699)'
                        .'|([0-9]+)/return(*:722)'
                        .'|([0-9]+)/extend(*:745)'
                        .'|([0-9]+)(*:761)'
                    .')'
                    .'|reservations/([0-9]+)(*:791)'
                    .'|f(?'
                        .'|ines/(?'
                            .'|([0-9]+)(*:819)'
                            .'|([0-9]+)/pay(*:839)'
                        .')'
                        .'|avorites/([0-9]+)(*:865)'
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
        215 => [
            [['_route' => 'api_admin_book_copies_list', '_controller' => 'App\\Controller\\BookInventoryController::list'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_book_copies_create', '_controller' => 'App\\Controller\\BookInventoryController::create'], ['id'], ['POST' => 0], null, false, false, null],
        ],
        251 => [
            [['_route' => 'api_admin_book_copy_update', '_controller' => 'App\\Controller\\BookInventoryController::update'], ['id', 'copyId'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_admin_book_copy_delete', '_controller' => 'App\\Controller\\BookInventoryController::delete'], ['id', 'copyId'], ['DELETE' => 0], null, false, true, null],
        ],
        278 => [
            [['_route' => 'api_admin_book_assets_list', '_controller' => 'App\\Controller\\BookAssetController::list'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_book_assets_upload', '_controller' => 'App\\Controller\\BookAssetController::upload'], ['id'], ['POST' => 0], null, false, false, null],
        ],
        314 => [
            [['_route' => 'api_admin_book_assets_download', '_controller' => 'App\\Controller\\BookAssetController::download'], ['id', 'assetId'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_admin_book_assets_delete', '_controller' => 'App\\Controller\\BookAssetController::delete'], ['id', 'assetId'], ['DELETE' => 0], null, false, true, null],
        ],
        351 => [[['_route' => 'api_admin_system_settings_update', '_controller' => 'App\\Controller\\Admin\\SystemConfigController::update'], ['key'], ['PUT' => 0], null, false, true, null]],
        376 => [[['_route' => 'api_admin_system_roles_update', '_controller' => 'App\\Controller\\Admin\\RoleAdminController::update'], ['roleKey'], ['PUT' => 0], null, false, true, null]],
        391 => [[['_route' => 'api_admin_system_roles_assign', '_controller' => 'App\\Controller\\Admin\\RoleAdminController::assign'], ['roleKey'], ['POST' => 0], null, false, false, null]],
        424 => [[['_route' => 'api_admin_system_integrations_update', '_controller' => 'App\\Controller\\Admin\\IntegrationAdminController::update'], ['id'], ['PUT' => 0], null, false, true, null]],
        445 => [[['_route' => 'api_admin_system_integrations_test', '_controller' => 'App\\Controller\\Admin\\IntegrationAdminController::testConnection'], ['id'], ['POST' => 0], null, false, false, null]],
        492 => [
            [['_route' => 'api_acquisitions_suppliers_update', '_controller' => 'App\\Controller\\AcquisitionSupplierController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_acquisitions_suppliers_deactivate', '_controller' => 'App\\Controller\\AcquisitionSupplierController::deactivate'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        526 => [[['_route' => 'api_acquisitions_orders_status', '_controller' => 'App\\Controller\\AcquisitionOrderController::updateStatus'], ['id'], ['PUT' => 0], null, false, false, null]],
        550 => [[['_route' => 'api_acquisitions_orders_receive', '_controller' => 'App\\Controller\\AcquisitionOrderController::receive'], ['id'], ['POST' => 0], null, false, false, null]],
        573 => [[['_route' => 'api_acquisitions_orders_cancel', '_controller' => 'App\\Controller\\AcquisitionOrderController::cancel'], ['id'], ['POST' => 0], null, false, false, null]],
        601 => [[['_route' => 'api_acquisitions_budgets_update', '_controller' => 'App\\Controller\\AcquisitionBudgetController::update'], ['id'], ['PUT' => 0], null, false, true, null]],
        626 => [[['_route' => 'api_acquisitions_budgets_expense', '_controller' => 'App\\Controller\\AcquisitionBudgetController::addExpense'], ['id'], ['POST' => 0], null, false, false, null]],
        650 => [[['_route' => 'api_acquisitions_budgets_summary', '_controller' => 'App\\Controller\\AcquisitionBudgetController::summary'], ['id'], ['GET' => 0], null, false, false, null]],
        678 => [[['_route' => 'api_loans_get', '_controller' => 'App\\Controller\\LoanController::getLoan'], ['id'], ['GET' => 0], null, false, true, null]],
        699 => [[['_route' => 'api_loans_by_user', '_controller' => 'App\\Controller\\LoanController::listByUser'], ['id'], ['GET' => 0], null, false, true, null]],
        722 => [[['_route' => 'api_loans_return', '_controller' => 'App\\Controller\\LoanController::returnLoan'], ['id'], ['PUT' => 0], null, false, false, null]],
        745 => [[['_route' => 'api_loans_extend', '_controller' => 'App\\Controller\\LoanController::extend'], ['id'], ['PUT' => 0], null, false, false, null]],
        761 => [[['_route' => 'api_loans_delete', '_controller' => 'App\\Controller\\LoanController::delete'], ['id'], ['DELETE' => 0], null, false, true, null]],
        791 => [[['_route' => 'api_reservations_cancel', '_controller' => 'App\\Controller\\ReservationController::cancel'], ['id'], ['DELETE' => 0], null, false, true, null]],
        819 => [[['_route' => 'api_fines_cancel', '_controller' => 'App\\Controller\\FineController::cancel'], ['id'], ['DELETE' => 0], null, false, true, null]],
        839 => [[['_route' => 'api_fines_pay', '_controller' => 'App\\Controller\\FineController::pay'], ['id'], ['POST' => 0], null, false, false, null]],
        865 => [
            [['_route' => 'api_favorites_remove', '_controller' => 'App\\Controller\\FavoriteController::remove'], ['bookId'], ['DELETE' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
