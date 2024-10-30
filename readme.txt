=== Mollie for Contact Form 7 ===
Tags: contact form 7 mollie, PayPal, iDeal, payment form, Mollie, donate, donatie
Contributors: tsjippy
Requires at least: 4.0.0
Requires PHP: 5.2.4
Tested up to: 5.6.1
Stable tag: 1.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

An add-on for Contact Form 7 to connect to Mollie.

== Description ==

Add-on plugin for Contact Form 7 - adds three fields and a shortcode.

##Mollie Description
Add a (hidden) field: [text paymentdescription "Payment for This is my description"]

##Payment amount
The amount which needs to be payed.
Does accept shortcodes without brackets like so: 
	[amount* amount-734 "CF7_GET key='Amount'"]

##Payment Options
Optional field so that the user can select from available payment options. 
This field is filled automatically with all payment options from Mollie. 
You can also add extra options like "cash". 
If this field is not present, people have to select the payment option on the mollie website. 
If there is only one payment option available this will be automatically selected.

There are three Payment options types:
###Default
The type is "oneoff", ment for payment that occur once
###Recurring
This list all availbale payment options for recurring payments
###Initial payment
This lists all available payment options to do the first payment of a recurring payment.
This initial payment is needed to confirm the recurring payment.

If you want to do recurring payment and you have only one recurring payment option available, you can skip this the recurring field. 
Only an Initial payment field is enough.

##iDeal bankchoice:
If payment option iDeal is chosen, this field can be used to select the bank (issuer).

##frequency 
You can optionally add a field with name "frequency" to use with the recurring payment to set the payment frequency in months.

##chargedate
You can optionall add a date field with the name "chargedate" to use with the recurring payment to set the first chargedate.
##Shortcode for payment result
[payment_result]
This shortcode can be used on a custom redirect page or on the form page itself.

The shortcode can have a shortcode nested within it.
It accepts two arguments, both can include layout and pictures:
succes -- the succes message to be displayed 
fail -- the failure message to be displayed

On succes only the success message will be displayed.
In all other cases the nested content will be displayed.

You can include shortcodes just use $l for [ and $r for ]

Shortcode can thus be used like this:

[payment_result success = "Uw donatie is succesvol ontvangen. Hartelijk dank daarvoor! $lSome shortcode$r"  fail = "Uw donatie is mislukt, probeert u het alstublieft opnieuw."]

	[payment_result success = "Your payment was successful." fail = "Payment failed, try again."]

		Fill in the form below to pay.
		[contact-form-7 id="211" title="Paymentform"]  
		Thank you.
	[/payment_result]

In this case the contact form with id 211 will not be shown on successful payment, only the success message will.

All arguments are optional, you can also use the shortcode like this: [payment_result].
In that case the default messages will be:
'Payment was successful, thank you.'
'Payment failed, please try again.'

## Shortcode for payment overview
The short code [paymentstable] gives an oveview of all payments made.
It accepts the parameters: hide, header, paymenttype and columns
hide difines the columns no to shown
header changes the title
paymenttype define whether to show only subscriptions (paymenttype="subscription") or only one time payments (paymenttype="onetime")
columns defines custom column names.
status="paid" only shows payments with the status paid
search = "!Description='Some decription'" shows all payments excluding the one with "Some decription" as description
search = "Description='Some decription'" shows all payments with "Some decription" as description

example:
[paymentstable} hide="Time,OrderID,CustomerID,Times,PaymentID, Status,SubscriptionID" header=Custom table title" columns="ID,Naam,E-mail,Bedrag, Omschrijving,Frequentie,Start datum, Mogelijke acties" status="paid" search="!Description='Some description'"]

== Usage ==
## General Settings
Create a Mollie account, and paste the api into the side-wide api key field under "Mollie", or use it on the form specific settings tab.

Optionally define a redirect page.

## Examples
### One off payment
	[hidden paymentdescription "Payment for some description"]
	
	[submission_id_hidden orderid]

	<label> Donation amount
	[amount* amount-185 min:5]</label>

	<label> Payment method
	[paymentchoice paymentmethod-901 paymenttype:oneoff]</label>

	<label> Your bank brand
	[bankchoice bankchoice-22]</label>
### Recurring Payment with multiple recurring methods avaialble
	[hidden paymentdescription "Payment for some description"]

	<label> Donation amount
	[amount* amount-185 min:5]</label>

	<label> Payment method
	[paymentchoice paymentmethod-14 paymenttype:recurring]</label>

	<label> Payment method for first payment
	[paymentchoice paymentmethod-15 paymenttype:first]</label>

	<label> Your bank brand
	[bankchoice bankchoice-22]</label>

	<label> How often do you want to pay
	[select frequency "1" "3" "6" "12"] months</label>

	<label> When do you want to be charged for the first time?
	[date chargedate]</label>
### Minimal Recurring Payment
	[hidden paymentdescription "Payment for some description"]

	<label> Donation amount
	[amount* amount-185 min:5]</label>

	<label> Payment method for first payment
	[paymentchoice paymentmethod-15 paymenttype:first]</label>
	


== Installation ==

Installing Mollie for Contact Form 7 can be done either by searching for "Mollie for Contact Form 7" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org.
2. Upload the ZIP file through the "Plugins > Add New > Upload" screen in your WordPress dashboard.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Visit the settings screen and configure, as desired.

== Frequently Asked Questions ==

= Why are the payments not stored in a database table? =

Payments are already stored in the Mollie system, this is just a payment plugin, so no other options are included.

== Screenshots ==

1. The main api key field
2. Form specific settings
3. Payment amount field
4. Payment options field
5. Bank choice field

== Changelog ==
= 4.9.0 =
* Library update to 2.29.0
* Activation bug fix

= 4.8.0 =
* Library update to 2.24.0
* Contact Form 7 update

= 4.7.4 =
* Now possible to search in table 

= 4.7.3 =
* Now possible to change the payment interval

= 4.7.2 =
* now possible to use shorcodes within the payment handler shortcode
* Bugfix in the webhook
* Payments stus translation

= 4.7.1 =
* Small bugfix

= 4.7.0 =
* Clean payments table on Api reset
* Bugfix in recurring webhook call
* Add overview of payments per customer
* Add css
* Translated into Dutch


= 4.6.2 =
* Bugfix

= 4.6.1 =
* Fix in subscription frequency not being stored

= 4.6.0 =
* It is now possible to have more than 1 shortcode within the result shortcode

= 4.5.0 =
* Added the possibility to manually add payment mandates on the admin page

= 4.4.4 =
* Bugfix for recurring payments

= 4.4.3 =
* Added "include blank" for bank choice

= 4.4.2 =
* Added Contact Form class to bankchoice dropdown.

= 4.4.1 =
* Small bugfix

= 4.4.0 =
* Give payment options the same options as radio buttons:
** default:1 now works
* Added paymentchoice* to make it required to select a paymentoption
* Added the time to the payment table

= 4.3.2 =
* Bugfix

= 4.3.1 =
* Fixed amount validation for numbers with a comma

= 4.3.0 =
* Added status argument to table shortcode to filter on payment status
* Fixed bug in redirect url handling

= 4.2.5 =
* Bugfix for page redirect

= 4.2.4 =
* Added extra logging

= 4.2.3 =
* Fixed non-valid api bug

= 4.2.2 =
* Fixed deletion of subscriptions

= 4.2.1 =
* Fixed mandatory amount validation

= 4.2.0 =
* Possibility to edit a customer name and e-mail
* Bugfix when aving two hortcodes on the same page

= 4.1.5 =
* Bugfix

= 4.1.4 =
* add listener for an "orderid" field

= 4.1.3 =
* Fix in js

= 4.1.2 =
* Fixed bug in custom payment handler

= 4.1.1 =
* Added count as an option to the shortcode (count="true") will add a total amount row to the table. 

= 4.1.0 =
* Fix for amount field
* Added shortcode paymentstable

= 4.0.0 =
* Update to Mollie API 2.12.0
* Fix when having multiple fields with the same name
* Support for recurring payments, including cancelling and editing
* Payments get stored in a special database
	
= 3.1.1 =
* Redirecturl fix

= 3.1.0 =
* Fixed broken payments with decimal amounts.

= 3.0.3 =
* Fixed url payments: /?amount=XXX&formid=XXX&paymentoption=ideal&description=SOMEDESCRIPTION&paymenttype=first/oneoff

= 3.0.2 =
* Fix for global ai key

= 3.0.1 =
* Small bugfix in combination with conditional fields plugin

= 3.0.0 =
* Mollie API updated to 2.10.0
* Support for recurring payments

= 2.1.0 =
* Better layout of the payment options
* If only one payment option, the option is chcked by default

= 2.0.6 =
* Bug fix

= 2.0.5 =
* Bug fix for certain API keys

= 2.0.4 =
* Removed capital letters from field names

= 2.0.3 =
* Small textual changes

= 2.0.2 =
* Small bugfixes

= 2.0.0 =
* Public release.

= 1.5.0 =
* Added payment webhook.
* Added shortcode

= 1.0.0 =
* Initial release









