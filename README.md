CheckoutFi-PHP
==============

This is a better (in my opinion) implementation of the PHP example provided by Checkout.fi.

Main goal of this project is to create highly usable and well documented PHP interface for Checkout.fi payments.

Check the wiki for basic usage.

Features
--------

It currently supports the basic payment processing.

Future features
---------------

- Support for payment status queries from checkout.fi servers.
- Support for the legacy checkout.fi payment processing method (only if really needed)
- More perhaps? 

Changelog
---------

2013-10-08 - 0.1.1 - Added `CheckoutReturnData->IsDelayed()` and fetching buttons throws an exception if curl POST request fails.

2013-10-07 - 0.1.0 - First version
