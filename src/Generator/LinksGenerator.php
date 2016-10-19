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

        $output = $this->output;
        $debName = Utils::composerNameToDebName($package->getName());
        $outputFile = $outputDirectory . '/debian/' . $debName . '.links';
        $autoload = $package->getAutoload();
        $pathMap = array();

        // Make path map
        if( !empty($autoload['psr-4']) ) {
            foreach( $autoload['psr-4'] as $namespace => $path ) {
                if( empty($path) ) {
                    $output->writeln("<warning>PSR-4 path cannot be empty for namespace: " . $namespace . " </warning>");
                    continue;
                }

                $ns = rtrim(str_replace('\\', '/', $namespace), '/');
                $left = 'usr/share/composer/' . rtrim($path, '/');
                $right = 'usr/share/php/' . $ns;
                $pathMap[$left] = $right;
            }
        }

        if( !empty($autoload['psr-0']) ) {
            foreach( $autoload['psr-0'] as $namespace => $path ) {
                // It's ok for PSR-0 to not include path
                $ns = rtrim(str_replace(array('\\', '_'), '/', $namespace), '/');
                list($leftmost) = explode('/', $ns);
                $left = 'usr/share/composer/' . $path . $leftmost;
                $right = 'usr/share/php/' . $leftmost;
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

        file_put_contents($outputFile, join("\n", $lines));
    }
}
