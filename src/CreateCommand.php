<?php

namespace DhMakeComposer;

use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\Downloader\GitDownloader;
use Composer\Downloader\ZipDownloader;
use Composer\Factory;
use Composer\IO\ConsoleIO;
use Composer\Package\Archiver\ArchiveManager;
use Composer\Package\Archiver\PharArchiver;
use Composer\Package\CompletePackage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create debian package')
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'The package name'
            )
            ->addArgument(
                'version',
                InputArgument::REQUIRED,
                'The package version'
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
        $packageName = $input->getArgument('package');
        $version = $input->getArgument('version');
        $outputDirectory = $input->getOption('output');
        $buildAlso = $input->getOption('build');
        $onlySourceDiff = $input->getOption('onlysourcediff');
        $freshDeb = $input->getOption('freshdeb');

        // Make composer object
        $io = new ConsoleIO($input, $output, new HelperSet());
        $composerFactory = new Factory();
        $composerConfig = new Config(true, $outputDirectory);
        $composer = $composerFactory->createComposer($io);

        // Query package
        $package = $composer->getRepositoryManager()->findPackage($packageName, $version);
        if( !$package ) {
            throw new \InvalidArgumentException('Unable to find ' . $packageName . ' with version ' . $version);
        } else if( !($package instanceof CompletePackage) ) {
            throw new \InvalidArgumentException('Invalid package class: ' . get_class($package));
        }

        // Make vars
        $packageName = $package->getName();
        $version = Utils::getVersionFromPackage($package);
        $debName = Utils::composerNameToDebName($packageName);
        $debNameVersion = $debName . '-' . $version;
        $packageOutputDir = $outputDirectory . '/' . $debNameVersion;
        $origName = $debName . '_' . $version . '.orig';
        $origFile = null;

        // Attempt to get source package from apt
        if( true ) { // @todo add flag
            $origFile = $this->doFetchSourcePackage($debName, $version, $outputDirectory, $output);
            if( $origFile ) {
                // Don't include source if fetching from apt-get source
                $onlySourceDiff = true;
                if( $freshDeb ) {
                    passthru('rm -rf ' . escapeshellarg($packageOutputDir));
                }
            }
        }

        if( !$origFile ) {
            // Create original
            $downloader = new DownloadManager($io);
            $downloader->setDownloader('git', new GitDownloader($io, $composerConfig));
            $downloader->setDownloader('zip', new ZipDownloader($io, $composerConfig));
            $archiver = new ArchiveManager($downloader);
            $archiver->addArchiver(new PharArchiver());
            $origFile = $archiver->archive($package, 'tar.gz', $outputDirectory, $origName);
            $freshDeb = true;
        }

        // Extract
        if( $freshDeb ) {
            $archive = new \PharData($origFile);
            $archive->extractTo($packageOutputDir, null, true);
        }

        // Generate
        (new Generator\ChangelogGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\ControlGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\CompatGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\RulesGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\CopyrightGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\LinksGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\InstallGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\DocsGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\SourceFormatGenerator($output))->generate($package, $packageOutputDir);

        $output->writeln('Debian package created in ' . $packageOutputDir . '');
        $output->writeln('');

        if( $buildAlso ) {
            $this->doRelease($packageOutputDir, $output);
            $this->doBinaryBuild($packageOutputDir, $output);
            $this->doSourceBuild($packageOutputDir, $output, $onlySourceDiff);
        }
    }

    private function doRelease($packageOutputDir, OutputInterface $output)
    {
        $process = new Process("debchange --release ''", $packageOutputDir);
        $process->run(function($type, $data) use ($output) {
            $output->write($data, false, Output::OUTPUT_RAW);
        });
        if( !$process->isSuccessful() ) {
            $output->writeln($process->getErrorOutput());
            throw new \RuntimeException('Failed to execute command');
        }
    }

    private function doBinaryBuild($packageOutputDir, OutputInterface $output)
    {
        $process = new Process("debuild", $packageOutputDir);
        $process->run(function($type, $data) use ($output) {
            $output->write($data, false, Output::OUTPUT_RAW);
        });
        $output->writeln('');
        if( !$process->isSuccessful() ) {
            $output->writeln($process->getErrorOutput());
            throw new \RuntimeException('Failed to execute command');
        }
    }

    private function doSourceBuild($packageOutputDir, OutputInterface $output, $onlySourceDiff)
    {
        $cmd = 'debuild -S';
        if( $onlySourceDiff ) {
            $cmd .= ' -sd';
        }
        $process = new Process($cmd, $packageOutputDir);
        $process->run(function($type, $data) use ($output) {
            $output->write($data, false, Output::OUTPUT_RAW);
        });
        $output->writeln('');
        if( !$process->isSuccessful() ) {
            $output->writeln($process->getErrorOutput());
            throw new \RuntimeException('Failed to execute command');
        }
    }

    private function doFetchSourcePackage($debName, $version, $outputDirectory, OutputInterface $output)
    {
        $process = new Process("apt-get source " . $debName . '=' . $version, $outputDirectory);

        $output->writeln('');
        $process->run(function($type, $data) use ($output) {
            $output->write($data, false, Output::OUTPUT_RAW);
        });
        $output->writeln('');

        if( $process->isSuccessful() ) {
            return $outputDirectory . '/' . $debName . '_' . $version . '.orig.tar.gz';
        } else {
            return false;
        }
    }
}
