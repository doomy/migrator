<?php

declare(strict_types=1);

namespace Doomy\Migrator\Tests;

use Doomy\Migrator\Command\MigratorCreateEntityTableCommand;
use Doomy\Migrator\Migration;
use Doomy\Repository\Tests\Support\TestEntity;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class MigratorCreateEntityTableCommandTest extends AbstractMigratorTestCase
{
    public function testRunMigrateCommand(): void
    {
        $application = new Application();
        $application->add(new MigratorCreateEntityTableCommand($this->migrator));
        $command = $application->find('migrator:create-entity-table');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'entityClass' => TestEntity::class,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('CREATE TABLE t_migration', $output);
        $this->assertStringContainsString('CREATE TABLE test_table', $output);
        $tables = $this->connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(2, $tables);
        Assert::assertEquals('test_table', $tables[1]['Tables_in_testing']);
        $migrations = $this->data->findAll(Migration::class);
        Assert::assertCount(1, $migrations);
        $migrationSql = file_get_contents(__DIR__ . '/migrations/' . '001-test_table.sql');
        Assert::assertIsString($migrationSql);
        Assert::assertStringContainsString('CREATE TABLE test_table', $migrationSql);
    }
}
