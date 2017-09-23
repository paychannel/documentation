# PayChannel Technical documentation
PayChannel is a safe, fast and secure way to accept online payments. At a checkout we only request minimum information (client’s name, card number, its expiration date and CVV code) to process a payment. Your customer only focuses on goods and services but not on a checkout process.

## Step 1. Payment form settings
Once the buyer have selected goods from the online store and created a shopping cart order, the online shop sends it to the order payment page.
The payment page must include a payment form indicating order parameters.

Payment forms contain the merchant ID, the amount and currency of the order, as well as links to the online store web-site, which will be sent to the buyer after successful or unsuccessful payment. 

Example:
```
<form method="POST" action="https://payment.paychannel.cc"> </form>
	<input name="RDI_MERCHANT_ID" value="1600000000">
	<input name="RDI_PAYMENT_AMOUNT" value="100.00">
	<input name="RDI_CURRENCY_ID" value="840">
	<input name="RDI_PAYMENT_NO" value="179">
	<input name="RDI_DESCRIPTION" value="Order #179 for Demo Customer">
	<input name="RDI_SUCCESS_URL" value="https://example.com/success.php">
	<input name="RDI_FAIL_URL" value="https://example.com/fail.php">
	<input type="submit">
</form>
```

## Step 2. Payment form protection
The online shop’s order parameters are transmitted to PayChannel via buyer’s web-browser, therefore, to prevent changes in the parameters on buyer’s side, the online store should make a digest of payment form fields.

The online store should add parameter RDI_SIGNATURE to the payment form, produced using your digital signature algorithm and online store’s “secret key”.

RDI_SIGNATURE parameter is produced by concatenation of the values of other parameters of the form sorted in alphabetical order of their names (not case sensitive) ending with the online store “secret key”. If the form contains multiple fields with identical names, such fields should be sorted in alphabetical order.

Concatenation of form’s field values and “secret key” (Windows-1251encoded) should be hashed using digital signature algorithm and it’s bytes should be encoded by Base64.

```
RDI_SIGNATURE = Base64(Byte(MD5(Windows1251(Sort(Params) + SecretKey))));
```

Make sure that all parameters, represented in the payment form, excluding RDI_SIGNATURE parameter, are used in digital signature production.

For example, if the submit button has a «name» attribute, the payment form will contain a «value» attribute for this button.

Make sure that your MD5 function returns byte array, not HEX representation.
If everything was done correctly, RDI_SIGNATURE parameter length will equal 24 symbols.
If RDI_DESCRIPTION parameter contains Cyrillic letters, make sure that form is sent to the server in UTF-8 encoding.
This form must have attribute accept-charset="UTF-8".

## Step 3. Processing of the payment result notification
Once the buyer completes the payment order, PayChannel performs POST-request to the «Data to send the results of transaction», indicated the in the online store settings. This request contains parameters of the payment form, information about the result of payment and some additional parameters:

| Parameter name | Description |
|-----------------------|-------------------------------------------------------------------------|
| RDI_MERCHANT_ID | Online store ID (Merchant ID). |
| RDI_PAYMENT_AMOUNT | Order amount |
| RDI_COMMISSION_AMOUNT | Commission amount |
| RDI_CURRENCY_ID | Currency ID (ISO 4217). |
| RDI_PAYMENT_NO | Order ID in online store’s accounting system. |
| RDI_ORDER_ID | Order ID in PayChannel accounting system. |
| RDI_DESCRIPTION | Order description. |
| RDI_CREATE_DATE | Date of creation of the order in Western European time zone (UTC+0). |
| RDI_ORDER_STATE | Order payment status: Accepted,— order is paid; |
| RDI_SIGNATURE | Payment notification signature produced using online shop “secret key”. |

The online store should process the request of the payment result notification and send a response. The processing request for the online store should happen the same way as if it had received a POST-request from a standard HTML-form,but instead of pages it should return the following line:
```
RDI_RESULT=OK
```

or

```
RDI_RESULT=RETRY&RDI_DESCRIPTION=Server is temporary unavailable
```

Parameter RDI_RESULT in store’s response should contain result of request processing and can accept one of the following values:
* OK - online store has processed the notification and accepted the order.
* RETRY - online store is unable to process notification at the moment. PayChannel will resend the request later.

In the online store’s response parameter RDI_DESCRIPTION it can contain a comment which will be stored in the logs of PayChannel. It is recommended to return it in case of an error. The value of this parameter must be encoded in UrlEncode:
```
#!/usr/bin/php
<php
function print_answer($result, $description) {
	print "RDI_RESULT=" . strtoupper($result) . "&";
	print "RDI_DESCRIPTION=" .urlencode($description);
	exit();
}
?>
```

For various reasons, PayChannel may not receive a response from the online store and send a second request, the online-shop must respond to a second request the same way as the first one.

### Verify data source
To make sure that the request came from PayChannel and the information received can be trusted, the online store has to calculate the digital signature of the request using his “secret key” and to compare it with the parameter RDI_SIGNATURE, received in the request.

This can be done using the algorithm described in section “Payment form Protection”, combining all the request parameters values (except RDI_SIGNATURE) in ascending order of their names with the online store’s “secret key” and produce a hash fingerprint of this value by your digital signature algorithm.

Example (PHP):
```
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
```
