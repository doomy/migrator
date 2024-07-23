<?php

declare(strict_types=1);

namespace Doomy\Migrator\Tests;

use Doomy\CustomDibi\Connection;
use Doomy\EntityCache\EntityCache;
use Doomy\Migrator\Migration;
use Doomy\Migrator\Migrator;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\Repository\EntityFactory;
use Doomy\Repository\RepoFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertCount;

final class MigratorTest extends TestCase
{
    private Connection $connection;

    public function __construct(string $name)
    {
        $rawConfig = file_get_contents(__DIR__ . '/../vendor/doomy/testing/testingDbCredentials.json');
        if ($rawConfig === false) {
            throw new \Exception('Could not read config');
        }
        $config = json_decode($rawConfig, true);
        if (! is_array($config)) {
            throw new \Exception('Invalid config');
        }
        $this->connection = new Connection($config);
        parent::__construct($name);
    }

    protected function tearDown(): void
    {
        $this->connection->query('DROP TABLE t_migration');
        $this->connection->query('DROP TABLE if exists t_test');

        $migrationFiles = glob(__DIR__ . '/migrations/*');
        if (is_array($migrationFiles)) {
            foreach ($migrationFiles as $file) {
                is_file($file) && unlink($file);
            }
        }
    }

    public function testMigrateCreatesTable(): void
    {
        $entityFactory = new EntityFactory($this->connection);
        $repoFactory = new RepoFactory($this->connection, $entityFactory);
        $data = new DataEntityManager($repoFactory, new EntityCache());
        $migrator = new Migrator($this->connection, $data, [
            'migrations_directory' => __DIR__ . '/migrations',
        ]);

        $tables = $this->connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(0, $tables);

        $migrator->migrate();
        $tables = $this->connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(1, $tables);
        Assert::assertEquals('t_migration', $tables[0]['Tables_in_testing']);
    }

    public function testMigrateAppliesMigration(): void
    {
        $entityFactory = new EntityFactory($this->connection);
        $repoFactory = new RepoFactory($this->connection, $entityFactory);
        $data = new DataEntityManager($repoFactory, new EntityCache());
        $migrator = new Migrator($this->connection, $data, [
            'migrations_directory' => __DIR__ . '/migrations',
        ]);
        $migrationFilename = '01-testing-migration.sql';
        file_put_contents(
            __DIR__ . '/migrations/' . $migrationFilename,
            'CREATE TABLE t_test (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id));'
        );
        $migrator->migrate();
        $tables = $this->connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(2, $tables);
        Assert::assertEquals('t_test', $tables[1]['Tables_in_testing']);
        $migrations = $data->findAll(Migration::class);
        assertCount(1, $migrations);
        $migration = reset($migrations);
        Assert::assertEquals('01-testing-migration', $migration->MIGRATION_ID);
        // TODO: test migration date
    }
}
