pragma solidity 0.4.20;
import "github.com/oraclize/ethereum-api/oraclizeAPI.sol";

/// @title Binary option for BTC using Oraclize.it to retrieve BTC-USD price from Coinbase
contract BinaryOption is usingOraclize {
    string public name;

    address public seller;
    uint public optionPrice;
    bool public optionForHigherPrice;
    uint public optionEndTime;

    uint public totalSellingAmount;
    uint public minSubscription;
    uint public totalSubscribedAmount;

    // current betting amounts for each bet and each player
    mapping(address => uint) public subscribedAmount;
    mapping(address => bool) public optionAlreadyClaimed;

    // wager ended
    bool public ended;
    uint public actualOptionPrice;
    bool public sellerWonTheOption;
    bool public sellerClaimedTheOption;
   
    event OptionSubscribed(uint amount);
    event OptionEnded(bool sellerWon);
    event OptionClaimed(uint amount);

    // create a simple wager
    function BinaryOption(
        string _optionName,         // simple description of the option
        uint _optionPrice,          // option price in USD cents
        bool _optionForHigherPrice, // option is for higher price
        uint _activeTime,           // active time of the option
        uint _minSubscription       // minimum subscription
    ) public payable {
        require(msg.value > _minSubscription);

        seller = msg.sender;
        totalSellingAmount = msg.value;

        name = _optionName;
        optionPrice = _optionPrice;
        optionForHigherPrice = _optionForHigherPrice;

        optionEndTime = now + _activeTime;
        minSubscription = _minSubscription;

        // schedule an oraclize.it query at the end of the option
        oraclize_query(_activeTime + 10, "URL", "json(https://api.coinbase.com/v2/prices/spot?currency=USD).data.amount");
    }

    // callback for oraclize.it
    function __callback(bytes32 myid, string result) public {
        if (msg.sender != oraclize_cbAddress()) revert();
        uint amount = stringToPrice(result);
        endOption(amount);
    }

    // subscribe to the option
    function subscribe() public payable {
        // allows subscription only before option ended
        require(now <= optionEndTime);
        require(msg.value >= minSubscription);
        require(msg.value <= totalSellingAmount - totalSubscribedAmount);
        require(msg.sender != seller);

        if (msg.value > 0) {
            subscribedAmount[msg.sender] += msg.value;
            totalSubscribedAmount += msg.value;
            OptionSubscribed(msg.value);
        }
    }
    
    // buyer's claim
    function buyerClaim() public returns (bool) {
        require(ended);
        require(!sellerWonTheOption);
        require(!optionAlreadyClaimed[msg.sender]);
        require(subscribedAmount[msg.sender] > 0);

        optionAlreadyClaimed[msg.sender] = true;
        uint amount = subscribedAmount[msg.sender] * 2;
        if (!msg.sender.send(amount)) {
            optionAlreadyClaimed[msg.sender] = false;
            return false;
        }

        OptionClaimed(amount);

        return true;
    }

    // seller's claim
    function sellerClaim() public returns (bool) {
        require(seller == msg.sender);
        require(ended);
        require(!sellerClaimedTheOption);

        sellerClaimedTheOption = true;
        uint amount;
        if (sellerWonTheOption) {
            amount = totalSellingAmount + totalSubscribedAmount;
        }
        else {
            amount = totalSellingAmount - totalSubscribedAmount;
        }

        if (amount > 0) {
            if (!msg.sender.send(amount)) {
                sellerClaimedTheOption = false;
                return false;
            }
        }

        OptionClaimed(amount);

        return true;
    }

    // end of option and assign the option price
    function endOption(uint _actualPrice) internal {
        // end option
        require(now > optionEndTime);
        require(!ended);

        // set end parameters
        ended = true;
        actualOptionPrice = _actualPrice;
        if (optionForHigherPrice) {
            sellerWonTheOption = (actualOptionPrice <= optionPrice);
        }
        else {
            sellerWonTheOption = (actualOptionPrice > optionPrice);
        }
 
        OptionEnded(sellerWonTheOption);
    }

    function stringToPrice(string s) internal constant returns (uint result) {
        bytes memory b = bytes(s);
        uint i;
        uint wholepart = 0;
        bool fraction = false;
        uint fractionlen = 0;
        result = 0;
        for (i = 0; i < b.length; i++) {
            uint c = uint(b[i]);
            if (c >= 48 && c <= 57) {
                if (fraction) {
                    if (fractionlen >= 2) {
                        break;
                    }
                    else {
                        result = result * 10 + (c - 48);
                        fractionlen ++;
                    }
                }
                else {
                    result = result * 10 + (c - 48);
                }
            }
            else if(c == 46) {
                wholepart = result;
                fraction = true;
                result = 0;
            }
        }
        if (fractionlen == 1) {
            result = result * 10;
        }

        result = result + wholepart * 100;
    }
}
