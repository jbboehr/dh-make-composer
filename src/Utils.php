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

    static public function getInterestingPathsFromPackage(CompletePackageInterface $package)
    {
        $autoload = $package->getAutoload();

        $files = array(
            'composer.json' => true,
        );

        foreach( $autoload as $type => $sub ) {
            foreach( $sub as $namespace => $path ) {
                $files[trim($path, '/')] = true;
            }
        }

        if( ($binaries = $package->getBinaries()) ) {
            foreach( $binaries as $bin ) {
                $files[$bin] = true;
            }
        }

        return array_keys($files);
    }

    static public function detectSourceRoot(CompletePackageInterface $package)
    {
        $autoload = $package->getAutoload();
        $paths = array();

        if( !empty($autoload['psr-4']) ) {
            foreach( $autoload['psr-4'] as $namespace => $path ) {
                $paths[$path] = true;
            }
        }

        if( !empty($autoload['psr-0']) ) {
            foreach( $autoload['psr-0'] as $namespace => $path ) {
                $ns = trim(str_replace(array('\\', '_'), '/', $namespace), '/');
                $path = trim($path, '/') . '/' . $ns;
                $paths[$path] = true;
            }
        }

        if( !empty($autoload['classmap']) ) {
            foreach( $autoload['classmap'] as $path ) {
                $paths[$path] = true;
            }
        }

        if( !empty($autoload['files']) ) {
            foreach( $autoload['files'] as $path ) {
                $path = dirname($path);
                $paths[$path] = true;
            }
        }

        $paths = array_keys($paths);

        if( empty($paths) ) {
            return null;
        } else if( count($paths) === 1 ) {
            $path = $paths[0];
        } else {
            /*
            $commonPath = array_shift($paths);
            foreach( $paths as $path ) {
                for( $i = 0; $i < strlen($path) && $i < strlen($commonPath); $i ++ ) {
                    if( $commonPath[$i] != $path[$i] ) {
                        $commonPath = substr($commonPath, 0, $i);
                        break;
                    }
                }

            }
            $path = $commonPath;
            */
            return null;
        }

        if( $path ) {
            return trim($path, '/');
        } else {
            return null;
        }
    }
}
