# Using the Jeeb plugin for ZenCart

## Prerequisites

* Last Cart Version Tested: 1.5.1

You must have a Jeeb merchant account to use this plugin.  It's free to [sign-up for a Jeeb merchant account](https://jeeb.io/home).

## Installation

Download the zip file for this plugin, unzip the archive and copy the files into the ZenCart directory on your webserver.

## Configuration

* Create a signature at jeeb.io under your account.
* In Admin panel under "Modules > Payment > Jeeb" click Install.
* Fill out all configuration information:
  * Verify that the module is enabled.
  * Set the signature you created in step 1.
  * Choose which environment you want (Live/Test).
  * Set a Base currency(It usually should be the currency of your store) and Target Currency(It is a multi-select option. You can choose any cryptocurrency from the listed options.).
  * Set the language of the payment page (you can set Auto-Select to auto detecting manner).
  * Choose a sort order for displaying this payment option to visitors.  Lowest is displayed first.<br />

## Usage

When a shopping chooses the Bitcoin payment method, they will be presented with an order summary as the next step (prices are shown in whatever currency they've selected for shopping). Upon receiving their order, the system takes the shopper to a jeeb.io invoice where the user is presented with bitcoin payment instructions.  Once payment is received, a link is presented to the shopper that will take them back to your website.

In your Admin control panel, you can see the orders made with Bitcoins just as you would any other order.  The status you selected in the configuration steps above will indicate whether the order has been paid for.  
