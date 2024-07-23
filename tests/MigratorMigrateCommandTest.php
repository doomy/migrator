<?php

declare(strict_types=1);

namespace Doomy\Migrator\Tests;

use Doomy\Migrator\Command\MigratorMigrateCommand;
use Doomy\Migrator\Migration;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class MigratorMigrateCommandTest extends AbstractMigratorTestCase
{
    public function testRunMigrateCommand(): void
    {
        $application = new Application();
        $migrationFilename = '01-testing-migration.sql';
        file_put_contents(
            __DIR__ . '/migrations/' . $migrationFilename,
            'CREATE TABLE t_test (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id));'
        );

        $application->add(new MigratorMigrateCommand($this->migrator));
        $command = $application->find('migrator:migrate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            // prefix the key with two dashes when passing options,
            // e.g: '--some-option' => 'option_value',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('CREATE TABLE t_migration', $output);
        $this->assertStringContainsString('CREATE TABLE t_test', $output);
        $tables = $this->connection->query('SHOW TABLES')
            ->fetchAll();
        Assert::assertCount(2, $tables);
        Assert::assertEquals('t_test', $tables[1]['Tables_in_testing']);
        $migrations = $this->data->findAll(Migration::class);
        Assert::assertCount(1, $migrations);
    }
}
