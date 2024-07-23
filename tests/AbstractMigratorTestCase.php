<?php

declare(strict_types=1);

namespace Doomy\Migrator\Tests;

use Doomy\EntityCache\EntityCache;
use Doomy\Migrator\Migrator;
use Doomy\Ormtopus\DataEntityManager;
use Doomy\Repository\EntityFactory;
use Doomy\Repository\Helper\DbHelper;
use Doomy\Repository\RepoFactory;
use Doomy\Repository\TableDefinition\ColumnTypeMapper;
use Doomy\Repository\TableDefinition\TableDefinitionFactory;
use Doomy\Testing\AbstractDbAwareTestCase;

abstract class AbstractMigratorTestCase extends AbstractDbAwareTestCase
{
    protected Migrator $migrator;

    protected DataEntityManager $data;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $entityFactory = new EntityFactory();
        $columnTypeMapper = new ColumnTypeMapper();
        $dbHelper = new DbHelper($columnTypeMapper);
        $tableDefinitionFactory = new TableDefinitionFactory($columnTypeMapper);
        $repoFactory = new RepoFactory($this->connection, $entityFactory, $dbHelper, $tableDefinitionFactory);
        $this->data = new DataEntityManager($repoFactory, new EntityCache());
        $this->migrator = new Migrator($this->connection, $this->data, [
            'migrations_directory' => __DIR__ . '/migrations',
        ], $tableDefinitionFactory, $dbHelper);
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
}
