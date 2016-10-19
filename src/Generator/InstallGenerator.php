<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;
use DhMakeComposer\Utils;

class InstallGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $debName = Utils::composerNameToDebName($package->getName());
        $outputFile = $outputDirectory . '/debian/' . $debName . '.install';
        $targetDirectory = 'usr/share/composer/' . $package->getName();
        $autoload = $package->getAutoload();
        $lines = array();
        $files = array(
            'composer.json' => true,
        );
        $hasRoot = false;

        foreach( $autoload as $type => $sub ) {
            foreach( $sub as $namespace => $path ) {
                $path = trim($path, '/');
                if( !$path ) {
                    // Empty path indicates repo root
                    $hasRoot = true;
                } else {
                    $files[$path] = true;
                }
            }
        }

        if( ($binaries = $package->getBinaries()) ) {
            foreach( $binaries as $bin ) {
                $files[$bin] = true;
            }
        }

        if( $hasRoot ) {
            // Scan output directoryfor .php files and directories that start with an
            // uppercase letter
            $it = new \DirectoryIterator($outputDirectory);
            foreach( $it as $file ) {
                $basename = $file->getBasename();
                if( $basename[0] === '.' ) {
                    continue;
                }
                if( $file->isDir() && preg_match('/^[A-Z]/', $basename) ) {
                    $files[$file->getFilename()] = true;
                } else if( $file->isFile() && substr($basename, -4) === '.php' ) {
                    $files[$file->getFilename()] = true;
                }
            }

        }

        $files = array_filter(array_keys($files), function($file) {
            return !in_array(strtolower($file), array('tests', 'test'));
        });

        foreach( $files as $file ) {
            $lines[] = $this->pathToInstallLine($targetDirectory, $file);
        }

        file_put_contents($outputFile, join("\n", $lines));
    }

    private function pathToInstallLine($targetDirectory, $path)
    {
        $parts = explode('/', $path);
        array_pop($parts);
        if( !empty($parts) ) {
            array_unshift($parts, '');
        }
        return $path . ' ' . $targetDirectory . join('/', $parts);
    }
}
