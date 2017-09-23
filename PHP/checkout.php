<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

// Secret key
$key = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

$fields = array(); 

$fields["RDI_MERCHANT_ID"]    = "1600000000";
$fields["RDI_PAYMENT_AMOUNT"] = "100.00";
$fields["RDI_CURRENCY_ID"]    = "840";
$fields["RDI_PAYMENT_NO"]     = "179";
$fields["RDI_DESCRIPTION"]    = "BASE64:".base64_encode("Order #179 for Demo Customer");
$fields["RDI_SUCCESS_URL"]    = "https://example.com/success.php";
$fields["RDI_FAIL_URL"]       = "https://example.com/fail.php";

// Message production by concatenation of form field values 
// sorted by field name in ascending order.
foreach($fields as $name => $val) {
	if(is_array($val)) {
		usort($val, "strcasecmp");
		$fields[$name] = $val;
	}
}

// RDI_SIGNATURE parameter production by 
// digital signature production of produced message 
// the MD5 and presenting it in Base64
uksort($fields, "strcasecmp");
$fieldValues = "";

foreach($fields as $value) {
	if(is_array($value)){
		foreach($value as $v) {
			$v = iconv("utf-8", "windows-1251", $v);
			$fieldValues .= $v;
		}
	}else{
		$value = iconv("utf-8", "windows-1251", $value);
		$fieldValues .= $value;
	}
}

$signature = base64_encode(pack("H*", md5($fieldValues . $key)));
$fields["RDI_SIGNATURE"] = $signature;

// Production of payment form’s HTML-code
print "<form action=\"https://payment.paychannel.cc\" method=\"POST\">";

foreach($fields as $key => $val) {
	if(is_array($val)) {
		foreach($val as $value) {
			print "$key: <input name=\"{$key}\" value=\"{$value}\" type=\"text\">";
		}
	}else{
		print "$key: <input name=\"{$key}\" value=\"{$val}\" type=\"text\">";
	}
}
print "<input type=\"submit\"></form>"; 
?>
