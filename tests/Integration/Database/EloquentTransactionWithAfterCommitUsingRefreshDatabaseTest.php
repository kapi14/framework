<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class EloquentTransactionWithAfterCommitUsingRefreshDatabaseTest extends TestCase
{
    use EloquentTransactionWithAfterCommitTests;
    use RefreshDatabase;

    /**
     * The current database driver.
     *
     * @return string
     */
    protected $driver;

    protected function getEnvironmentSetUp($app)
    {
        $connection = $app['config']->get('database.default');

        $this->driver = $app['config']->get("database.connections.$connection.driver");
    }
}
