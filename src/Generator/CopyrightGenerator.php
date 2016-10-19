<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;
use DhMakeComposer\Utils;

class CopyrightGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $outputFile = $outputDirectory . '/debian/copyright';
        $author = Utils::getUploaderFromPackage($package)
            ?? array('name' => 'YOUR NAME', 'email' => 'your.name@example.com');
        $sourceUrl = $package->getHomepage()
            ?? $package->getSourceUrl()
            ?? $package->getDistUrl()
            ?? 'https://FIX.ME/';

        $lines = [
            'Format: http://www.debian.org/doc/packaging-manuals/copyright-format/1.0/',
            'Upstream-Name: ' . $package->getPrettyName(),
            'Upstream-Contact: ' . Utils::personArrayToString($author),
            'Source: ' . $sourceUrl,
            '',
            'Files: *',
            'Copyright: ' . Utils::personArrayToString($author),
            'License: ' . $this->translateLicense($package->getLicense(), $outputDirectory),
        ];

        file_put_contents($outputFile, join("\n", $lines));
    }

    private function translateLicense($licenseArray, $outputDirectory)
    {
        $possibleLicenses = array_merge(
            glob($outputDirectory . '/LICENSE*'),
            glob($outputDirectory . '/COPYING*')
        );
        $end = '';
        if( !empty($possibleLicenses) ) {
            $end = $this->indentLicense($possibleLicenses[0]);
        }

        // @todo fixme
        return join(', ', $licenseArray) . $end;
    }

    private function indentLicense($licenseFile)
    {
        $buf = "";
        foreach( file($licenseFile) as $line ) {
            $line = trim($line);
            if( $line ) {
                $buf .= "\n " . $line;
            } else {
                $buf .= "\n .";
            }
        }

        if( substr($buf, -3) === "\n ." ) {
            $buf = substr($buf, 0, -3);
        }

        return $buf;
    }
}
