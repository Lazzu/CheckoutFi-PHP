<?php

/*
 * Checkout.fi PHP Interface for API V1.7
 *
 * Based on http://checkout.fi/uploads/sopimukset/Checkout_1_4_rajapinta_api-v1.7.pdf
 *
 * Version 0.1.0
 *
 * Created by Lasse Numminen
 * http://lasse.pw/
 *
 * MIT Licence
 *
 **/

namespace CheckoutFi;

// Class for checkout.fi exceptions
class CheckoutException extends \Exception {}

abstract class CheckoutDataObject 
{
	public $merchant		= "";
	public $password		= "";

	protected $validNames = array();

	abstract public function CalculateMAC();

	// Get data from given array
	public function FromArray(array $arr)
	{
		foreach($arr as $key => $value)
		{
			$key = strtolower($key);

			if( ! in_array($key, $this->validNames) )
				continue;

			$this->{$key} = $value;
		}

		if( ! $this->Validate() )
		{
			// MAC from the array is not valid.
			throw new CheckoutException("MAC is not valid!");
		}
	}

	// Validate the given MAC by comparing to the MAC calculated from current data
	public function ValidMAC($mac)
	{
		// Calculate new mac from current data
		$tmpMac = $this->CalculateMAC();

		// Strict comparison.
		return $mac === $tmpMac;
	}

	// Validate this data object
	public function Validate()
	{
		// Validate current mac
		return $this->ValidMAC($this->mac);
	}
} // Class CheckoutDataObject

// Class for storing the checkout purchase data
class CheckoutPostData extends CheckoutDataObject
{
	public $version			= "0001";
	public $language		= "FI";
	public $country			= "FIN";
	public $currency		= "EUR";
	public $device			= "1";
	public $content			= "1";
	public $type			= "0";
	public $algorithm		= "2";
	public $stamp			= 0;
	public $amount			= 0;
	public $reference		= "";
	public $message			= "";
	public $return			= "";
	public $cancel			= "";
	public $reject			= "";
	public $delayed			= "";
	public $delivery_date	= "";
	public $firstname		= "";
	public $familyname		= "";
	public $address			= "";
	public $postcode		= "";
	public $postoffice		= "";
	public $status			= "";
	public $email			= "";
	public $phone			= "";
	public $mac				= "";

	protected $validNames = array();

	// Constructor for easy object generation
	public function __construct($merchant, $password)
	{
		$this->merchant	= $merchant; // merchant id
		$this->password	= $password; // security key (about 80 chars)

		$this->validNames = array("version", "language", "country", "currency", "device", "content",
			"type", "algorithm", "stamp", "amount", "reference", "message", "return", "cancel", "reject", 
			"delayed", "delivery_date", "firstname", "familyname", "address", "postcode", "postoffice", 
			"status", "email", "phone", "mac");			
	}

	// Calculate MAC from current data
	public function CalculateMAC()
	{
		return strtoupper(
			md5(
				$this->version . "+" .
				$this->stamp . "+" .
				$this->amount . "+" .
				$this->reference . "+" . 
				$this->message . "+" . 
				$this->language . "+" .
				$this->merchant . "+" .
				$this->return . "+" . 
				$this->cancel . "+" . 
				$this->reject . "+" . 
				$this->delayed . "+" . 
				$this->country . "+" . 
				$this->currency . "+" . 
				$this->device . "+" . 
				$this->content . "+" . 
				$this->type . "+" . 
				$this->algorithm . "+" . 
				$this->delivery_date . "+" . 
				$this->firstname . "+" . 
				$this->familyname . "+" . 
				$this->address . "+" . 
				$this->postcode . "+" . 
				$this->postoffice . "+" . 
				$this->password
			)
		);
	}

	// Get POST data
	public function GetPOSTData()
	{
		$post['VERSION']		= $this->version;
		$post['STAMP']			= $this->stamp;
		$post['AMOUNT']			= $this->amount;
		$post['REFERENCE']		= $this->reference;
		$post['MESSAGE']		= $this->message;
		$post['LANGUAGE']		= $this->language;
		$post['MERCHANT']		= $this->merchant;
		$post['RETURN']			= $this->return;
		$post['CANCEL']			= $this->cancel;
		$post['REJECT']			= $this->reject;
		$post['DELAYED']		= $this->delayed;
		$post['COUNTRY']		= $this->country;
		$post['CURRENCY']		= $this->currency;
		$post['DEVICE']			= $this->device;
		$post['CONTENT']		= $this->content;
		$post['TYPE']			= $this->type;
		$post['ALGORITHM']		= $this->algorithm;
		$post['DELIVERY_DATE']	= $this->delivery_date;
		$post['FIRSTNAME']		= $this->firstname;
		$post['FAMILYNAME']		= $this->familyname;
		$post['ADDRESS']		= $this->address;
		$post['POSTCODE']		= $this->postcode;
		$post['POSTOFFICE']		= $this->postoffice;
		$post['EMAIL']			= $this->email;
		$post['PHONE']			= $this->phone;

		$post['MAC'] = $this->CalculateMAC();

		return $post;
	}
}

class CheckoutReturnData extends CheckoutDataObject
{
	public $version			= "0001";
	public $stamp			= 0;
	public $reference		= "";
	public $payment 		= "";
	public $status			= 0;
	public $algorithm		= 0;
	public $mac				= "";

	protected $validNames = array();

	// The API doc reference says these are the valid indicators for successful payment
	protected $validPaidStatusNumbers = array(2, 4, 5, 6, 7, 8, 9, 10);

	// Constructor for easy object generation
	public function __construct($merchant, $password)
	{
		$this->merchant	= $merchant; // merchant id
		$this->password	= $password; // security key (about 80 chars)

		$this->validNames = array("version", "stamp", "reference", "payment", "status", "algorithm", "mac");

		$this->FromArray($_GET);			
	}

	// Calculate MAC from current data
	public function CalculateMAC()
	{
		return strtoupper(
			md5(
				$this->password 	. "&" .
				$this->version 		. "&" .
				$this->stamp 		. "&" .
				$this->reference 	. "&" .
				$this->payment 		. "&" .
				$this->status 		. "&" .
				$this->algorithm
			)
		);
	}

	public function IsPaid()
	{
		return in_array($this->status, $this->validPaidStatusNumbers);
	}
}

// Class for making checkout requests and creating data objects
class Checkout
{
	// MerchantID and security key
	protected $merchant;
	protected $password;

	public $PaymentURL = "";

	public function __construct($merchant, $password) 
	{
		$this->merchant	= $merchant; // merchant id
		$this->password	= $password; // security key (about 80 chars)
	}

	// Get new data object
	public function GetPostObject()
	{
		return new CheckoutPostData($this->merchant, $this->password);
	}

	// Get new return data object
	public function GetReturnObject()
	{
		return new CheckoutReturnData($this->merchant, $this->password);
	}

	// Are we returning from payment process?
	public function ReturningFromPayment()
	{
		return isset($_GET['MAC']);
	}

	/*
	 * returns payment information in XML
	 */
	public function GetCheckoutBankButtons(CheckoutPostData $data) 
	{
		$data->device = "10"; // Set device to accept XML

		// Get the xml data and convert to object
		$xml = simplexml_load_string($this->SendCheckoutPostData($data));

		// Check if succeeded
		if($xml === false)
			throw new CheckoutException("Checkout.fi buttons fetch failed. XML Not valid.");

		// Save the payment URL
		$this->PaymentURL = $xml->paymentURL;

		// Return the button data
		return $xml->payments->payment->banks;
	}
	
	private function SendCheckoutPostData(CheckoutPostData $data) {

		$post = $data->GetPOSTData();

		$options = array(
				CURLOPT_POST 		=> 1,
				CURLOPT_HEADER 		=> 0,
				CURLOPT_URL 		=> 'https://payment.checkout.fi',
				CURLOPT_FRESH_CONNECT 	=> 1,
				CURLOPT_RETURNTRANSFER 	=> 1,
				CURLOPT_FORBID_REUSE 	=> 1,
				CURLOPT_TIMEOUT 	=> 20,
				CURLOPT_POSTFIELDS 	=> http_build_query($post)
		);
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
	    curl_close($ch);

	    return $result; 
	}
	
}  // class Checkout

