using System;
using System.Web;
using System.Text;
using System.Security.Cryptography;
using System.Collections.Generic;

public class PaymentForm : IHttpHandler {
	public void ProcessRequest(HttpContext context) {
		// Secret key
		string merchantKey = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"

		// Add form’s fields into array of form fields
		SortedDictionary<string, string=""> formField 
		  = new SortedDictionary<string, string="">();

		formField.Add("RDI_MERCHANT_ID",    "1600000000");
		formField.Add("RDI_PAYMENT_AMOUNT", "100.00");
		formField.Add("RDI_CURRENCY_ID",    "840");
		formField.Add("RDI_PAYMENT_NO",     "179");
		formField.Add("RDI_DESCRIPTION",    "BASE64:" + Convert.ToBase64String(Encoding.UTF8.GetBytes("Order #179 for Demo Customer")));
		formField.Add("RDI_SUCCESS_URL",    "https://example.com/success.php");
		formField.Add("RDI_FAIL_URL",       "https://example.com/success.php");

		// RDI_SIGNATURE parameter production by
		// digital signature production of produced message
		// the MD5 and presenting it in Base64

		StringBuilder signatureData = new StringBuilder();
		foreach (string key in formField.Keys) {
			signatureData.Append(formField[key]);
		}

		// RDI_SIGNATURE parameter production by
		// digital signature production of produced message
		// the MD5 and presenting it in Base64
		string message = signatureData.ToString() + merchantKey;
		Byte[] bytes = Encoding.GetEncoding(1251).GetBytes(message);
		Byte[] hash = new MD5CryptoServiceProvider().ComputeHash(bytes);
		string signature = Convert.ToBase64String(hash);

		// Add RDI_SIGNATURE parameter into array of form’s fields
		formField.Add("RDI_SIGNATURE", signature);

		// Production of payment form’s HTML-code
		StringBuilder output = new StringBuilder();

		output.AppendLine("<form method=\"POST\" action=\"https://payment.paychannel.cc\">");

		foreach (string key in formField.Keys) {
			output.AppendLine(String.Format("{0}: <input name=\"{0}\" value=\"{1}\">", key, formField[key]));
		}

		output.AppendLine("<input type=\"submit\"></form>");

		context.Response.ContentType = "text/html; charset=UTF-8";
		context.Response.Write(output.ToString());
	}
}</string,></string,>
