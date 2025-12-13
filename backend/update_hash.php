<?php
// Simple script to update all user passwords to 'admin'
// Run: php bin/console doctrine:dbal:run-sql "$(php update_hash.php)"

$hash = password_hash('admin', PASSWORD_BCRYPT);
echo "UPDATE app_user SET password = '$hash';";
