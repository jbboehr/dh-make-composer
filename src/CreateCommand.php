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
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('package');
        $version = $input->getArgument('version');
        $outputDirectory = $input->getOption('output');

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

        // Create original
        $downloader = new DownloadManager($io);
        $downloader->setDownloader('git', new GitDownloader($io, $composerConfig));
        $downloader->setDownloader('zip', new ZipDownloader($io, $composerConfig));
        $archiver = new ArchiveManager($downloader);
        $archiver->addArchiver(new PharArchiver());
        $origFile = $archiver->archive($package, 'tar.gz', $outputDirectory, $origName);

        // Extract
        $archive = new \PharData($origFile);
        $archive->extractTo($packageOutputDir, null, true);

        // Generate
        (new Generator\ChangelogGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\ControlGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\CompatGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\RulesGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\CopyrightGenerator($output))->generate($package, $packageOutputDir);
        //(new Generator\LinksGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\InstallGenerator($output))->generate($package, $packageOutputDir);
        (new Generator\DocsGenerator($output))->generate($package, $packageOutputDir);

        $output->writeln('Debian package created in ' . $packageOutputDir . '');
        $output->writeln('');
    }
}
