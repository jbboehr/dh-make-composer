<?php

namespace DhMakeComposer;

use Composer\Package\CompletePackageInterface;

class Utils
{
    static public function composerNameToDebName($name)
    {
        list($vendor, $package) = explode('/', $name, 2);
        // If the package name begins with vendor, omit the vendor from the combined package name
        if( substr($package, 0, strlen($vendor)) === $vendor ) {
            $name = $package;
        }
        return 'php-' . trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
    }

    static public function getUploaderFromGitConfig()
    {
        $gitConfigFile = getenv('HOME') . '/.gitconfig';
        if( !file_exists($gitConfigFile) ) {
            return null;
        }

        $ret = parse_ini_file($gitConfigFile);
        if( empty($ret['email']) || empty($ret['name']) ) {
            return null;
        }

        return array(
            'email' => $ret['email'],
            'name' => $ret['name'],
        );
    }

    static public function getUploaderFromPackage(CompletePackageInterface $package)
    {
        $authors = $package->getAuthors();
        if( !$authors ) {
            return null;
        }

        $firstAuthor = $authors[0];
        if( empty($firstAuthor['name']) || empty($firstAuthor['email']) ) {
            return null;
        }

        return $firstAuthor;
    }

    static public function personArrayToString(array $person)
    {
        return $person['name'] . ' <' . $person['email'] . '>';
    }

    static public function getVersionFromPackage(CompletePackageInterface $package)
    {
        return ltrim($package->getPrettyVersion(), 'v');
    }
}
