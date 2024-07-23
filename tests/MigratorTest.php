<?php

declare(strict_types=1);

namespace Doomy\Migrator\Tests;

use Doomy\EntityCache\EntityCache;
use Doomy\Migrator\Migration;
use Doomy\Migrator\Migrator;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\Repository\EntityFactory;
use Doomy\Repository\Helper\DbHelper;
use Doomy\Repository\RepoFactory;
use Doomy\Repository\TableDefinition\ColumnTypeMapper;
use Doomy\Repository\TableDefinition\TableDefinitionFactory;
use PHPUnit\Framework\Assert;

final class MigratorTest extends AbstractDbAwareTestCase
{
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
        $entityFactory = new EntityFactory();
        $columnTypeMapper = new ColumnTypeMapper();
        $dbHelper = new DbHelper($columnTypeMapper);
        $tableDefinitionFactory = new TableDefinitionFactory($columnTypeMapper);
        $repoFactory = new RepoFactory($this->connection, $entityFactory, $dbHelper, $tableDefinitionFactory);
        $data = new DataEntityManager($repoFactory, new EntityCache());
        $migrator = new Migrator($this->connection, $data, [
            'migrations_directory' => __DIR__ . '/migrations',
        ], $tableDefinitionFactory, $dbHelper);

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
        $entityFactory = new EntityFactory();
        $columnTypeMapper = new ColumnTypeMapper();
        $dbHelper = new DbHelper($columnTypeMapper);
        $tableDefinitionFactory = new TableDefinitionFactory($columnTypeMapper);
        $repoFactory = new RepoFactory($this->connection, $entityFactory, $dbHelper, $tableDefinitionFactory);
        $data = new DataEntityManager($repoFactory, new EntityCache());
        $migrator = new Migrator($this->connection, $data, [
            'migrations_directory' => __DIR__ . '/migrations',
        ], $tableDefinitionFactory, $dbHelper);
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
        Assert::assertCount(1, $migrations);
        $migration = reset($migrations);
        Assert::assertInstanceOf(Migration::class, $migration);
        Assert::assertEquals('01-testing-migration', $migration->getMigrationId());
        // TODO: test migration date
    }
}
