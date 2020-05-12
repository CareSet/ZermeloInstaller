<?php

namespace CareSet\ZermeloInstaller\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Arr;

class ZermeloInstallerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zermelo:install
                    {--database= : Pass in the database name}
                    {--force : Overwrite existing views and database by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all available zermelo packages';

    protected $zermelo_install_commands = [
        'zermelo:install_api',
        'zermelo:install_zermelobladetabular',
        'zermelo:install_zermelobladecard',
        'zermelo:install_zermelobladetreecard',
        'zermelo:install_zermelobladegraph',
    ];

    public function handle()
    {
        $force = $this->option('force');
        $this->info("Installing Zermelo API engine");

        foreach ($this->zermelo_install_commands as $zermelo_install_command) {
            if (Arr::has(Artisan::all(), $zermelo_install_command)) {
                $this->info("Running `$zermelo_install_command`");
                Artisan::call($zermelo_install_command, ['--force' => $force], $this->getOutput());
            } else {
                $this->line("$zermelo_install_command not available");
            }
        }
    }
}
