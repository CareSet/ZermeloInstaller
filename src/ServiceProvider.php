<?php

namespace CareSet\ZermeloInstaller;

use Illuminate\Support\ServiceProvider as BaseProvider;
use CareSet\ZermeloBladeTabular\Console\ZermeloInstallerCommand;

class ServiceProvider extends BaseProvider
{
    public function register()
    {
        /*
         * Register our zermelo installer command, which runs all the
         * sub-packages' install commands
         */
        $this->commands([
            \CareSet\ZermeloInstaller\Console\ZermeloInstallerCommand::class
        ]);
    }
}
