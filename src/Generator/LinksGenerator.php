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
                $fragment = str_replace(array('\\', '_'), '/', $namespace);
                $left = rtrim($path, '/') . ($path && $fragment ? '/' : '') . $fragment;
                $right = rtrim($fragment, '/');
                $pathMap[$left] = $right;
            }
        }

        $lines = array();
        foreach( $pathMap as $left => $right ) {
            if( in_array(strtolower($left), array('test', 'tests')) ) {
                continue;
            }
            $lines[] = $leftPrefix . $left . ' ' . $rightPrefix . $right;
        }

        if( ($binaries = $package->getBinaries()) ) {
            foreach( $binaries as $bin ) {
                $lines[] = $leftPrefix . $bin . ' usr/bin/' . basename($bin);
            }
        }

        file_put_contents($outputFile, join("\n", $lines));
    }
}
