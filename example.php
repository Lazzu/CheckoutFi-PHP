<?php

require("checkout.php");

// Create Checkout object using the given MerchantID and secret
$co = new CheckoutFi\Checkout(375917, "SAIPPUAKAUPPIAS"); // merchantID and securitykey (normally about 80 chars)

// We are using date() and we do not want php to whine about it.
date_default_timezone_set("UTC");

// Check if we are returning from payment
if($co->ReturningFromPayment()) 
{ 
	echo '<h1>Checkout API example</h1>';
	//echo '<p><a href="xml2.txt">View sourcecode</a></p>';

	try {

		// Get the return object. This might throw CheckoutFi\CheckoutException
		$data = $co->GetReturnObject();

		// The method above has already checked the MAC, and if we got this far it must be valid.
		echo("<p>Checkout transaction MAC CHECK OK, payment status =  ");

		// Check if payment is OK
		if($data->IsPaid()) 
		{
			echo("Paid.");
		} 
		else
		{
			echo("Not paid.");
		}

		echo ("</p>");
	} 
	catch (CheckoutFi\CheckoutException $e)
	{
		echo("<p>Checkout transaction failed. Reason: " . $e->getMessage() . "</p>");
	}

	echo "<p><a href='http://localhost/checkoutfi/example.php'>Start again</a></p>";
	exit;
}



// Order information
$coData = $co->GetPostObject();

$coData->stamp			= time(); // unique timestamp
$coData->reference		= "12344"; // Order ID or something
$coData->message		= "Huonekalutilaus\nPaljon puita,&lehtiä ja muttereita";
$coData->return			= "http://localhost/checkoutfi/example.php?test=1";
$coData->delayed		= "http://localhost/checkoutfi/example.php?test=2";
$coData->amount			= "1000"; // price in cents
$coData->delivery_date	= date("Ymd");
$coData->firstname		= "Tero";
$coData->familyname		= "Testaaja";
$coData->address		= "Ääkköstie 5b3\nKulmaravintolan yläkerta";
$coData->postcode		= "33100";
$coData->postoffice		= "Tampere";
$coData->email			= "support@checkout.fi";
$coData->phone			= "0800 552 010";

// Empty array to avoid warnings and notices.
$buttons = array();

// Try to get payment buttons
try
{
	$buttons = $co->GetCheckoutBankButtons($coData); 
}
catch ( CheckoutFi\CheckoutException $e )
{
	echo($e->getMessage());
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Checkout maksudemo</title>
<style type="text/css">
.C1 {
	width: 180px;
	height: 120px;
	border: 1pt solid #a0a0a0;
	display: block;
	float: left;
	margin: 7px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
	clear: none;
	padding: 0;
}

.C1:hover {
	background-color: #f0f0f0;
	border-color: black;
}

.C1 form {
	width: 180px;
	height: 120px;
}

.C1 form span {
	display: table-cell;
	vertical-align: middle;
	height: 92px;
	width: 180px;
}

.C1 form span input {
	margin-left: auto;
	margin-right: auto;
	display: block;
	border: 1pt solid #f2f2f2;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
	padding: 5px;
	background-color: white;
}

.C1:hover form span input {
	border: 1pt solid black;
}

.C1 div {
	text-align: center;
	font-family: arial;
	font-size: 8pt;
}
</style>
</head>
<body>
	<h1>Checkout API example</h1>
	<h2>Maksunapit verkkosivulle, suositeltu tapa / XML API, Recommended
		method</h2>

	<?php foreach($buttons as $bankX):?>
	<?php foreach($bankX as $bank):?>
	<div class='C1'>
		<form action='<?=$bank['url'];?>' method='post'>
			<?php foreach($bank as $key => $value):?>
			<input type='hidden' name='<?=$key;?>' value='<?=htmlspecialchars($value);?>'>
			<?php endforeach;?>
			<span><input type='image' src='<?=$bank['icon'];?>'> </span>
			<div>
				<?=$bank['name'];?>
			</div>
		</form>
	</div>
	<?php endforeach;?>
	<?php endforeach;?>
	<hr style='clear: both;'>
	<?php /*
	<!--<h2>Erillinen maksusivu / vanha tapa / deprecated method</h2>
	<form action='https://payment.checkout.fi/' method='post'>
		<?php foreach($coObject as $field => $value):?>
		<input type='hidden' name='<?=$field;?>' value='<?=htmlspecialchars($value);?>'>
		<?php endforeach;?>
		<input type='submit' value='Siirry maksusivulle'>
	</form>--> */ ?>
</body>
</html>