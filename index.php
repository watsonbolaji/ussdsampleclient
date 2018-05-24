<?php

// We begin by reading the HTTP request body contents.
// Since we expect is to be in JSON format, let's parse as well.

//Needed for CORS implementation on PHP
//header("Access-Control-Allow-Origin: http://apps.smsgh.com");
//header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
//header('Content-type: application/json; charset=utf-8');
$ussdRequest = json_decode(@file_get_contents('php://input'));
debug("We got: ".file_get_contents("php://input"));

// Our response object. We shall use PHP's json_encode function
// to convert the various properties (we'll set later) into JSON.
$ussdResponse = new stdclass;

// Check if the decoding of the HTTP request body into JSON was
// successful. You can use json_last_error to get the exact nature of
// the error in event the decoding failed. I'll skip that here.  !=
if ($ussdRequest != NULL){

	switch ($ussdRequest->Type) {

		// Initiation request. This is the first type of request every
		// USSD application will receive. So let's display our main menu.
		case 'Initiation':
			$ussdResponse->Message =
				'Welcome to GSK, Select Drug Class:
				1 Anti-biotics
				2 Anti-Malaria
				3 Vaccines';
			$ussdResponse->Type = 'Response';
			break;

		// Response request. This is where all other interactions occur.
		// Every time the mobile subscriber responds to any of our menus,
		// this will be the type of request we shall receive.
		case 'Response':

		//ClientState->DrugClass
		//ClientState->Drug
		//ClientState->Age
		//ClientState->Gender

			switch ($ussdRequest->Sequence) {

				// Menu selection. Note that everytime we receive a request
				// in a particular session, the Sequence will increase by 1.
				// Sequence number 1 was that of the initiation request.

				case 2:
					$items = array('1' => 'Anti-biotics', '2' => 'Anti-Malaria', '3' => 'Vaccines');

					$exact_drugs = array('1' => "1. Anti-biotics A\n2. Anti-biotics B\n3. Anti-biotics C\n4. Anti-biotics D"
						, '2' => "1. Anti-Malaria A\n2. Anti-Malaria B\n3. Anti-Malaria C\n4. Anti-Malaria D"
						, '3' => "1. Vaccine A\n2. Vaccine B\n3. Vaccine C\n4. Vaccine D");

					if (isset($items[$ussdRequest->Message])) {						
						$ussdResponse->Message = 'You’ve selected: '. $items[$ussdRequest->Message] . ", kindly choose exact drug\n".
						$exact_drugs[$ussdRequest->Message];
						$ussdResponse->Type = 'Response';
						$ussdResponse->ClientState = $items[$ussdRequest->Message];
					} else {
						$ussdResponse->Message = 'Invalid option.';
						//$ussdResponse->ClientState = $items[$ussdRequest->Message];
						$ussdResponse->Type = 'Release';
					}
					
					break;

				case 3:
					
					//determine the exact drug selected, using drugclass.
				    debug("ussdRequest->ClientState: ".print_r($ussdRequest->ClientState,TRUE)."\n");
					if(isset($ussdRequest->ClientState)){
						switch ($ussdRequest->ClientState) {
							case 'Anti-biotics':
							$drugs = array('1' => 'Anti-biotics A', '2' => 'Anti-biotics B', '3' => 'Anti-biotics C' , '4' => 'Anti-biotics D');
							break;
							case 'Anti-Malaria':
							$drugs = array('1' => 'Anti-Malaria A', '2' => 'Anti-Malaria B', '3' => 'Anti-Malaria C' , '4' => 'Anti-Malaria D');
							break;
							case 'Vaccines':
							$drugs = array('1' => 'Vaccine A', '2' => 'Vaccines B', '3' => 'Vaccines C' , '4' => 'Vaccines D');
							break;
							default:
							break;
						}
					}

					$age_str = "1. 00; 05 Years\n2. 05; 15 Years\n3 15; 30 Years\n4. 40 Above";
					$age = array('1' => '00; 05 Years', '2' => '05; 15 Years', '3' => '15; 30 Years', '4' => '40 Above');
					
					if (isset($drugs[$ussdRequest->Message])) {
						# code...
						$ussdResponse->Message = 'You’ve selected: '. $drugs[$ussdRequest->Message] . ", Select Patients’ Age\n".
						$age_str;
						$ussdResponse->Type = 'Response';
						$ussdResponse->ClientState = $drugs[$ussdRequest->Message];
					} else {
						$ussdResponse->Message = 'Invalid option.';
						//$ussdResponse->ClientState = $items[$ussdRequest->Message];
						$ussdResponse->Type = 'Release';
					}
					
					break;

				case 4:
					
					//determine the age selected.
				    debug("ussdRequest->ClientState: ".print_r($ussdRequest->ClientState,TRUE)."\n");	
				    $client_state = $ussdRequest->ClientState;				

					$gender_str = "1. Male\n2. Female";
					$age = array('1' => '00; 05 Years', '2' => '05; 15 Years', '3' => '15; 30 Years', '4' => '40 Above');
					
					if (isset($age[$ussdRequest->Message])) {
						# code...
						$ussdResponse->Message = 'You’ve selected: '. $age[$ussdRequest->Message] . ", kindly Select Gender\n".
						$gender_str;
						$ussdResponse->Type = 'Response';
						$ussdResponse->ClientState = $client_state.'|'.$age[$ussdRequest->Message];
					} else {
						$ussdResponse->Message = 'Invalid option.';
						//$ussdResponse->ClientState = $items[$ussdRequest->Message];
						$ussdResponse->Type = 'Release';
					}
					
					break;

				case 5:
				    //aa|ab|ac|ad
					//Anti-biotics C|15; 30 Years
				    //confirmation
					debug("ussdRequest->ClientState: ".print_r($ussdRequest->ClientState,TRUE)."\n");
					$client_state = $ussdRequest->ClientState;
					$myArray = explode('|', $client_state);


					$gender = array('1' => 'Male', '2' => 'Female');
					$confirmation_str = "\nPlease confirm request.\n1. All Correct\n2. Back To Edit Entry";
					
					if (isset($gender[$ussdRequest->Message])) {
							$ussdResponse->Message = 'You have selected: '. $myArray[0]. ' for '. $myArray[1]. ' '.  $gender[$ussdRequest->Message] . $confirmation_str;
							$ussdResponse->Type = 'Response';
							$ussdResponse->ClientState = $client_state.'|'.$gender[$ussdRequest->Message];
					}


				break;

				default:
						$ussdResponse->Message = 'Unexpected request.';
						$ussdResponse->Type = 'Release';
						break;	
				
							
				}	


         break;
		// Session cleanup.
		// Not much to do here.
		default:
			$ussdResponse->Message = 'Unexpected request.';
			$ussdResponse->Type = 'Release';
			break;
	}

}else {
	$ussdResponse->Message = 'Invalid user USSD request.';
	$ussdResponse->Type = 'Release';
}

// Let's set the HTTP content-type of our response, encode our
// USSD response object into JSON, and flush the output.
header('Content-type: application/json; charset=utf-8');
echo json_encode($ussdResponse);
debug("We sent: ".json_encode($ussdResponse));

function debug($text){
	//echo "$text\n";
	$file = fopen("gsk_ussd.log","a");
	//fwrite($file,"\n==============\n".date("Y-m-d H:i:s")."\n-------------\n".$text."\n");
	fwrite($file,date("Y-m-d H:i:s")."--".$text."\n");
	fclose($file);
}
