<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;

class RulesGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $outputFile = $outputDirectory . '/debian/rules';
        file_put_contents($outputFile, join("\n", array(
            '#!/usr/bin/make -f',
            '%:',
            "\tdh $@ --with phpcomposer",
            '',
        )));
    }
}
