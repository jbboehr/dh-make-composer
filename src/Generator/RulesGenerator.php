<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;
use DhMakeComposer\Utils;

class RulesGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $outputFile = $outputDirectory . '/debian/rules';
        $sourceRoot = Utils::detectSourceRoot($package);

        $lines = array(
            '#!/usr/bin/make -f',
            '%:',
            "\tdh $@ --with phpcomposer",
            '',
            'override_dh_auto_build:',
        );

        if( $sourceRoot ) {
            $lines[] = "\tphpab --output " . $sourceRoot . '/autoload.php ' . $sourceRoot;
        } else {
            foreach( Utils::getInterestingPathsFromPackage($package) as $path ) {
                $lines[] = "\tphpab --output " . $path . '/autoload.php ' . $path;
            }
        }

        $lines[] = '';

        file_put_contents($outputFile, join("\n", $lines));
    }

    private function detectSourceRoot(CompletePackageInterface $package)
    {
        $autoload = $package->getAutoload();
        $paths = array();

        if( !empty($autoload['psr-4']) ) {
            foreach( $autoload['psr-4'] as $namespace => $path ) {
                $paths[$path] = true;
                //$lines[] = "\tphpab --output " . $path . '/autoload.php ' . $path;
            }
        }

        if( !empty($autoload['psr-0']) ) {
            foreach( $autoload['psr-0'] as $namespace => $path ) {
                $ns = trim(str_replace(array('\\', '_'), '/', $namespace), '/');
                $path = trim($path, '/') . '/' . $ns;
                if( is_file($path . '.php') ) {
                    $path = dirname($path);
                }
                $paths[$path] = true;
                //$lines[] = "\tphpab --output " . $path . '/autoload.php ' . $path;
            }
        }

        if( !empty($autoload['classmap']) ) {
            foreach( $autoload['classmap'] as $path ) {
                $paths[$path] = true;
            }
        }

        if( !empty($autoload['files']) ) {
            foreach( $autoload['files'] as $path ) {
                // meh
                if( is_file($path) ) {
                    $path = dirname($path);
                }
                $paths[$path] = true;
            }
        }

        $paths = array_keys($paths);

        if( empty($paths) ) {
            return null;
        } else if( count($paths) === 1 ) {
            $path = $paths[0];
        } else {
            $commonPath = array_shift($paths);
            foreach( $paths as $path ) {
                for( $i = 0; $i < strlen($path) && $i < strlen($commonPath); $i ++ ) {
                    if( $commonPath[$i] != $path[$i] ) {
                        $commonPath = substr($commonPath, 0, $i);
                        break;
                    }
                }

            }
            $path = trim($commonPath, '/');
        }

        if( $path ) {
            return "\tphpab --output " . $path . '/autoload.php composer.json';
            //return "\tphpab --output " . $path . '/autoload.php ' . $path;
        } else {
            return null;
        }
    }
}
