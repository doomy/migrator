<?php

declare(strict_types=1);

namespace Doomy\Migrator\Tests;

use Doomy\EntityCache\EntityCache;
use Doomy\Migrator\Command\MigratorMigrateCommand;
use Doomy\Migrator\Migration;
use Doomy\Migrator\Migrator;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\Repository\EntityFactory;
use Doomy\Repository\Helper\DbHelper;
use Doomy\Repository\RepoFactory;
use Doomy\Repository\TableDefinition\ColumnTypeMapper;
use Doomy\Repository\TableDefinition\TableDefinitionFactory;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class MigratorMigrateCommandTest extends AbstractDbAwareTestCase
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

    public function testRunMigrateCommand(): void
    {
        $application = new Application();
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

        $application->add(new MigratorMigrateCommand($migrator));
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
        $migrations = $data->findAll(Migration::class);
        Assert::assertCount(1, $migrations);
    }
}
