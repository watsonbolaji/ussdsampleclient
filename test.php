<?php
// We begin by reading the HTTP request body contents.
// Since we expect is to be in JSON format, let's parse as well.
$ussdRequest = json_decode(@file_get_contents('php://input'));
// Our response object. We shall use PHP's json_encode function
// to convert the various properties (we'll set later) into JSON.
$ussdResponse = new stdclass;
// Check if the decoding of the HTTP request body into JSON was
// successful. You can use json_last_error to get the exact nature of
// the error in event the decoding failed. I'll skip that here.
if ($ussdRequest != NULL)
switch ($ussdRequest->Type) {
	
	// Initiation request. This is the first type of request every
	// USSD application will receive. So let's display our main menu.
	case 'Initiation':
		$ussdResponse->Message =
			"Welcome to Freebie Service.\n" .
			"1. Free Food\n2. Free Drink\n3. Free Airtime";
		$ussdResponse->Type = 'Response';
		break;
	
	// Response request. This is where all other interactions occur.
	// Every time the mobile subscriber responds to any of our menus,
	// this will be the type of request we shall receive.
	case 'Response':
		switch ($ussdRequest->Sequence) {
			
			// Menu selection. Note that everytime we receive a request
			// in a particular session, the Sequence will increase by 1.
			// Sequence number 1 was that of the initiation request.
			case 2:
				$items = array('1' => 'food', '2' => 'drink', '3' => 'airtime');
				if (isset($items[$ussdRequest->Message])) {
					$ussdResponse->Message = 'Are you sure you want free '
						. $items[$ussdRequest->Message] . "?\n1. Yes\n2. No";
					$ussdResponse->Type = 'Response';
					$ussdResponse->ClientState = $items[$ussdRequest->Message];
				} else {
					$ussdResponse->Message = 'Invalid option.';
					$ussdResponse->Type = 'Release';
				}
				break;
			
			// Order confirmation. Here the user has responded to our
			// previously sent menu (i.e. Are you sure you want...)
			// Note that we saved the option the user selected in our
			// previous dialog into the ClientState property.
			case 3:
				switch ($ussdRequest->Message) {
					case '1':
						$ussdResponse->Message =
							'Thank you. You will receive your free '
							. $ussdRequest->ClientState . ' shortly.';
						break;
					case '2':
						$ussdResponse->Message = 'Order cancelled.';
						break;
					default:
						$ussdResponse->Message = 'Invalid selection.';
						break;
				}
				$ussdResponse->Type = "Release";
				break;
			
			// Unexpected request. If the code here should ever
			// execute, it means the request is probably forged.
			default:
				$ussdResponse->Message = 'Unexpected request.';
				$ussdResponse->Type = 'Release';
				break;
		}
		break;
		
	// Session cleanup.
	// Not much to do here.
	default:
		$ussdResponse->Message = 'Duh.';
		$ussdResponse->Type = 'Release';
		break;
}
// An error has occured.
// Probably the request JSON could not be parsed.
else {
	$ussdResponse->Message = 'Invalid USSD request.';
	$ussdResponse->Type = 'Release';
}
// Let's set the HTTP content-type of our response, encode our
// USSD response object into JSON, and flush the output.
header('Content-type: application/json; charset=utf-8');
echo json_encode($ussdResponse);