<?php

declare(strict_types=1);

namespace Doomy\Migrator\Command;

use Doomy\Migrator\Migrator;
use Doomy\Repository\Model\Entity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('migrator:create-entity-table')]
class MigratorCreateEntityTableCommand extends Command
{
    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('entityClass', InputArgument::REQUIRED, 'Entity class to create table for');
    }

    /**
     * Don't forget to return 0 for success or non-zero for error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\sprintf('Migrating'));
        // choose command name

        $entityClass = $input->getArgument('entityClass');
        assert(\is_string($entityClass), 'Entity class must be a string');
        assert(is_subclass_of($entityClass, Entity::class), 'Entity class must be a subclass of ' . Entity::class);
        $this->migrator->createMigrationFromEntity($entityClass);
        $this->migrator->migrate();
        $output->writeln($this->migrator->getOutput());

        return 1;
    }
}
