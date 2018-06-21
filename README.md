# BinaryOption

A simple BTC binary option smart contract using oraclize.it to retrieve BTC-USD price from coinbase.com.

The seller deploy the option contract with a total subscriable amount with an option price and direction (higher or lower).
The smart contract schedules an oraclize.it call after the option is ended and retrieves the spot price from coinbase.com to settle the option.
The buyer wins the option when the price is consistent with the direction and get twice of the invested amount. The seller claims the remaining amounts.

To create an option:

Deploy this smart contract with the following parameters, along with the total subscriable amount:

- optionName: name of the option
- optionPrice: price of the option in cents, e.g. 650000 for US$6500
- optionForHigherPrice: true if the option is for higher price, or false if for lower (this is for buyer's perspective, e.g. if true, the buyers win if the price is higher)
- activeTime: active time of the option in seconds starting from now. e.g. 3600 for 1 hour
- minSubscription: minimum subscription value in wei

Buyer calls subscribe() and send the amount to subcribe to the option.

After the option ended and settled, seller calls sellerClaim() to claim the remaining amount (and the winnings if seller won), and the buyer calls buyerClaim() to claim their winnings.

Events:

- OptionSubscribed(uint amount): someone subscribed to the option with amount
- OptionEnded(bool sellerWon): the option ended and settled
- OptionClaimed(uint amount): someone claimed the winning

# Web Front End

deploy.php and option.php for working with Metamask.

These are just a simple frontend with no need for a backend server (only require a server for serving the files as Metamask does not allow using local HTML files).

Usage:

The seller uses deploy.php to deploy an option.
After an option is successfully deployed, it produces a link to option.php with the contract address, and anyone (other than the seller) can subscribe to the option using the link.
After the option is ended, oracalize.it calls the smart contract to settle the contract. Then everyone who won the option can claim their winnings. The seller can claim the remaining amount.
Both deploy.php and option.php has a variable called 'supportedNetworkType' to designate which network to use. By default, it uses the Ropsten test network.

A test front end using the Ropsten test network is running at https://clover.kimicat.com/deploy.php and https://clover.kimicat.com/option.php.
