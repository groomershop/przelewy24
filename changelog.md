# Change Log #

## [1.1.33] 15-03-2021
- fix missing last method bug

## [1.1.32] 15-03-2021
- fix validation

## [1.1.31] 15-01-2021
- change recurring cards to REST API
- fix compatibility with Magento 2.4

## [1.1.30] 03-06-2020

- fix of extra charge issue when adding order in admin panel

## [1.1.29] 12-05-2020

- frontend validation of terms of use acceptance (if rendering acceptance checkbox is enabled in admin panel)

## [1.1.28] 30-04-2020

- fix repeat payment button
- added new status of new order which will be visible on storefront

## [1.1.27] 11-03-2020

- fix redirect to przelewy with promoted payment method 
- fix extra charge calculation when shipping method is not selected
- fix extra charge for admin order create view

## [1.1.26] 18-02-2020

- fix for payment method screen (when payment methods are displayed as text) -  remembered card will no longer be escaped (it shouldn`t as it contains html)
- fix for one click payment

## [1.1.25] 10-02-2020

- tax and discount data added to przelewy24 order summary

## [1.1.24] 10-01-2020

- fix cache for product page

## [1.1.23] 16-10-2019

- fix change address in payment
- Remove zencard plugin 

## [1.1.22] 04-09-2019

- Add payment method 218

## [1.1.21] 26-04-2019

- Making compile command work with this extension. Files had to be autoloaded sooner than they used to, so require_once
  was added to registration.


## [1.1.20] 10-04-2019

- Support for Magento in Version 2.3.1 (workaround). CSRF validation should not be performed when P24 pings Magento app.

## [1.1.19] 18-12-2018

- "Total Paid" and "Total Refunded" has been fixed in the order view

## [1.1.18] 05-09-2018

- fix configure plugin in multistore
- fix saved payment cart in multistore
- fix refunding order

## [1.1.17] 20-07-2018

- fix link in email to complete payment in multistore
- default check in checkbox "Do not remember my cards" and fixed this

## [1.1.16] 20-07-2018

- fix link in email to complete payment
- fix translate email in multistore

## [1.1.15] 14-06-2018

- Added Czech translations.

## [1.1.14] 08-06-2018

- fixed link to payment in email.

## [1.1.13] 10-05-2018

- delayed payments fix.

## [1.1.12] 08-12-2017

- update shared-libraries, template fix.

## [1.1.11] 08-12-2017

- Security fixes.

## [1.1.10] 10.11.2017

- Security fixes.

## [1.1.9] 16.10.2017

- Fixed failure redirect
- Fixed OneClick redirect

## [1.1.8] 29.06.2017

- Fixed getting ZenCard scripts. The function getScript in ZenCard Api is triggered only if merchant has ZenCard enabled.

## [1.1.7] 23.02.2017

- Base language: EN
- Added Hungarian translations.

## [1.1.6] 28.11.2016 ##

- Fixed url builder for emails.

## [1.1.5] 14.10.2016 ##

- Added class `Przelewy24Product`.
- Prepare products cart for p24. 

## [1.1.4] 12.10.2016 ##

- Default payment methods.

## [1.1.3] 11.10.2016 ##

- Added pay info about required.

## [1.1.2] 05.10.2016 ##

- Fixed incorrect dependency.

## [1.1.1] 27.09.2016 ##

- Added javascript to graphical methods list allowing choosing a method.

## [1.1.0] 20.09.2016 ##

- Changed ZenCardApi class to use `number_format` function in setting `p24_amount_discount`.

## [1.0.9] 20.09.2016 ##

- Fixed custom default payment link email template - added default wrapper.

## [1.0.8] 19.09.2016 ##

- Fixed double zencard discount price.

## [1.0.7] 12.09.2016 ##

- Send zencard confirm always after created new order.
- New zencard library added.

## [1.0.6] 12.09.2016 ##

- Added zencard info to order view.

## [1.0.5] 09.09.2016 ##

- Fixed order summary view with discount. 

## [1.0.4] 02.09.2016 ##

- Dialcom/Przelewy/Model/Payment/Przelewy.php
- Added shipping cost,
- quantity, price - fixed format

