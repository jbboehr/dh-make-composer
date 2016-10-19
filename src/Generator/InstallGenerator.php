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
        $sourceRoot = Utils::detectSourceRoot($package);
        $lines = array();

        if( $sourceRoot ) {
            $lines[] = $this->pathToInstallLine($targetDirectory, $sourceRoot);
        } else {
            foreach( Utils::getInterestingPathsFromPackage($package) as $path ) {
                $lines[] = $this->pathToInstallLine($targetDirectory, $path);;
            }
        }

        file_put_contents($outputFile, join("\n", $lines));
    }

    private function pathToInstallLine($targetDirectory, $path)
    {
        $parts = explode('/', $path);
        array_pop($parts);
        return $path . ' ' . $targetDirectory . '/' . join('/', $parts);
    }
}
