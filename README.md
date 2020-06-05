# Zermelo Installer
This repo exists to faciliate the installation of the various Zermelo packages. See: https://github.com/CareSet/Zermelo 

This packages composer.json file contains all composer packages in it's require
section, so that when this package is installed, it will also bring in the following:
* Zermelo - The back-end API
* ZermeloBladeTabular - The DataTables tabular interface
* ZermeloBladeCard - The Card interface
* ZermeloBladeTreeCard - The tree card interface
* ZermeloBladeGraph - The graph interface (Private only)

When you run 'php artisan zermelo:install' this package will subsequntly call
the installers of all the above subpackages.

A note on branches of this repository:
* "master" - Requires the official packagist versions of zermelo* packages in compooser.json
* "develop" - Requires the dev-master branches, and references the github repositories
