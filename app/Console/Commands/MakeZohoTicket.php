<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeZohoTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoho:ticket_test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a demo ticket';

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
        
	$dept_id = env('ZOHO_DEPT_ID');
	$contact_id = env('ZOHO_ROBOT_CONTACT_ID');

	$today = date("D M j G:i:s T Y");

	$subject  = "Robot Ticket Test Made $today";
	$description = "<h1> Header 1 </h1> <h2>Header 2 </h2> <h3> header 3</h3> <h4> Header 4 </h4> 
<p>This is a test of the robots ability to create tickets</p> 
this should appear <br> on <br> different lines<br>
<ul>
	<li> Test </li>
	<li> Happennig </li>
</ul>

<ol>
	<li>one</li>
	<li>two</li>
</ol>

";

        \App\ZohoDesk::makeTicket($dept_id,$contact_id,$subject,$description);


    }
}
