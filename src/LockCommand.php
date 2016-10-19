<?php

namespace DhMakeComposer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class LockCommand extends Command
{
    private $outputDirectory;

    private $templateDirectory;

    private $uploader = 'Foo Bar <foo@bar>';

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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputDirectory = $input->getArgument('directory');
        $outputDirectory = $input->getOption('output');

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
            $fakeInput = new ArgvInput(array(
                '',
                $package->name,
                $package->version,
                '--output',
                $outputDirectory
            ), $command->getDefinition());
            $command->run($fakeInput, $output);
        }
    }

    /*
    private function makeInstallFile($package, OutputInterface $output)
    {
        $autoload = $package->autoload;

        // @todo fixme
        $pathMap = array();
        if( !empty($autoload->{'psr-4'}) ) {
            foreach( $autoload->{'psr-4'} as $namespace => $path ) {
                if( empty($path) ) {
                    $output->writeln("<error>PSR-4 path cannot be empty for namespace: " . $namespace . " </error>");
                    continue;
                }

                $ns = rtrim(str_replace('\\', '/', $namespace), '/');
                $left = rtrim($path, '/');
                $right = '/usr/share/php/' . $ns;
                $pathMap[$left] = $right;
            }
        }
        if( !empty($autoload->{'psr-0'}) ) {
            foreach( $autoload->{'psr-0'} as $namespace => $path ) {
                // It's ok for PSR-0 to not include path
                $ns = rtrim(str_replace(array('\\', '_'), '/', $namespace), '/');
                list($leftmost) = explode('/', $ns);
                $left = $path . $leftmost;
                $right = '/usr/share/php/' . $leftmost;
                $pathMap[$left] = $right;
            }
        }
        if( !empty($autoload->files) ) {
            // @todo ?
        }
        if( !empty($autoload->classmap) ) {
            // @todo ?
        }

        $lines = array();
        foreach( $pathMap as $k => $v ) {
            $lines[] = $k . ' ' . $v;
        }

        return join("\n", $lines);
    }
    */
}
