var iconv = require('iconv-lite');
var crypto = require('crypto');

var key = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

var fields = {
	RDI_MERCHANT_ID: '1600000000',
	RDI_PAYMENT_AMOUNT: '100.00',
	RDI_CURRENCY_ID: '840',
	RDI_PAYMENT_NO: '179',
	RDI_DESCRIPTION: 'BASE64:' + new Buffer('Order #179 for Demo Customer').toString('base64'),
	RDI_EXPIRED_DATE: '2019-12-31T23:59:59',
	RDI_SUCCESS_URL: 'https://example.com/success.php',
	RDI_FAIL_URL: 'https://example.com/fail.php',
};

var comparator = function(a, b){
	var a = a.toLowerCase();
	var b = b.toLowerCase();
	return a > b ? 1 : a < b ? -1 : 0;
};

var createInput = function(name, value){
	return '<input name="' + name + '" value="' + value + '">';
};

var inputs = '';
var values = '';

Object.keys(fields).sort(comparator).forEach(function(name){
	var value = fields[name];
	if (Array.isArray(value)) {
		values += value.sort(comparator).join('');
		inputs += value.map(function(val){ return createInput(name, val); }).join('');
	}
	else {
		values += value;
		inputs += createInput(name, value);
	}
});

inputs += createInput('RDI_SIGNATURE', crypto.createHash('md5').update(iconv.encode(values + key, 'win1251')).digest('base64'));

console.log('<form method="POST" action="https://payment.paychannel.cc" accept-charset="UTF-8">' + inputs + '<input type="submit"></form>');
