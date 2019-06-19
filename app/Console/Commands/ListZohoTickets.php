<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListZohoTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoho:get_ticket_json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump All Tickets to the console';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
        \App\ZohoDesk::listTickets();

    }
}
