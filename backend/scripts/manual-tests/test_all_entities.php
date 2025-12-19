<?php
// Comprehensive SQL/Entity test - tests all major database tables

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;

// Load environment variables from .env file manually
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Bootstrap Symfony Kernel
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $em */
$em = $container->get('doctrine.orm.entity_manager');

echo "\n=== COMPREHENSIVE DATABASE TEST ===\n\n";

$results = [];
$errors = [];

// Test 1: Users (app_user)
echo "1. Testing app_user table...\n";
try {
    $conn = $em->getConnection();
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM app_user');
    $count = $stmt->fetchOne();
    echo "   ✓ app_user: {$count} rows found\n";
    $results['app_user'] = "OK ({$count} rows)";
    
    // Get sample user
    $stmt = $conn->executeQuery('SELECT id, email, roles FROM app_user LIMIT 1');
    $user = $stmt->fetchAssociative();
    if ($user) {
        echo "   Sample: ID={$user['id']}, Email={$user['email']}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['app_user'] = $e->getMessage();
}

// Test 2: Authors
echo "\n2. Testing author table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM author');
    $count = $stmt->fetchOne();
    echo "   ✓ author: {$count} rows found\n";
    $results['author'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('SELECT id, name FROM author LIMIT 3');
    $authors = $stmt->fetchAllAssociative();
    foreach ($authors as $author) {
        echo "   - {$author['name']} (ID: {$author['id']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['author'] = $e->getMessage();
}

// Test 3: Categories
echo "\n3. Testing category table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM category');
    $count = $stmt->fetchOne();
    echo "   ✓ category: {$count} rows found\n";
    $results['category'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('SELECT id, name FROM category LIMIT 3');
    $categories = $stmt->fetchAllAssociative();
    foreach ($categories as $cat) {
        echo "   - {$cat['name']} (ID: {$cat['id']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['category'] = $e->getMessage();
}

// Test 4: Books
echo "\n4. Testing book table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM book');
    $count = $stmt->fetchOne();
    echo "   ✓ book: {$count} rows found\n";
    $results['book'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('SELECT b.id, b.title, a.name as author FROM book b LEFT JOIN author a ON b.author_id = a.id LIMIT 3');
    $books = $stmt->fetchAllAssociative();
    foreach ($books as $book) {
        echo "   - {$book['title']} by {$book['author']} (ID: {$book['id']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['book'] = $e->getMessage();
}

// Test 5: Book Categories (junction table)
echo "\n5. Testing book_category junction table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM book_category');
    $count = $stmt->fetchOne();
    echo "   ✓ book_category: {$count} rows found\n";
    $results['book_category'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT b.title, c.name as category 
        FROM book_category bc
        JOIN book b ON bc.book_id = b.id
        JOIN category c ON bc.category_id = c.id
        LIMIT 3
    ');
    $bookCats = $stmt->fetchAllAssociative();
    foreach ($bookCats as $bc) {
        echo "   - {$bc['title']} -> {$bc['category']}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['book_category'] = $e->getMessage();
}

// Test 6: Book Copies
echo "\n6. Testing book_copy table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM book_copy');
    $count = $stmt->fetchOne();
    echo "   ✓ book_copy: {$count} rows found\n";
    $results['book_copy'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT bc.id, bc.inventory_code, bc.status, b.title 
        FROM book_copy bc
        JOIN book b ON bc.book_id = b.id
        LIMIT 3
    ');
    $copies = $stmt->fetchAllAssociative();
    foreach ($copies as $copy) {
        echo "   - {$copy['inventory_code']}: {$copy['title']} (Status: {$copy['status']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['book_copy'] = $e->getMessage();
}

// Test 7: Loans
echo "\n7. Testing loan table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM loan');
    $count = $stmt->fetchOne();
    echo "   ✓ loan: {$count} rows found\n";
    $results['loan'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT l.id, b.title, u.email, l.borrowed_at, l.due_at, l.returned_at
        FROM loan l
        JOIN book b ON l.book_id = b.id
        JOIN app_user u ON l.user_id = u.id
        LIMIT 3
    ');
    $loans = $stmt->fetchAllAssociative();
    foreach ($loans as $loan) {
        $status = $loan['returned_at'] ? 'RETURNED' : 'ACTIVE';
        echo "   - {$loan['email']}: {$loan['title']} [{$status}]\n";
        echo "     Borrowed: {$loan['borrowed_at']}, Due: {$loan['due_at']}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['loan'] = $e->getMessage();
}

// Test 8: Reservations
echo "\n8. Testing reservation table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM reservation');
    $count = $stmt->fetchOne();
    echo "   ✓ reservation: {$count} rows found\n";
    $results['reservation'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT r.id, b.title, u.email, r.status, r.reserved_at
        FROM reservation r
        JOIN book b ON r.book_id = b.id
        JOIN app_user u ON r.user_id = u.id
        LIMIT 3
    ');
    $reservations = $stmt->fetchAllAssociative();
    foreach ($reservations as $res) {
        echo "   - {$res['email']}: {$res['title']} (Status: {$res['status']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['reservation'] = $e->getMessage();
}

// Test 9: Favorites
echo "\n9. Testing favorite table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM favorite');
    $count = $stmt->fetchOne();
    echo "   ✓ favorite: {$count} rows found\n";
    $results['favorite'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT f.id, b.title, u.email, f.created_at
        FROM favorite f
        JOIN book b ON f.book_id = b.id
        JOIN app_user u ON f.user_id = u.id
        LIMIT 3
    ');
    $favorites = $stmt->fetchAllAssociative();
    foreach ($favorites as $fav) {
        echo "   - {$fav['email']}: {$fav['title']}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['favorite'] = $e->getMessage();
}

// Test 10: Reviews
echo "\n10. Testing review table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM review');
    $count = $stmt->fetchOne();
    echo "   ✓ review: {$count} rows found\n";
    $results['review'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT r.id, b.title, u.email, r.rating, r.comment
        FROM review r
        JOIN book b ON r.book_id = b.id
        JOIN app_user u ON r.user_id = u.id
        LIMIT 3
    ');
    $reviews = $stmt->fetchAllAssociative();
    foreach ($reviews as $rev) {
        $comment = substr($rev['comment'] ?? '', 0, 50);
        echo "   - {$rev['email']}: {$rev['title']} ({$rev['rating']}/5)\n";
        if ($comment) echo "     \"{$comment}...\"\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['review'] = $e->getMessage();
}

// Test 11: Fines
echo "\n11. Testing fine table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM fine');
    $count = $stmt->fetchOne();
    echo "   ✓ fine: {$count} rows found\n";
    $results['fine'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT f.id, f.amount, f.currency, f.reason, f.paid_at, u.email
        FROM fine f
        JOIN loan l ON f.loan_id = l.id
        JOIN app_user u ON l.user_id = u.id
        LIMIT 3
    ');
    $fines = $stmt->fetchAllAssociative();
    foreach ($fines as $fine) {
        $status = $fine['paid_at'] ? 'PAID' : 'UNPAID';
        echo "   - {$fine['email']}: {$fine['amount']} {$fine['currency']} [{$status}]\n";
        echo "     Reason: {$fine['reason']}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['fine'] = $e->getMessage();
}

// Test 12: Digital Assets
echo "\n12. Testing book_digital_asset table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM book_digital_asset');
    $count = $stmt->fetchOne();
    echo "   ✓ book_digital_asset: {$count} rows found\n";
    $results['book_digital_asset'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT bda.id, b.title, bda.original_filename, bda.size, bda.mime_type
        FROM book_digital_asset bda
        JOIN book b ON bda.book_id = b.id
        LIMIT 3
    ');
    $assets = $stmt->fetchAllAssociative();
    foreach ($assets as $asset) {
        $size = number_format($asset['size'] / 1024, 2);
        echo "   - {$asset['title']}: {$asset['original_filename']} ({$size} KB, {$asset['mime_type']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['book_digital_asset'] = $e->getMessage();
}

// Test 13: Suppliers
echo "\n13. Testing supplier table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM supplier');
    $count = $stmt->fetchOne();
    echo "   ✓ supplier: {$count} rows found\n";
    $results['supplier'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('SELECT id, name, contact_email, active FROM supplier LIMIT 3');
    $suppliers = $stmt->fetchAllAssociative();
    foreach ($suppliers as $supplier) {
        $active = $supplier['active'] ? 'ACTIVE' : 'INACTIVE';
        echo "   - {$supplier['name']} ({$supplier['contact_email']}) [{$active}]\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['supplier'] = $e->getMessage();
}

// Test 14: Acquisition Budget
echo "\n14. Testing acquisition_budget table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM acquisition_budget');
    $count = $stmt->fetchOne();
    echo "   ✓ acquisition_budget: {$count} rows found\n";
    $results['acquisition_budget'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('SELECT id, name, fiscal_year, allocated_amount, spent_amount, currency FROM acquisition_budget LIMIT 3');
    $budgets = $stmt->fetchAllAssociative();
    foreach ($budgets as $budget) {
        echo "   - {$budget['name']} ({$budget['fiscal_year']}): {$budget['allocated_amount']} {$budget['currency']} (Spent: {$budget['spent_amount']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['acquisition_budget'] = $e->getMessage();
}

// Test 15: Acquisition Orders
echo "\n15. Testing acquisition_order table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM acquisition_order');
    $count = $stmt->fetchOne();
    echo "   ✓ acquisition_order: {$count} rows found\n";
    $results['acquisition_order'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT ao.id, ao.title, ao.status, ao.total_amount, ao.currency, s.name as supplier
        FROM acquisition_order ao
        JOIN supplier s ON ao.supplier_id = s.id
        LIMIT 3
    ');
    $orders = $stmt->fetchAllAssociative();
    foreach ($orders as $order) {
        echo "   - {$order['title']} from {$order['supplier']}: {$order['total_amount']} {$order['currency']} (Status: {$order['status']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['acquisition_order'] = $e->getMessage();
}

// Test 16: System Settings
echo "\n16. Testing system_setting table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM system_setting');
    $count = $stmt->fetchOne();
    echo "   ✓ system_setting: {$count} rows found\n";
    $results['system_setting'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('SELECT id, setting_key, setting_value, value_type FROM system_setting LIMIT 5');
    $settings = $stmt->fetchAllAssociative();
    foreach ($settings as $setting) {
        $value = substr($setting['setting_value'], 0, 50);
        echo "   - {$setting['setting_key']} = {$value} ({$setting['value_type']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['system_setting'] = $e->getMessage();
}

// Test 17: Staff Roles
echo "\n17. Testing staff_role table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM staff_role');
    $count = $stmt->fetchOne();
    echo "   ✓ staff_role: {$count} rows found\n";
    $results['staff_role'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('SELECT id, name, role_key FROM staff_role LIMIT 3');
    $roles = $stmt->fetchAllAssociative();
    foreach ($roles as $role) {
        echo "   - {$role['name']} (key: {$role['role_key']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['staff_role'] = $e->getMessage();
}

// Test 18: Notification Log
echo "\n18. Testing notification_log table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM notification_log');
    $count = $stmt->fetchOne();
    echo "   ✓ notification_log: {$count} rows found\n";
    $results['notification_log'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT nl.id, nl.type, nl.channel, nl.status, u.email
        FROM notification_log nl
        JOIN app_user u ON nl.user_id = u.id
        LIMIT 3
    ');
    $notifications = $stmt->fetchAllAssociative();
    foreach ($notifications as $notif) {
        echo "   - {$notif['email']}: {$notif['type']} via {$notif['channel']} (Status: {$notif['status']})\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['notification_log'] = $e->getMessage();
}

// Test 19: Audit Logs
echo "\n19. Testing audit_logs table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM audit_logs');
    $count = $stmt->fetchOne();
    echo "   ✓ audit_logs: {$count} rows found\n";
    $results['audit_logs'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT al.id, al.entity_type, al.action, al.created_at, u.email
        FROM audit_logs al
        LEFT JOIN app_user u ON al.user_id = u.id
        LIMIT 3
    ');
    $audits = $stmt->fetchAllAssociative();
    foreach ($audits as $audit) {
        $user = $audit['email'] ?? 'SYSTEM';
        echo "   - {$user}: {$audit['action']} on {$audit['entity_type']} at {$audit['created_at']}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['audit_logs'] = $e->getMessage();
}

// Test 20: Registration Tokens
echo "\n20. Testing registration_token table...\n";
try {
    $stmt = $conn->executeQuery('SELECT COUNT(*) as cnt FROM registration_token');
    $count = $stmt->fetchOne();
    echo "   ✓ registration_token: {$count} rows found\n";
    $results['registration_token'] = "OK ({$count} rows)";
    
    $stmt = $conn->executeQuery('
        SELECT rt.id, u.email, rt.created_at, rt.used_at
        FROM registration_token rt
        JOIN app_user u ON rt.user_id = u.id
        LIMIT 3
    ');
    $tokens = $stmt->fetchAllAssociative();
    foreach ($tokens as $token) {
        $status = $token['used_at'] ? 'USED' : 'PENDING';
        echo "   - {$token['email']}: Created {$token['created_at']} [{$status}]\n";
    }
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    $errors['registration_token'] = $e->getMessage();
}

// Summary
echo "\n\n=== TEST SUMMARY ===\n";
echo "Total tables tested: " . count($results) + count($errors) . "\n";
echo "Successful: " . count($results) . "\n";
echo "Failed: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n=== ERRORS FOUND ===\n";
    foreach ($errors as $table => $error) {
        echo "\n[{$table}]\n";
        echo "  " . $error . "\n";
    }
}

echo "\n=== ALL RESULTS ===\n";
foreach ($results as $table => $result) {
    echo "  ✓ {$table}: {$result}\n";
}

if (count($errors) > 0) {
    echo "\n";
    foreach ($errors as $table => $result) {
        echo "  ✗ {$table}: FAILED\n";
    }
}

echo "\n=== END OF TEST ===\n";
