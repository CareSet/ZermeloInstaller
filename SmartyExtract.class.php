<?php
/*
	A class that uses the Smarty Extract API to get an address from a single 
	block of address text. 

*/
require_once('vendor/autoload.php');
require_once('util/mysqli.php');

use SmartyStreets\PhpSdk\StaticCredentials;
use SmartyStreets\PhpSdk\ClientBuilder;
use SmartyStreets\PhpSdk\US_Extract\Lookup;
use SmartyStreets\PhpSdk\ArrayUtil;
use Dotenv\Dotenv;

class SmartyExtract {


	//saves all of the candidates to the trueaddress database...
	///and then returns the trueaddress_id of the last one...
	//this version assumes that the argument is an address.. in a single block
	function processBlockAddress($address_text){

		if(strlen(trim($address_text)) == 0){
			//fail
			return(0);
		}

		$dotenv = new \Dotenv\Dotenv(__DIR__);
		$dotenv->load();

		//you should have these in your .env...
       	 	$authId = getenv('SMARTY_AUTH_ID');
        	$authToken = getenv('SMARTY_AUTH_TOKEN');

		//echo "authid: $authId and authtoken: $authToken\n";

		$staticCredentials = new StaticCredentials($authId, $authToken);
        	$client = (new ClientBuilder($staticCredentials))->buildUSExtractApiClient();
	
        $lookup = new Lookup($address_text);
        $client->sendLookup($lookup);
        $result = $lookup->getResult();
        $metadata = $result->getMetadata();
//        print('Found ' . $metadata->getAddressCount() . " addresses.\n");
//        print($metadata->getVerifiedCount() . " of them were valid.\n\n");
        $addresses = $result->getAddresses();
 //       print("Addresses: \n**********************\n");
        foreach($addresses as $address) {
 //           print("\n\"" . $address->getText() . "\"\n");
 //           print("\nVerified? " . ArrayUtil::getStringValueOfBoolean($address->isVerified()));
            if (count($address->getCandidates()) > 0) {
 //               print("\nMatches:");
                foreach ($address->getCandidates() as $candidate) {
   //                 print("\n" . $candidate->getDeliveryLine1());
    //                print("\n" . $candidate->getLastLine() . "\n");
		    $trueaddress_id =  $this->trueaddressFromSmartyCandidate($candidate);
		    
			//lets use the first one..
			return($trueaddress_id);
                }

            } else {
		return(0); //fail
	    }
        }

	return(0); //got no results... also faile

	}

	//given a smarty php Candidate object..
	//find or save a trueaddress and return the trueaddress_id.
	public function  trueaddressFromSmartyCandidate($C){ //$c = candidate

 		$barcode = $C->getDeliveryPointBarcode();

		$get_existing_sql = "
SELECT 
	trueaddress.id AS trueaddress_id 
FROM address.trueaddress 
WHERE delivery_point_barcode = '$barcode';
";

		$result = f_mysql_query($get_existing_sql);
		while($row = mysqli_fetch_assoc($result)){
			$trueaddress_id  = $row['trueaddress_id'];
			//if we found a trueaddress... return it!!
			return($trueaddress_id);
		}


		$city_name = $C->getComponents()->getCityName();
		$u_city_name = strtoupper($city_name);
		$state_code = $C->getComponents()->getStateAbbreviation();
		
		$find_city_sql = "
SELECT 
	citys.id AS city_id
FROM address.citys 
JOIN address.states ON 
	states.id =
	citys.state_id
WHERE city_name = '$city_name' AND short_state = '$state_code'
";

		$result = f_mysql_query($find_city_sql);
		while($row = mysqli_fetch_assoc($result)){
			$city_id = $row['city_id'];
		}

		$default_city_id = 0; //not sure what this does anymore...

	$d = [];
 $d['delivery_point_barcode'] = $barcode;
 $d['city_id'] = $city_id;
 $d['default_city_id'] = $default_city_id;
 $d['addressee'] = $C->getAddressee();
 $d['delivery_line_1'] = $C->getDeliveryLine1();
 $d['delivery_line_2'] = $C->getDeliveryLine2();
 $d['last_line'] = $C->getLastLine();
 $d['urbanization'] = $C->getComponents()->getUrbanization();
 $d['primary_number'] = $C->getComponents()->getPrimaryNumber();
 $d['street_name'] = $C->getComponents()->getStreetName();
 $d['street_predirection'] = $C->getComponents()->getStreetPreDirection();
 $d['street_postdirection'] = $C->getComponents()->getStreetPostDirection();;
 $d['street_suffix'] = $C->getComponents()->getStreetSuffix();
 $d['secondary_number'] = $C->getComponents()->getSecondaryNumber();
 $d['secondary_designator'] = $C->getComponents()->getSecondaryDesignator();
 $d['extra_secondary_number'] = $C->getComponents()->getExtraSecondaryNumber();
 $d['extra_secondary_designator'] = $C->getComponents()->getExtraSecondaryDesignator();
 $d['pmb_designator'] = $C->getComponents()->GetPmbDesignator();
 $d['pmb_number'] = $C->getComponents()->getPmbNumber();
 $d['zipcode'] = $C->getComponents()->getZipcode();
 $d['zip_type'] = $C->getMetadata()->getZipType();
 $d['plus4_code'] = $C->getComponents()->getPlus4Code();
 $d['delivery_point'] = $C->getComponents()->getDeliveryPoint();
 $d['delivery_point_check_digit'] = $C->getComponents()->getDeliveryPointCheckDigit() ;
 $d['record_type'] = $C->getMetadata()->getRecordType();
 $d['county_fips'] = $C->getMetadata()->getCountyFips();
 $d['county_name'] = $C->getMetadata()->getCountyName();
 $d['carrier_route'] = $C->getMetadata()->getCarrierRoute();
 $d['congressional_district'] = $C->getMetadata()->getCongressionalDistrict();
 $d['building_default_indicator'] = $C->getMetadata()->getBuildingDefaultIndicator();
 $d['rdi'] = $C->getMetadata()->getRdi();
 $d['elot_sequence'] = $C->getMetadata()->getElotSequence();
 $d['elot_sort'] = $C->getMetadata()->getElotSort();
 $d['latitude'] = $C->getMetadata()->getLatitude();
 $d['longitude'] = $C->getMetadata()->getLongitude();
 $d['precision'] = $C->getMetadata()->getPrecision();
 $d['time_zone'] = $C->getMetadata()->getTimeZone();
 $d['utc_offset'] = $C->getMetadata()->getUtcOffset();
 if($C->getMetadata()->obeysDst()){
	$d['is_dst'] = 1;
 }else{
	$d['is_dst'] = 0;
 }
 $d['dpv_match_code'] = $C->getAnalysis()->getDpvMatchCode();
 $d['dpv_footnotes'] = $C->getAnalysis()->getDpvFootnotes();
 $d['dpv_cmra'] = $C->getAnalysis()->getCmra();
 $d['dpv_vacant'] = $C->getAnalysis()->getVacant();
 $active = $C->getAnalysis()->getActive();
 if(strtoupper($active) == 'Y'){
 	$d['active'] = 1;
 }else{
 	$d['active'] = 0;
 }

 $is_ews_match = $C->getAnalysis()->isEwsMatch();
 if(strtoupper($is_ews_match) == 'Y'){
 	$d['is_ews_match'] = 1;
 }else{
 	$d['is_ews_match'] = 0;
 }
 $d['footnotes'] = $C->getAnalysis()->getFootnotes();
 $d['lacslink_code'] = $C->getAnalysis()->getLacsLinkCode();
 $d['lacslink_indicator'] = $C->getAnalysis()->getLacsLinkIndicator();
 $is_suitelink_match = $C->getAnalysis()->isSuiteLinkMatch();
 if(strtoupper($is_suitelink_match) == 'Y'){
 	$d['is_suitelink_match'] = 1;
 }else{
 	$d['is_suitelink_match'] = 0;
 }
 $d['datasource_id'] = 16; //for EBB Address add


		//Ok, now we have packed all of the trueaddress detail into the $d array.
		//now we need to create an insert statement
		$insert_sql = "
INSERT INTO address.trueaddress (
";	
		$value_sql = " ) VALUES (";

		//the reason we have this in an array is so that we can escape simply..
		$c = '';
		foreach($d as $key => $value){
			$safe_value  = f_mysql_real_escape_string($value);

			$insert_sql .= "$c `$key`";

			$value_sql .= "$c '$safe_value'";

			$c = ',';
		}

		$sql = "$insert_sql $value_sql )";

		f_mysql_query($sql);
		//if we get here then we just inserted a new trueaddress into the database!!!
		//we need the id to return!!

		$trueaddress_id = f_mysql_insert_id();
		return($trueaddress_id);

	}

}


