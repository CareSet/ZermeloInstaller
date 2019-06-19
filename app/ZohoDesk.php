<?php


namespace App;


class ZohoDesk  {


	public static function listTickets(){

    		$auth_token = env('ZOHO_AUTH_TOKEN');
    		$org_id = env('ZOHO_ORG_ID');

		//thanks to https://github.com/thisvijay/zohodesk_api_samples/blob/on_progress/PHP/allTickets.php
    		$headers=array(
            		"Authorization: $auth_token",
            		"orgId: $org_id",
            		"contentType: application/json; charset=utf-8",
    		);
    
    		$params="limit=5&include=contacts,products"; //options as parameters
    
    		$url="https://desk.zoho.com/api/v1/tickets?$params";
    
    		$ch= curl_init($url);
    		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
    		curl_setopt($ch,CURLOPT_HTTPGET,TRUE);
    
    		$response= curl_exec($ch);
    		$info= curl_getinfo($ch);
    
    		if($info['http_code']==200){
			$pretty_response = json_encode(json_decode($response),JSON_PRETTY_PRINT);
			echo $pretty_response;
    		}else{
        		echo "Request not successful. Response code : ".$info['http_code']." <br>";
        		echo "Response : $response";
    		}
    
    		curl_close($ch);

	}


	public static function makeTicket($department_id = null,$contact_id = null,$subject  = null, $description = null){

		if(is_null($department_id)){
			echo "Error: cannot create a ticket without a department id";
			exit();
		}

		if(is_null($contact_id)){
			echo "Error: cannot create a ticket without a contact id";
			exit();
		}

		if(is_null($subject)){
			echo "Error: cannot create a ticket without a subject";
			exit();
		}

		if(is_null($description)){
			echo "Error: cannot create a ticket without a description";
			exit();
		}

    		$auth_token = env('ZOHO_AUTH_TOKEN');
    		$org_id = env('ZOHO_ORG_ID');

		echo "trying to create with org_id:$org_id  dept_id:$department_id contact_id:$contact_id auth_token:$auth_token

subject: $subject
description: $description
";

    		$ticket_data=array(
        		"departmentId" => $department_id,
        		"contactId" => $contact_id,
        		"subject" => $subject,
			"description" => $description,
    		);
    
    		$headers=array(
            		"Authorization: $auth_token",
            		"orgId: $org_id",
            		"contentType: application/json; charset=utf-8",
    		);
    
    		$url="https://desk.zoho.com/api/v1/tickets";
    
		//this syntax needs to go.
    		$ticket_data=(gettype($ticket_data)==="array")? json_encode($ticket_data):$ticket_data;
    
    		$ch= curl_init($url);
    		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
    		curl_setopt($ch,CURLOPT_POST,TRUE);
    		curl_setopt($ch, CURLOPT_POSTFIELDS,$ticket_data); //convert ticket data array to json
    
    		$response= curl_exec($ch);
    		$info= curl_getinfo($ch);
    
    		if($info['http_code']==200){
			$pretty_response = json_encode(json_decode($response),JSON_PRETTY_PRINT);
			echo $pretty_response;
    		}else{
        		echo "Request not successful. Response code : ".$info['http_code']." <br>";
        		echo "Response : $response";
    		}
    
    		curl_close($ch);


	} 


}
