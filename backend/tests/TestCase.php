<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * P1-GAP-TEST-DB: Secondary production database guard.
     * The primary guard is in tests/bootstrap.php (pre-app-bootstrap).
     * This re-verifies after Laravel app is booted, checking the actual
     * resolved database connection configuration.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbName = config('database.connections.mysql.database');
        $appEnv = config('app.env');

        if ($dbName === 'tllos_mall' || $appEnv === 'production') {
            $this->fail(
                "FATAL: Test is running against PRODUCTION database!\n" .
                "  APP_ENV={$appEnv}\n" .
                "  DB_DATABASE={$dbName}\n" .
                "Tests must use tllos_mall_test. Check phpunit.xml and .env.testing."
            );
        }
    }
}
