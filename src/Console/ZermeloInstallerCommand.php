<?php

namespace CareSet\ZermeloInstaller\Console;

use Illuminate\Console\Command;

class ZermeloInstallerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:chapple
                    {--database= : Pass in the database name}
                    {--force : Overwrite existing views and database by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all available zermelo packages';

    public function handle()
    {
        $this->info("Installing Zermelo API engine");
        // Artisan::call('install:zermelobladegraph');
    }

}
