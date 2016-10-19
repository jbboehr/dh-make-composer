<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;
use DhMakeComposer\Utils;

class LinksGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $debName = Utils::composerNameToDebName($package->getName());
        $outputFile = $outputDirectory . '/debian/' . $debName . '.links';
        $autoload = $package->getAutoload();
        $pathMap = array();
        $leftPrefix = 'usr/share/composer/' . $package->getName() . '/';
        $rightPrefix = 'usr/share/php/';

        // Make path map
        if( !empty($autoload['psr-4']) ) {
            foreach( $autoload['psr-4'] as $namespace => $path ) {
                $right = rtrim($path, '/');
                $left = rtrim(str_replace('\\', '/', $namespace), '/');
                $pathMap[$right] = $left;
            }
        }

        if( !empty($autoload['psr-0']) ) {
            foreach( $autoload['psr-0'] as $namespace => $path ) {
                list($leftmost) = explode('\\', $namespace);
                $left = $path . $leftmost;
                $right = $leftmost;
                $pathMap[$left] = $right;
            }
        }

        $lines = array();
        foreach( $pathMap as $left => $right ) {
            $lines[] = $leftPrefix . $left . ' ' . $rightPrefix . $right;
        }

        if( ($binaries = $package->getBinaries()) ) {
            foreach( $binaries as $bin ) {
                $lines[] = 'usr/bin/' . basename($bin) . ' ' . $leftPrefix . $bin;
            }
        }

        file_put_contents($outputFile, join("\n", $lines));
    }
}
