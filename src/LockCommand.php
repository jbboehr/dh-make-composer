<?php

namespace DhMakeComposer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LockCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('lock')
            ->setDescription('Build package from lock file')
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'Directory that contains composer-driven project'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'The output directory',
                '.'
            )
            ->addOption(
                'build',
                'b',
                InputOption::VALUE_NONE,
                'Build the packages'
            )
            ->addOption(
                'onlysourcediff',
                null,
                InputOption::VALUE_NONE,
                'Do not include orig'
            )
            ->addOption(
                'freshdeb',
                null,
                InputOption::VALUE_NONE,
                'Re-extract source packages (deletes debian folder, etc)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputDirectory = $input->getArgument('directory');
        $outputDirectory = $input->getOption('output');
        $buildAlso = $input->getOption('build');
        $onlySourceDiff = $input->getOption('onlysourcediff');
        $freshDeb = $input->getOption('freshdeb');

        if( substr($inputDirectory, -strlen('composer.lock')) === 'composer.lock' ) {
            $inputFile = $inputDirectory;
            $inputDirectory = dirname($inputDirectory);
            // ok
        } else {
            $inputFile = rtrim($inputDirectory, '/\\') . '/composer.lock';
        }

        if( !file_exists($inputFile) ) {
            $output->writeln('<error>No composer.lock in ' . $inputDirectory . '</error>');
            return -1;
        }

        // Read composer.lock
        $lock = json_decode(file_get_contents($inputFile), false);

        foreach( $lock->packages as $package ) {
            $command = new CreateCommand();
            $argv = array(
                '',
                $package->name,
                $package->version,
                '--output',
                $outputDirectory
            );
            if( $buildAlso ) {
                $argv[] = '--build';
            }
            if( $onlySourceDiff ) {
                $argv[] = '--onlysourcediff';
            }
            if( $freshDeb ) {
                $argv[] = '--freshdeb';
            }
            $fakeInput = new ArgvInput($argv, $command->getDefinition());
            $command->run($fakeInput, $output);
        }

        return 0;
    }
}
