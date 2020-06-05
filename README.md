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
* "develop" - Requires the dev-master branches **NOTE** repositories must be added at root level composer.json

In order to get composer to checkout development branch source code from github, you
have to include the following repositories section in your root-level composer.json file.

```
  "repositories": [
    {
      "type": "git",
      "url":"https://github.com/CareSet/Zermelo.git"
    },
    {
      "type": "git",
      "url": "https://github.com/CareSet/ZermeloBladeTabular.git"
    },
    {
      "type": "git",
      "url": "https://github.com/CareSet/ZermeloBladeCard.git"
    },
    {
      "type": "git",
      "url": "https://github.com/CareSet/ZermeloBladeTreeCard.git"
    },
    {
      "type": "git",
      "url": "https://github.com/CareSet/ZermeloBladeGraph.git"
    }
  ],
```
