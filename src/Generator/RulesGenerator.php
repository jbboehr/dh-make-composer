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
            "\tdh $@ --with phpcomposer --buildsystem=makefile",
            '',
            'override_dh_auto_build:',
        );


//        if( $sourceRoot ) {
//            $lines[] = "\tphpab --output " . $sourceRoot . '/autoload.php ' . $sourceRoot;
//        }

        $lines[] = '';

        file_put_contents($outputFile, join("\n", $lines));
    }
}
