<?php

namespace DhMakeComposer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    private $outputDirectory;

    private $templateDirectory;

    private $uploader = 'Foo Bar <foo@bar>';

    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build package')
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'Directory that contains composer-driven project'
            )
            ->addOption(
                'output',
                '0',
                InputOption::VALUE_REQUIRED,
                'The output directory (default current directory)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Read git config
        $gitConfigFile = getenv('HOME') . '/.gitconfig';
        if( file_exists($gitConfigFile) ) {
            $ret = parse_ini_file($gitConfigFile);
            if( !empty($ret['email']) && !empty($ret['name']) ) {
                $this->uploader = $ret['name'] . ' <' . $ret['email'] . '>';
            }
        }


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

        $this->outputDirectory = rtrim($outputDirectory, '/\\');
        $this->templateDirectory = __DIR__ . '/../res';

        // Read composer.lock
        $lock = json_decode(file_get_contents($inputFile), false);

        foreach( $lock->packages as $package ) {
            $this->processPackage($package, $output);
        }
    }

    private function processPackage($package, OutputInterface $output)
    {
        $distUrl = $package->dist->url;

        // Make sure it's on github
        // @todo support something other than github
        if( parse_url($distUrl, PHP_URL_HOST) !== 'api.github.com' ) {
            $output->writeln('<error>Package failed for dist ' . $distUrl . ' (only github is currently supported)</error>');
            return;
        }


        $debName = self::composerNameToDebName($package->name);
        $version = ltrim($package->version, 'v');
        $debNameVersion = $debName . '_' . $version;
        $outputDirectory = $this->outputDirectory . '/' . $debNameVersion;
        $debianDirectory = $outputDirectory . '/debian';
        $orig = $this->outputDirectory . '/' . $debName . '_' . $version . '.orig.tar.gz';

        $output->writeln('Creating package ' . $debNameVersion);

        // Download dist
        $this->downloadDist($orig, $distUrl, $output);

        // Make directories
        if( !is_dir($outputDirectory) ) {
            mkdir($outputDirectory);
        }
        if( !is_dir($debianDirectory) ) {
            mkdir($debianDirectory);
        }

        // Extract tarball
        $this->extractTar($orig, $outputDirectory);;

        // Make debian files
        file_put_contents($debianDirectory . '/changelog', $this->makeChangelogFile($debName, $version));
        file_put_contents($debianDirectory . '/control', $this->makeControlFile($package, $debName));
        file_put_contents($debianDirectory . '/copyright', $this->makeCopyrightFile($package));
        file_put_contents($debianDirectory . '/rules', $this->makeRulesFile());
        file_put_contents($debianDirectory . '/compat',  '9');
        file_put_contents($debianDirectory . '/' . $debName . '.install', $this->makeInstallFile($package, $output));

    }



    private function downloadDist($orig, $url, OutputInterface $output)
    {
        $url = str_replace('zipball', 'tarball', $url);

        $output->writeln('Downloading ' . basename($orig));

        if( file_exists($orig) ) {
            // @todo add force
            $output->writeln("File exists, skipping");
            return;
        }


        $output->writeln('From ' . $url);

        // @todo error handling

        $fh = fopen($orig, 'w');
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_FILE => $fh,
            CURLOPT_USERAGENT => 'jbboehr/dh_make_composer',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function($ch, $total, $current) use ($output) {
                $output->write("\r" . $current . ' / ' . $total);
            }
        ));
        $ret = curl_exec($ch);
        fclose($fh);
        curl_close($ch);

        $output->writeln("");

        if( !$ret ) {
            throw new \Exception('curl transfer failed');
        }
    }

    private function extractTar($file, $path)
    {
        passthru('tar xf ' . $file . ' --strip=1 --directory=' . escapeshellarg($path));
    }

    private function makeChangelogFile($debName, $version)
    {
        $lines = array(
            $debName . ' (' . $version . ') UNRELEASED; urgency=low',
            '',
            '  * Initial release',
            '',
            ' -- ' . $this->uploader . '  ' . date('r'),
            '',
        );
        return join("\n", $lines);
    }

    private function makeControlFile($package, $debName)
    {
        $lines = array(
            'Source: ' . $debName,
            'Section: php',
            'Priority: extra',
            'Maintainer: Debian PHP PEAR Maintainers <pkg-php-pear@lists.alioth.debian.org>',
            'Uploaders: ' . $this->uploader,
            'Build-Depends: debhelper (>= 9), pkg-php-tools (>= 1.7~)',
            'Standards-Version: 3.9.6',
            'Homepage: https://packagist.org/packages/' . $package->name,
            'Vcs-Git: ' . str_replace('https://', 'git://', $package->source->url),
            'Vcs-Browser: ' . str_replace('.git', '', $package->source->url),
            '',
            'Package: ' . $debName,
            'Architecture: all',
            'Depends: ${misc:Depends}, ${phpcomposer:Debian-require}',
            'Recommends: ${phpcomposer:Debian-recommend}',
            'Suggests: ${phpcomposer:Debian-suggest}',
            'Description: ${phpcomposer:description}',
            ' ',
            '',
        );
        return join("\n", $lines);
    }

    private function makeCopyrightFile($package)
    {
        $authorName = 'FIXME';
        if( !empty($package->authors) ) {
            $firstAuthor = $package->authors[0];
            $authorName = $firstAuthor->name;
            if( !empty($firstAuthor->email) ) {
                $authorName .= ' <' . $firstAuthor->email . '>';
            }
        }

        $license = 'UNKNOWN';
        if( !empty($package->license) ) {
            $license = $package->license[0];
        }

        $homepage = null;
        if( !empty($package->homepage) ) {
            $homepage = $package->homepage;
        } else if( !empty($package->url) ) {
            $homepage = $package->url;
        }

        $lines = array(
            'Format: http://www.debian.org/doc/packaging-manuals/copyright-format/1.0/',
            'Upstream-Name: ' . $package->name,
            'Upstream-Contact: ' . $authorName,
            'Source: ' . $homepage,
            '',
            'Files: *',
            'Copyright: ' . $authorName,
            'License: ' . $license,
            '',
        );

        return join("\n", $lines);
    }

    private function makeRulesFile()
    {
        return join("\n", array(
            '#!/usr/bin/make -f',
            '%:',
            "\tdh $@ --with phpcomposer",
            '',
        ));
    }

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




    static private function composerNameToDebName($name)
    {
        list($vendor, $package) = explode('/', $name, 2);
        if( $vendor === $package ) {
            $name = $package;
        }
        return 'php-' . trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
    }
}
