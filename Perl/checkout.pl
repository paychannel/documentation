#!/usr/bin/perl
use Digest::MD5 qw(md5_base64);
use MIME::Base64;

# Secret key
$key = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

my %fields; # Add form’s fields into array of form fields

$fields{"RDI_MERCHANT_ID"}    = "1600000000";
$fields{"RDI_PAYMENT_AMOUNT"} = "100.00";
$fields{"RDI_CURRENCY_ID"}    = "840";
$fields{"RDI_PAYMENT_NO"}     = "179";
$fields{"RDI_DESCRIPTION"}    =  encode_base64("Order #179 for Demo Customer");
$fields{"RDI_SUCCESS_URL"}    = "https://example.com/success.php";
$fields{"RDI_FAIL_URL"}       = "https://example.com/fail.php";

# Message production by concatenation of form field values 
# sorted by field name in ascending order.
my $fieldValues = "";

for my $key (sort { lc($a) cmp lc($b) } keys %fields) {
	$fieldValues .= $fields{$key};
}

# RDI_SIGNATURE parameter production by
# digital signature production of produced message
# the MD5 and presenting it in Base64
my $signature = md5_base64($fieldValues, $key) . '==';

# Add RDI_SIGNATURE parameter into array of form’s fields
$fields{"RDI_SIGNATURE"} = $signature;

# Production of payment form’s HTML-code
print "Content-type: text/html; charset=UTF-8nn";
print "<form action=\"https://payment.paychannel.cc\" method=\"POST\">";

for my $key (sort { lc($a) cmp lc($b) } keys %fields) {
	print "$key: <input name=\"$key\" value=\"$fields{$key}\">";
}

print "<input type=\"submit\"></form>n";
