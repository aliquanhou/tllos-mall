<?php
/**
 * PHPUnit Bootstrap with Production Database Fail-Closed Protection
 *
 * This bootstrap runs before any test. It refuses to execute if the test
 * environment is configured to use the production database.
 *
 * P1-GAP-TEST-DB Closure: PHPUnit must NEVER connect to tllos_mall (production).
 */

require __DIR__ . '/../vendor/autoload.php';

// Fail-Closed: refuse to run against production database
$dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '';
$appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '';

$isProductionDb = ($dbName === 'tllos_mall');
$isProductionEnv = ($appEnv === 'production');

if ($isProductionDb || $isProductionEnv) {
    fwrite(STDERR, "\n");
    fwrite(STDERR, "================================================================\n");
    fwrite(STDERR, "  FATAL: Refusing to run PHPUnit against production database\n");
    fwrite(STDERR, "================================================================\n");
    fwrite(STDERR, "  APP_ENV     = " . ($appEnv ?: '(not set)') . "\n");
    fwrite(STDERR, "  DB_DATABASE = " . ($dbName ?: '(not set)') . "\n");
    fwrite(STDERR, "\n");
    fwrite(STDERR, "  Tests must use the isolated test database (tllos_mall_test).\n");
    fwrite(STDERR, "  Check phpunit.xml and .env.testing configuration.\n");
    fwrite(STDERR, "================================================================\n");
    fwrite(STDERR, "\n");
    exit(1);
}

// Also verify via Laravel config after app bootstrap is too late;
// this pre-check is the primary guard. TestCase setUp will re-verify.
