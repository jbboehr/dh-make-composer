
# dh-make-composer

Command line utility that build a debian package from a composer package.
See also: [PackageBuilder](https://github.com/wapmorgan/PackageBuilder).


### Usage

```sh
composer require jbboehr/dh-make-composer
```

Create a source package from a specific package:

```sh
dh_make_composer create monolog/monolog 1.15.0
```

Create a source package from another package's lock file:

```sh
dh_make_composer lock composer.lock --output output
```

Notes:

- The name and email in your `~/.gitconfig` will be used for the uploader.
- The paths used in the `autoloader` and `bin` keys in `composer.json` will be used to add files to the package
- If an empty path is used, all `.php` files and directories that start with an uppercase letter will be added by default
- The files are installed into `/usr/share/composer` with the same directory structure as the source repo
- Each namespace in the autoloader is symlinked into `/usr/share/php` such that you can use a simple autoloader to include the class.

### License

See the [license file](LICENSE.md)
