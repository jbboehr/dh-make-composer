<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;

class CompatGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $outputFile = $outputDirectory . '/debian/compat';
        file_put_contents($outputFile, '9');
    }
}
