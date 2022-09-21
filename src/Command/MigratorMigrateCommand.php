<?php

namespace Doomy\Migrator\Command;

use Doomy\Migrator\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigratorMigrateCommand extends Command
{
    private Migrator $migrator;

    public function __construct(Migrator $migrator) {
        $this->migrator = $migrator;
        parent::__construct();
    }

    protected function configure(): void
    {
        // choose command name
        $this->setName('migrator:migrate')
            // description (optional)
            ->setDescription('Runs migrations');
    }

    /**
     * Don't forget to return 0 for success or non-zero for error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\sprintf('Migrating'));


        $this->migrator->migrate();
        $output->writeln($this->migrator->getOutput());

        return 1;
    }
}