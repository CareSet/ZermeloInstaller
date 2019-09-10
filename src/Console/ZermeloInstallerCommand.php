<?php

namespace CareSet\ZermeloInstaller\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

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

    protected $zermelo_install_commands = [
        'install:zermelo_api',
        'install:zermelobladetabular',
        'install:zermelobladecard',
        "install:zermelobladetree"
    ];

    public function handle()
    {
        $this->info("Installing Zermelo API engine");
        foreach ($this->zermelo_install_commands as $zermelo_install_command) {
            if (array_has(Artisan::all(), $zermelo_install_command)) {
                $this->info("Running `$zermelo_install_command`");
                Artisan::call($zermelo_install_command, [], $this->getOutput());
            } else {
                $this->line("$zermelo_install_command not available");
            }
        }
    }
}
