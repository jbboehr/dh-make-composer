<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;
use DhMakeComposer\Utils;

class ChangelogGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $outputFile = $outputDirectory . '/debian/changelog';
        $debName = Utils::composerNameToDebName($package->getName());
        $version = Utils::getVersionFromPackage($package);
        $pkgVersion = '1';
        $fullVersion = $version . '-' . $pkgVersion;

        $uploader = Utils::getUploaderFromPackage($package)
            ?? Utils::getUploaderFromGitConfig()
            ?? array('name' => 'YOUR NAME', 'email' => 'your.name@example.com');

        $lines = array(
            $debName . ' (' . $fullVersion . ') UNRELEASED; urgency=low',
            '',
            '  * Initial release',
            '',
            ' -- ' . $uploader['name'] . ' <' . $uploader['email'] . '>  ' . date('r'),
            '',
        );

        file_put_contents($outputFile, join("\n", $lines));
    }
}
