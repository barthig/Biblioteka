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
        '/api/auth/profile' => [[['_route' => 'api_auth_profile', '_controller' => 'App\\Controller\\AuthController::profile'], null, ['GET' => 0], null, false, false, null]],
        '/api/dashboard' => [[['_route' => 'api_dashboard_overview', '_controller' => 'App\\Controller\\DashboardController::overview'], null, ['GET' => 0], null, false, false, null]],
        '/api/users' => [
            [['_route' => 'api_users_list', '_controller' => 'App\\Controller\\UserController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_users_create', '_controller' => 'App\\Controller\\UserManagementController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/auth/register' => [[['_route' => 'api_auth_register', '_controller' => 'App\\Controller\\RegistrationController::register'], null, ['POST' => 0], null, false, false, null]],
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
                    .'|users/(?'
                        .'|([0-9]+)(?'
                            .'|(*:35)'
                        .')'
                        .'|([0-9]+)/block(?'
                            .'|(*:60)'
                        .')'
                        .'|([0-9]+)/permissions(*:88)'
                    .')'
                    .'|a(?'
                        .'|uth/verify/([^/]++)(*:119)'
                        .'|dmin/(?'
                            .'|books/(?'
                                .'|([0-9]+)/copies(?'
                                    .'|(*:162)'
                                .')'
                                .'|([0-9]+)/copies/([0-9]+)(?'
                                    .'|(*:198)'
                                .')'
                                .'|([0-9]+)/assets(?'
                                    .'|(*:225)'
                                .')'
                                .'|([0-9]+)/assets/([0-9]+)(?'
                                    .'|(*:261)'
                                .')'
                            .')'
                            .'|system/(?'
                                .'|settings/([^/]++)(*:298)'
                                .'|roles/([^/]++)(?'
                                    .'|(*:323)'
                                    .'|/assign(*:338)'
                                .')'
                                .'|integrations/(?'
                                    .'|([0-9]+)(*:371)'
                                    .'|([0-9]+)/test(*:392)'
                                .')'
                            .')'
                            .'|acquisitions/(?'
                                .'|suppliers/([0-9]+)(?'
                                    .'|(*:439)'
                                .')'
                                .'|orders/(?'
                                    .'|([0-9]+)/status(*:473)'
                                    .'|([0-9]+)/receive(*:497)'
                                    .'|([0-9]+)/cancel(*:520)'
                                .')'
                                .'|budgets/(?'
                                    .'|([0-9]+)(*:548)'
                                    .'|([0-9]+)/expenses(*:573)'
                                    .'|([0-9]+)/summary(*:597)'
                                .')'
                            .')'
                        .')'
                    .')'
                    .'|books/(?'
                        .'|([0-9]+)(?'
                            .'|(*:629)'
                        .')'
                        .'|([0-9]+)/reviews(?'
                            .'|(*:657)'
                        .')'
                    .')'
                    .'|loans/(?'
                        .'|([0-9]+)(*:684)'
                        .'|user/([0-9]+)(*:705)'
                        .'|([0-9]+)/return(*:728)'
                        .'|([0-9]+)/extend(*:751)'
                        .'|([0-9]+)(*:767)'
                    .')'
                    .'|reservations/([0-9]+)(*:797)'
                    .'|f(?'
                        .'|ines/(?'
                            .'|([0-9]+)(*:825)'
                            .'|([0-9]+)/pay(*:845)'
                        .')'
                        .'|avorites/([0-9]+)(*:871)'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [
            [['_route' => 'api_users_get', '_controller' => 'App\\Controller\\UserController::getUserById'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_users_update', '_controller' => 'App\\Controller\\UserManagementController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_users_delete', '_controller' => 'App\\Controller\\UserManagementController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        60 => [
            [['_route' => 'api_users_block', '_controller' => 'App\\Controller\\UserManagementController::block'], ['id'], ['POST' => 0], null, false, false, null],
            [['_route' => 'api_users_unblock', '_controller' => 'App\\Controller\\UserManagementController::unblock'], ['id'], ['DELETE' => 0], null, false, false, null],
        ],
        88 => [[['_route' => 'api_users_permissions_update', '_controller' => 'App\\Controller\\UserManagementController::updatePermissions'], ['id'], ['PUT' => 0], null, false, false, null]],
        119 => [[['_route' => 'api_auth_verify', '_controller' => 'App\\Controller\\RegistrationController::verify'], ['token'], ['GET' => 0], null, false, true, null]],
        162 => [
            [['_route' => 'api_admin_book_copies_list', '_controller' => 'App\\Controller\\BookInventoryController::list'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_book_copies_create', '_controller' => 'App\\Controller\\BookInventoryController::create'], ['id'], ['POST' => 0], null, false, false, null],
        ],
        198 => [
            [['_route' => 'api_admin_book_copy_update', '_controller' => 'App\\Controller\\BookInventoryController::update'], ['id', 'copyId'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_admin_book_copy_delete', '_controller' => 'App\\Controller\\BookInventoryController::delete'], ['id', 'copyId'], ['DELETE' => 0], null, false, true, null],
        ],
        225 => [
            [['_route' => 'api_admin_book_assets_list', '_controller' => 'App\\Controller\\BookAssetController::list'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_admin_book_assets_upload', '_controller' => 'App\\Controller\\BookAssetController::upload'], ['id'], ['POST' => 0], null, false, false, null],
        ],
        261 => [
            [['_route' => 'api_admin_book_assets_download', '_controller' => 'App\\Controller\\BookAssetController::download'], ['id', 'assetId'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_admin_book_assets_delete', '_controller' => 'App\\Controller\\BookAssetController::delete'], ['id', 'assetId'], ['DELETE' => 0], null, false, true, null],
        ],
        298 => [[['_route' => 'api_admin_system_settings_update', '_controller' => 'App\\Controller\\Admin\\SystemConfigController::update'], ['key'], ['PUT' => 0], null, false, true, null]],
        323 => [[['_route' => 'api_admin_system_roles_update', '_controller' => 'App\\Controller\\Admin\\RoleAdminController::update'], ['roleKey'], ['PUT' => 0], null, false, true, null]],
        338 => [[['_route' => 'api_admin_system_roles_assign', '_controller' => 'App\\Controller\\Admin\\RoleAdminController::assign'], ['roleKey'], ['POST' => 0], null, false, false, null]],
        371 => [[['_route' => 'api_admin_system_integrations_update', '_controller' => 'App\\Controller\\Admin\\IntegrationAdminController::update'], ['id'], ['PUT' => 0], null, false, true, null]],
        392 => [[['_route' => 'api_admin_system_integrations_test', '_controller' => 'App\\Controller\\Admin\\IntegrationAdminController::testConnection'], ['id'], ['POST' => 0], null, false, false, null]],
        439 => [
            [['_route' => 'api_acquisitions_suppliers_update', '_controller' => 'App\\Controller\\AcquisitionSupplierController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_acquisitions_suppliers_deactivate', '_controller' => 'App\\Controller\\AcquisitionSupplierController::deactivate'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        473 => [[['_route' => 'api_acquisitions_orders_status', '_controller' => 'App\\Controller\\AcquisitionOrderController::updateStatus'], ['id'], ['PUT' => 0], null, false, false, null]],
        497 => [[['_route' => 'api_acquisitions_orders_receive', '_controller' => 'App\\Controller\\AcquisitionOrderController::receive'], ['id'], ['POST' => 0], null, false, false, null]],
        520 => [[['_route' => 'api_acquisitions_orders_cancel', '_controller' => 'App\\Controller\\AcquisitionOrderController::cancel'], ['id'], ['POST' => 0], null, false, false, null]],
        548 => [[['_route' => 'api_acquisitions_budgets_update', '_controller' => 'App\\Controller\\AcquisitionBudgetController::update'], ['id'], ['PUT' => 0], null, false, true, null]],
        573 => [[['_route' => 'api_acquisitions_budgets_expense', '_controller' => 'App\\Controller\\AcquisitionBudgetController::addExpense'], ['id'], ['POST' => 0], null, false, false, null]],
        597 => [[['_route' => 'api_acquisitions_budgets_summary', '_controller' => 'App\\Controller\\AcquisitionBudgetController::summary'], ['id'], ['GET' => 0], null, false, false, null]],
        629 => [
            [['_route' => 'api_books_get', '_controller' => 'App\\Controller\\BookController::getBook'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_books_update', '_controller' => 'App\\Controller\\BookController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'api_books_delete', '_controller' => 'App\\Controller\\BookController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        657 => [
            [['_route' => 'api_reviews_list', '_controller' => 'App\\Controller\\ReviewController::list'], ['id'], ['GET' => 0], null, false, false, null],
            [['_route' => 'api_reviews_upsert', '_controller' => 'App\\Controller\\ReviewController::upsert'], ['id'], ['POST' => 0], null, false, false, null],
            [['_route' => 'api_reviews_delete', '_controller' => 'App\\Controller\\ReviewController::delete'], ['id'], ['DELETE' => 0], null, false, false, null],
        ],
        684 => [[['_route' => 'api_loans_get', '_controller' => 'App\\Controller\\LoanController::getLoan'], ['id'], ['GET' => 0], null, false, true, null]],
        705 => [[['_route' => 'api_loans_by_user', '_controller' => 'App\\Controller\\LoanController::listByUser'], ['id'], ['GET' => 0], null, false, true, null]],
        728 => [[['_route' => 'api_loans_return', '_controller' => 'App\\Controller\\LoanController::returnLoan'], ['id'], ['PUT' => 0], null, false, false, null]],
        751 => [[['_route' => 'api_loans_extend', '_controller' => 'App\\Controller\\LoanController::extend'], ['id'], ['PUT' => 0], null, false, false, null]],
        767 => [[['_route' => 'api_loans_delete', '_controller' => 'App\\Controller\\LoanController::delete'], ['id'], ['DELETE' => 0], null, false, true, null]],
        797 => [[['_route' => 'api_reservations_cancel', '_controller' => 'App\\Controller\\ReservationController::cancel'], ['id'], ['DELETE' => 0], null, false, true, null]],
        825 => [[['_route' => 'api_fines_cancel', '_controller' => 'App\\Controller\\FineController::cancel'], ['id'], ['DELETE' => 0], null, false, true, null]],
        845 => [[['_route' => 'api_fines_pay', '_controller' => 'App\\Controller\\FineController::pay'], ['id'], ['POST' => 0], null, false, false, null]],
        871 => [
            [['_route' => 'api_favorites_remove', '_controller' => 'App\\Controller\\FavoriteController::remove'], ['bookId'], ['DELETE' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
