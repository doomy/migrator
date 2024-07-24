<?php

declare(strict_types=1);

namespace Doomy\Migrator\Tests;

use Doomy\Migrator\Migration;
use Doomy\Repository\Tests\Support\TestEntity;
use PHPUnit\Framework\Assert;

final class MigratorTest extends AbstractMigratorTestCase
{
    public function testMigrateCreatesTable(): void
    {
        $tables = $this->connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(0, $tables);

        $this->migrator->migrate();
        $tables = $this->connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(1, $tables);
        Assert::assertEquals('t_migration', $tables[0]['Tables_in_testing']);
    }

    public function testMigrateAppliesMigration(): void
    {
        $migrationFilename = '01-testing-migration.sql';
        file_put_contents(
            __DIR__ . '/migrations/' . $migrationFilename,
            'CREATE TABLE t_test (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id));'
        );
        $migratedAt = new \DateTime();
        $this->migrator->migrate();
        $tables = $this->connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(2, $tables);
        Assert::assertEquals('t_test', $tables[1]['Tables_in_testing']);
        $migrations = $this->data->findAll(Migration::class);
        Assert::assertCount(1, $migrations);
        $migration = reset($migrations);
        Assert::assertInstanceOf(Migration::class, $migration);
        Assert::assertEquals('01-testing-migration', $migration->getMigrationId());
        Assert::assertEqualsWithDelta($migratedAt, $migration->getMigrationDate(), 1);
    }

    public function testCreateMigrationFromEntity(): void
    {
        $migrationId = $this->migrator->createMigrationFromEntity(TestEntity::class);
        Assert::assertSame('001-test_table', $migrationId);
        $migrationFilename = $migrationId . '.sql';
        $migrationSql = file_get_contents(__DIR__ . '/migrations/' . $migrationFilename);
        Assert::assertIsString($migrationSql);
        Assert::assertStringContainsString('CREATE TABLE test_table', $migrationSql);
    }
}
