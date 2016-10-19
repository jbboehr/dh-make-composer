<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;

class SourceFormatGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }
        if( !is_dir($outputDirectory . '/debian/source') ) {
            mkdir($outputDirectory . '/debian/source');
        }

        $outputFile = $outputDirectory . '/debian/source/format';
        file_put_contents($outputFile, '3.0 (quilt)');
    }
}
