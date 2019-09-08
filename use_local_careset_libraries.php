<?php
/*
	This is a script to facilicate development of this project alongside the CareSet DURC and Zermelo projects that form the basis of its functionality. 
	Essentially the method makes "sodt links" to local directories rather than leveraging data from composer in the ./vendor directory...
	to facilitate that, it simply detects which careset libraries have been installed locally
	and assumes that the local copies should be used.
	
	No one who is not co-developing or at least forking the careset tools should be doing this.

	IT is critical when using this script to remember that local copies of these libraries must be updated to be effective...
	
*/


$repos = [
	'ftrotter',
	'docgraph',
	'careset',
	];

foreach($repos as $this_repo){

	$vendor_dir = "./vendor/$this_repo/";

	if(file_exists($vendor_dir)){

		// get the listing of the careset composer directory, without the dots https://www.php.net/manual/en/function.scandir.php#107215
		$local_dir_listing = array_diff(scandir($vendor_dir),['..','.']);

		//the differnce between the github projec names and the composer directory names is just letter case...
		//so we can quickly find the mapping between what we have under vendor and what we have under ../ by also ignoring case...

		$parent_dir_list = array_diff(scandir('../'),['..','.']);

		$ln_map = [];

		foreach($parent_dir_list as $this_parent_dir){
			$lower_case_dir_name = strtolower($this_parent_dir);
			if(in_array($lower_case_dir_name,$local_dir_listing)){
				//then we have a match...
				$ln_map["vendor/$this_repo/$lower_case_dir_name"] = realpath("../$this_parent_dir");
			}
		}


		$cmd = [];

		foreach($ln_map as $vendor_dir => $local_library_dir){

			$cmd["removing the current $vendor_dir"] = "rm -rf $vendor_dir";
			$cmd["linking $vendor_dir to $local_library_dir"] = "ln -s $local_library_dir $vendor_dir"; 

		}

		foreach($cmd as $label => $to_run){
			echo "Running $label with\n\t$to_run\n";
			system($to_run);
		}
	}//end if exists
}
