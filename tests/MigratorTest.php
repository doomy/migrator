<?php

declare(strict_types=1);

namespace Doomy\Migrator\Tests;

use Doomy\CustomDibi\Connection;
use Doomy\EntityCache\EntityCache;
use Doomy\Migrator\Migrator;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\Repository\EntityFactory;
use Doomy\Repository\RepoFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    public function __construct(string $name)
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../vendor/doomy/testing/testingDbCredentials.json'), true);
        $this->connection = new Connection($config);
        parent::__construct($name);
    }

    protected function tearDown(): void
    {
        $this->connection->query('DROP TABLE t_migration');
    }

    public function testMigrateCreatesTable(): void
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../vendor/doomy/testing/testingDbCredentials.json'), true);
        $connection = new Connection($config);
        $entityFactory = new EntityFactory($connection);
        $repoFactory = new RepoFactory($connection, $entityFactory);
        $data = new DataEntityManager($repoFactory, new EntityCache());
        $migrator = new Migrator($connection, $data, [
            'migrations_directory' => __DIR__ . '/migrations',
        ]);

        $tables = $connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(0, $tables);

        $migrator->migrate();
        $tables = $connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(1, $tables);
        Assert::assertEquals('t_migration', $tables[0]['Tables_in_testing']);
    }
}
