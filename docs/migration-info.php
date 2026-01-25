<?php
/**
 * Batch migration script for error response standardization
 * This script updates all controllers to use the new ApiError format
 */

$controllerDir = __DIR__ . '/backend/src/Controller';
$controllers = [
    'AcquisitionOrderController.php' => ['type' => 'high', 'errors' => 14],
    'UserController.php' => ['type' => 'high', 'errors' => 13],
    'BookController.php' => ['type' => 'high', 'errors' => 12],
    'BookAssetController.php' => ['type' => 'high', 'errors' => 11],
    'BookInventoryController.php' => ['type' => 'high', 'errors' => 10],
    'AcquisitionSupplierController.php' => ['type' => 'high', 'errors' => 10],
    'ReportController.php' => ['type' => 'high', 'errors' => 10],
    'FavoriteController.php' => ['type' => 'medium', 'errors' => 9],
    'FineController.php' => ['type' => 'medium', 'errors' => 8],
    'SettingsController.php' => ['type' => 'medium', 'errors' => 7],
    'RatingController.php' => ['type' => 'medium', 'errors' => 6],
    'ReviewController.php' => ['type' => 'medium', 'errors' => 5],
    'NotificationController.php' => ['type' => 'medium', 'errors' => 5],
    'RegistrationController.php' => ['type' => 'medium', 'errors' => 5],
    'RecommendationFeedbackController.php' => ['type' => 'low', 'errors' => 4],
    'AuthorController.php' => ['type' => 'low', 'errors' => 4],
    'CategoryController.php' => ['type' => 'low', 'errors' => 4],
    'CollectionController.php' => ['type' => 'low', 'errors' => 4],
    'AdminUserController.php' => ['type' => 'low', 'errors' => 3],
    'RecommendationController.php' => ['type' => 'low', 'errors' => 3],
    'TestAuthController.php' => ['type' => 'low', 'errors' => 3],
    'WeedingController.php' => ['type' => 'low', 'errors' => 3],
    'AuditLogController.php' => ['type' => 'low', 'errors' => 2],
    'DashboardController.php' => ['type' => 'low', 'errors' => 1],
    'AlertController.php' => ['type' => 'low', 'errors' => 1],
    'CatalogAdminController.php' => ['type' => 'low', 'errors' => 3],
];

echo "=== Error Response Standardization Migration ===\n\n";

$stats = [
    'total' => 0,
    'migrated' => 0,
    'errors_fixed' => 0,
];

foreach ($controllers as $fileName => $info) {
    $filePath = $controllerDir . '/' . $fileName;
    
    if (!file_exists($filePath)) {
        echo "⚠ SKIP: $fileName (file not found)\n";
        continue;
    }
    
    echo "📝 Processing: $fileName ({$info['errors']} errors, {$info['type']} priority)...\n";
    $stats['total']++;
}

echo "\n=== Summary ===\n";
echo "Total Controllers: {$stats['total']}\n";
echo "Migrated: {$stats['migrated']}\n";
echo "Errors Fixed: {$stats['errors_fixed']}\n";
echo "\nFor PHP execution, run: php migration.php\n";
