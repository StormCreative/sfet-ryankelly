<?
// Database variables
$host = "localhost"; //database location
$user = "toplist_admin"; //database username
$pass = "r00t3dy0un0wm0f0"; //database password
$db_name = "toplist_community"; //database name

// PayPal settings
$test_mode = true;
if ($test_mode) {
	$paypal_email = 'danny@xwebdev.com';
} else {
	$paypal_email = 'paypal@top-list.co.uk';
}
$return_url = 'https://www.top-list.co.uk/bidding/payment/complete';
$cancel_url = 'https://www.top-list.co.uk/bidding/payment/cancelled';
$notify_url = 'https://www.top-list.co.uk/ipn/paypal.php';

// Include Functions
include("functions.php");

//Database Connection
$link = mysql_connect($host, $user, $pass);
mysql_select_db($db_name);

// Check if paypal request or response
if (!isset($_POST["txn_id"]) && !isset($_POST["txn_type"])){

	// Firstly Append paypal account to querystring
	$querystring .= "?business=".urlencode($paypal_email)."&";	
	
	// Append amount& currency (£) to quersytring so it cannot be edited in html
	
	//The item name and amount can be brought in dynamically by querying the $_POST['item_number'] variable.
	$querystring .= "item_name=".urlencode($item_name)."&";
	$querystring .= "item_number=".urlencode($item_number)."&";
	$querystring .= "amount=".urlencode($item_amount)."&";
	$querystring .= "cmd=_xclick&";
	$querystring .= "no_note=1&";
	$querystring .= "lc=UK&";
	$querystring .= "currency_code=USD&";
	$querystring .= "bn=PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest&";
	//$querystring .= "payer_email=&";
	//$querystring .= "first_name=&";
	//$querystring .= "last_name=&";
	
	//loop for posted values and append to querystring
	foreach($_POST as $key => $value){
		$value = urlencode(stripslashes($value));
		$querystring .= "$key=$value&";
	}
	
	// Append paypal return addresses
	$querystring .= "return=".urlencode(stripslashes($return_url))."&";
	$querystring .= "cancel_return=".urlencode(stripslashes($cancel_url))."&";
	$querystring .= "notify_url=".urlencode($notify_url);
	
	// Append querystring with custom field
	//$querystring .= "&custom=".USERID;
	
	// Redirect to paypal IPN
	if ($test_mode) {
		header('Location: https://www.sandbox.paypal.com/cgi-bin/webscr'.$querystring);
	} else {
		header('Location: https://www.paypal.com/cgi-bin/webscr'.$querystring);
	}
	exit();
} else {
	/*
	// Response from Paypal

	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';
	foreach ($_POST as $key => $value) {
		$value = urlencode(stripslashes($value));
		$value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i','${1}%0D%0A${3}',$value);// IPN fix
		$req .= "&$key=$value";
	}
	
	// assign posted variables to local variables
	$data['item_name']			= $_POST['item_name'];
	$data['item_number'] 		= $_POST['item_number'];
	$data['payment_status'] 	= $_POST['payment_status'];
	$data['payment_amount'] 	= $_POST['mc_gross'];
	$data['payment_currency']	= $_POST['mc_currency'];
	$data['txn_id']				= $_POST['txn_id'];
	$data['receiver_email'] 	= $_POST['receiver_email'];
	$data['payer_email'] 		= $_POST['payer_email'];
	$data['custom'] 			= $_POST['custom'];
	
	// post back to PayPal system to validate
	$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
	
	if ($test_mode) {
		$fp = @fsockopen('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
	} else {
		$fp = @fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);
	}
	if (!$fp) {
		// HTTP ERROR
		@mail("admin@top-list.co.uk", "PAYPAL DEBUGGING", "HTTP ERROR, ".$errno."\n\r".$errstr);
	} else {
		fputs($fp, $header . $req);
		while (!feof($fp)) {
			$res = fgets($fp, 4096);
			if (strcmp($res, "VERIFIED") == 0) {
				// Used for debugging
				@mail("admin@top-list.co.uk", "PAYPAL DEBUGGING", "Verified Response<br />data = <pre>".print_r($post, true)."</pre>");
						
				// Validate payment (Check unique txnid & correct price)
				$valid_txnid = check_txnid($data['txn_id']);
				$valid_price = check_price($data['payment_amount'], $data['item_number']);
				// PAYMENT VALIDATED & VERIFIED!
				if($valid_txnid && $valid_price){				
					$orderid = updatePayments($data);		
					if($orderid){					
						// Payment has been made & successfully inserted into the Database
						@mail("admin@top-list.co.uk", "PAYPAL DEBUGGING", "Success<br />data = <pre>".print_r($post, true)."</pre>");
					} else {							
						// E-mail admin or alert user
						//@mail("admin@top-list.co.uk", "PAYPAL DEBUGGING", "Success, Database Error<br />data = <pre>".print_r($post, true)."</pre>");
					}
				} else {					
					// E-mail admin or alert user
					//@mail("admin@top-list.co.uk", "PAYPAL DEBUGGING", "Success, Invalid Data Response<br />data = <pre>".print_r($post, true)."</pre>");
				}						
			
			} else if (strcmp ($res, "INVALID") == 0) {
				// Used for debugging
				//@mail("admin@top-list.co.uk", "PAYPAL DEBUGGING", "Invalid Response<br />data = <pre>".print_r($post, true)."</pre>");
			}
		}
		@mail("admin@top-list.co.uk", "PAYPAL DEBUGGING", "PAYPAL ACCESSED!!!\n Data = <pre>".print_r($post, true)."</pre>");
		fclose ($fp);
	}*/
	
	
	/*
	$req = 'cmd=_notify-validate';

	// go through each of the POSTed vars and add them to the variable
	foreach ($_POST as $key => $value) {
		$value = urlencode(stripslashes($value));
		$req .= "&$key=$value";
	}
	
	// post back to PayPal system to validate
	$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
	
	// In a live application send it back to www.paypal.com
	// but during development you will want to use the paypal sandbox
	
	// comment out one of the following lines.
	// Be aware that some users have reported that Paypal Sandbox will only accept 
	// use of ssl on port 443 and not unencrypted port 80
	
	// Associate the file to the filename variable  
	$filename = 'ipn.log';
	
	// Open the file and read line by line 
	$fp = fopen("$filename", "r") or die("Couldn't open $filename"); 
	$mail_Body.= "Couldn't open $filename";
	while (!feof($fp)) {
		$line = fgets($fp); 
		if(strlen($line) < 2) continue; 
		$line_array = explode(" ", $line); 
		print count($line_array); 
		$line_array[7] = date("Y-m-d H:i:s", $line_array[7]); 
		$line_array[8] = $line_array[4] / $line_array[1]; 
		settype($line_array[8], "integer"); 
		$mail_Body.= "$line_array[7]\n"; 
		$mail_Body.= "$line_array[8]\n"; 
	} 
	fclose($fp); 
	$mail_From = "From: admin@top-list.co.uk";
	$mail_To = $email;
	$mail_Subject = "LOG";
	mail($mail_To, $mail_Subject, $mail_Body, $mail_From); 
	
	$fp = fsockopen('www.sandbox.paypal.com', 80, $errno, $errstr, 30);
	//$fp = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30);
	
	// or use port 443 for an SSL connection
	//$fp = fsockopen ('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
	
	if (!$fp) {
		// HTTP ERROR Failed to connect
		$mail_From = "From: admin@top-list.co.uk";
		$mail_To = $email;
		$mail_Subject = "HTTP ERROR";
		$mail_Body = $errstr;
		mail($mail_To, $mail_Subject, $mail_Body, $mail_From); 
	} else {
		fputs($fp, $header . $req);
		while (!feof($fp)) {
			$res = fgets ($fp, 1024);
			if (strcmp ($res, "VERIFIED") == 0) {
				$item_name = $_POST['item_name'];
				$item_number = $_POST['item_number'];
				$item_colour = $_POST['custom'];  
				$payment_status = $_POST['payment_status'];
				$payment_amount = $_POST['mc_gross'];         //full amount of payment. payment_gross in US
				$payment_currency = $_POST['mc_currency'];
				$txn_id = $_POST['txn_id'];                   //unique transaction id
				$receiver_email = $_POST['receiver_email'];
				$payer_email = $_POST['payer_email'];
	
				// use the above params to look up what the price of "item_name" should be.
				//$amount_they_should_have_paid = lookup_price($item_name); // you need to create this code to find out what the price for the item they bought really is so that you can check it against what they have paid. This is an anti hacker check.
				
				// the next part is also very important from a security point of view
				// you must check at the least the following...
	
				if (($payment_status == 'Completed') &&   //payment_status = Completed
					($receiver_email == $paypal_email) &&   // receiver_email is same as your account email
					//($payment_amount == $amount_they_should_have_paid ) &&  //check they payed what they should have
					($payment_currency == "GBP")) { // &&  // and its the correct currency 
					//(!txn_id_used_before($txn_id))) {  //txn_id isn't same as previous to stop duplicate payments. You will need to write a function to do this check.
					//        uncomment this section during development to receive an email to indicate whats happened
					$mail_To = "admin@top-list.co.uk";
					$mail_Subject = "completed status received from paypal";
					$mail_Body = "completed: $item_number  $txn_id";
					mail($mail_To, $mail_Subject, $mail_Body);
				} else {
					//
					// paypal replied with something other than completed or one of the security checks failed.
					// you might want to do some extra processing here
					//
					//in this application we only accept a status of "Completed" and treat all others as failure. You may want to handle the other possibilities differently
					//payment_status can be one of the following
					//Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for
					//                           Completed the transaction that was reversed have been returned to you.
					//Completed:            The payment has been completed, and the funds have been added successfully to your account balance.
					//Denied:                 You denied the payment. This happens only if the payment was previously pending because of possible
					//                            reasons described for the PendingReason element.
					//Expired:                 This authorization has expired and cannot be captured.
					//Failed:                   The payment has failed. This happens only if the payment was made from your customerâ€™s bank account.
					//Pending:                The payment is pending. See pending_reason for more information.
					//Refunded:              You refunded the payment.
					//Reversed:              A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from
					//                          your account balance and returned to the buyer. The reason for the
					//                           reversal is specified in the ReasonCode element.
					//Processed:            A payment has been accepted.
					//Voided:                 This authorization has been voided.
					//
					//
					// we will send an email to say that something went wrong
					$mail_To = "admin@top-list.co.uk";
					$mail_Subject = "PayPal IPN status not completed or security check fail";
					//
					//you can put whatever debug info you want in the email
					//
					$mail_Body = "Something wrong. \n\nThe transaction ID number is: $txn_id \n\n Payment status = $payment_status \n\n Payment amount = $payment_amount";
					mail($mail_To, $mail_Subject, $mail_Body);
				}
			} else if (strcmp ($res, "INVALID") == 0) {
				//
				// Paypal didnt like what we sent. If you start getting these after system was working ok in the past, check if Paypal has altered its IPN format
				//
			} else {
				$mail_To = "admin@top-list.co.uk";
				$mail_Subject = "PayPal - Invalid IPN ";
				$mail_Body = "We have had an INVALID response. \n\nThe transaction ID number is: $txn_id \n\n username = $username";
				mail($mail_To, $mail_Subject, $mail_Body);
			}
		}
		fclose ($fp);
	}*/
	
	$request = "cmd=_notify-validate"; 
	foreach ($_POST as $varname => $varvalue){
		$email .= "$varname: $varvalue\n";  
		if(function_exists('get_magic_quotes_gpc') and get_magic_quotes_gpc()){  
			$varvalue = urlencode(stripslashes($varvalue)); 
		}
		else { 
			$value = urlencode($value); 
		} 
		$request .= "&$varname=$varvalue"; 
	} 
	$ch = curl_init();
	if ($test_mode) {
		curl_setopt($ch, CURLOPT_URL, "https://www.sandbox.paypal.com/cgi-bin/webscr");
	} else {
		curl_setopt($ch, CURLOPT_URL, "https://www.paypal.com/cgi-bin/webscr");
	}
	curl_setopt($ch,CURLOPT_POST,true);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$request);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$result = curl_exec($ch);
	curl_close($ch);
	switch($result){
		case "VERIFIED":
			break;
		case "INVALID":
			break;
		default:
	}
	if ($result == 'VERIFIED') {
		$item_name = $_POST['item_name'];
		$item_number = $_POST['item_number'];
		$item_colour = $_POST['custom'];
		$payment_status = $_POST['payment_status'];
		if ($payment_status) {
			$pending_reason = $_POST['pending_reason'];
		}
		$payment_amount = $_POST['mc_gross'];         //full amount of payment. payment_gross in US
		$payment_currency = $_POST['mc_currency'];
		$txn_id = $_POST['txn_id'];                   //unique transaction id
		$receiver_email = $_POST['receiver_email'];
		$payer_email = $_POST['payer_email'];
		if ((strtolower($payment_status) == 'completed') && ($receiver_email == $paypal_email) && ($payment_currency == "USD") && ($item_name == 'Gold Bidding') && check_txnid($txn_id)) {
			//($payment_amount == $amount_they_should_have_paid ) &&  //check they payed what they should have
			//(!txn_id_used_before($txn_id))) {
			updatePayments($_POST);
			
			$mail_To = "admin@top-list.co.uk";
			$mail_Subject = "Completed status received from paypal";
			$mail_Body = "Completed: $item_number\nTransaction ID: $txn_id\nPaid: $$payment_amount";
			mail($mail_To, $mail_Subject, $mail_Body);
		} else {
			//Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for
			//                           Completed the transaction that was reversed have been returned to you.
			//Completed:            The payment has been completed, and the funds have been added successfully to your account balance.
			//Denied:                 You denied the payment. This happens only if the payment was previously pending because of possible
			//                            reasons described for the PendingReason element.
			//Expired:                 This authorization has expired and cannot be captured.
			//Failed:                   The payment has failed. This happens only if the payment was made from your customerâ€™s bank account.
			//Pending:                The payment is pending. See pending_reason for more information.
			//Refunded:              You refunded the payment.
			//Reversed:              A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from
			//                          your account balance and returned to the buyer. The reason for the
			//                           reversal is specified in the ReasonCode element.
			//Processed:            A payment has been accepted.
			//Voided:                 This authorization has been voided.
			
			if (isset($pending_reason)) {
				$pending_extra = "\n\n Pending Reason = $pending_reason";
			}
			
			// we will send an email to say that something went wrong
			$mail_To = "admin@top-list.co.uk";
			$mail_Subject = "PayPal IPN status not completed or security check fail";
			$mail_Body = "Something wrong. \n\nThe transaction ID number is: $txn_id \n\n Payment status = $payment_status $pending_extra \n\n Payment amount = $payment_amount";
			mail($mail_To, $mail_Subject, $mail_Body);
		}
	} else if ($result == 'INVALID') {
		$mail_To = "admin@top-list.co.uk";
		$mail_Subject = "PayPal - Invalid IPN";
		$mail_Body = "We have had an INVALID response. \n\nThe transaction ID number is: $txn_id \n\n Username = $username";
		mail($mail_To, $mail_Subject, $mail_Body);
	} else {
		$mail_To = "admin@top-list.co.uk";
		$mail_Subject = "PayPal - Error";
		$mail_Body = "We have had an ERROR response. \n\nThe transaction ID number is: $txn_id \n\n Username = $username";
		mail($mail_To, $mail_Subject, $mail_Body);
	}
}
?>