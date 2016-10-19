<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;
use DhMakeComposer\Utils;

class ControlGenerator extends AbstractGenerator
{
    public function generate(CompletePackageInterface $package, $outputDirectory)
    {
        // Make sure directory exists
        if( !is_dir($outputDirectory . '/debian') ) {
            mkdir($outputDirectory . '/debian');
        }

        $outputFile = $outputDirectory . '/debian/control';
        $debName = Utils::composerNameToDebName($package->getName());

        $uploader = Utils::getUploaderFromGitConfig()
            ?? array('name' => 'YOUR NAME', 'email' => 'your.name@example.com');
        $maintainer = Utils::getUploaderFromPackage($package)
            ?? $uploader
            ?? array('name' => 'Debian PHP PEAR Maintainers', 'email' => 'pkg-php-pear@lists.alioth.debian.org');

        $lines = array(
            'Source: ' . $debName,
            'Section: php',
            'Priority: extra',
            'Maintainer: ' . Utils::personArrayToString($maintainer),
            'Uploaders: ' . Utils::personArrayToString($uploader),
            'Build-Depends: debhelper (>= 9), pkg-php-tools (>= 1.7~)',
            'Standards-Version: 3.9.6',
            'Homepage: ' . $package->getHomepage() ?? $package->getDistUrl(),
            'Vcs-Git: ' . $this->getVcsGit($package),
            'Vcs-Browser: ' . $package->getSourceUrl(),
            '',
            'Package: ' . $debName,
            'Architecture: all',
            'Depends: ${misc:Depends}, ${phpcomposer:Debian-require}',
            'Recommends: ${phpcomposer:Debian-recommend}',
            'Suggests: ${phpcomposer:Debian-suggest}',
            'Description: ${phpcomposer:description}',
            ' ',
            '',
        );

        file_put_contents($outputFile, join("\n", $lines));
    }

    private function getVcsGit(CompletePackageInterface $package)
    {
        $url = $package->getSourceUrl();
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return 'git' . substr($url, strlen($scheme));
    }
}
