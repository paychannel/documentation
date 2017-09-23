<?php
// Secret key of online store (set in your account)
$skey = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

// Function that returns result in Paychannel
function print_answer($result, $description) {
	print "RDI_RESULT=" . strtoupper($result) . "&";
	print "RDI_DESCRIPTION=" .urlencode($description);
	exit();
}

// Check of necessary parameters availability in POST request

if (!isset($_POST["RDI_SIGNATURE"]))
	print_answer("Retry", "Parameter RDI_SIGNATURE is absent.");

if (!isset($_POST["RDI_PAYMENT_NO"]))
	print_answer("Retry", "Parameter RDI_PAYMENT_NO is absent.");

if (!isset($_POST["RDI_ORDER_STATE"]))
	print_answer("Retry", "Parameter RDI_ORDER_STATE is absent.");

// Extraction of all the parametrers of POST-request, except RDI_SIGNATURE
foreach($_POST as $name => $value) {
	if ($name !== "RDI_SIGNATURE") $params[$name] = $value;
}

// Array sorting in ascending order of key names and join them
ksort($params, SORT_STRING); $values = "";

foreach($params as $name => $value) {
	//Conversion of the current encoding (UTF-8)
	//required only if the encoding is different from the store Windows-1251
	$value = iconv("utf-8", "windows-1251", $value);
	$values .= $value;
}

// Generating a signature to compare with RDI_SIGNATURE
$signature = base64_encode(pack("H*", md5($values . $skey)));

// Comparing generated signature with RDI_SIGNATURE
if ($signature == $_POST["RDI_SIGNATURE"]) {
	if (strtoupper($_POST["RDI_ORDER_STATE"]) == "ACCEPTED") {
		// TODO: Mark order as "paid" in store’s accounting system
		print_answer("Ok", "Order #" . $_POST["RDI_PAYMENT_NO"] . " is paid!");
	} else {
		// Something wrong, unknown order status received
		print_answer("Retry", "Unknown order status ". $_POST["RDI_ORDER_STATE"]);
	}
}else{
	// Signature does not match, probably you have changed online shop settings
	print_answer("Retry", "Wrong digital signature " . $_POST["RDI_SIGNATURE"]);
}
?>
