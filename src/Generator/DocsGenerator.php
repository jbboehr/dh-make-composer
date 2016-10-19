<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;
use DhMakeComposer\Utils;

class DocsGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $debName = Utils::composerNameToDebName($package->getName());
        $outputFile = $outputDirectory . '/debian/' . $debName . '.docs';

        // Find files
        $files = array_map(function($file) use ($outputDirectory) {
            return substr($file, strlen($outputDirectory) + 1);
        }, array_unique(array_merge(
            glob($outputDirectory . '/*.md'),
            glob($outputDirectory . '/*.markdown'),
            glob($outputDirectory . '/LICENSE*'),
            glob($outputDirectory . '/README*')
        )));

        if( !empty($files) ) {
            file_put_contents($outputFile, join("\n", $files));
        }
    }
}
