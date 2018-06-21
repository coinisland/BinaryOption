<?php
?>

<html>
<head>
<title>Deploy Option</title>
</head>
<body>
<h2>Deploy Option</h2>
<table>
<tr>
<th>Option Name</th>
<th><input type="text" name="optionname"></input></th>
</tr>
<tr>
<th>Option Price (in USD)</th>
<th><input type="number" name="optionprice"></input></th>
</tr>
<tr>
<th>Option Direction (for buyers to win the option)</th>
<th><input type="radio" id="optionhigher" name="optiondir"><label>Higher</label><input type="radio" id="optionlower" name="optiondir"><label>Lower</label></th>
</tr>
<tr>
<th>Active Time (in seconds)</th>
<th><input type="number" name="activetime"></input></th>
</tr>
<tr>
<th>Minimum Subscription (ETH)</th>
<th><input type="number" name="minsub"></input></th>
</tr>
<tr>
<th>Total Funds available for subscription (ETH)</th>
<th><input type="number" name="totalfund"></input></th>
</tr>
</table>
<p id="metamask"></p>
<p id="contractlink"></p>
</body>
</html>

<script type='text/javascript' src='BinaryOption.js'></script>
<script type='text/javascript'>

// designate network
// "1" for main net
// "3" for Ropsten test net
var supportedNetworkType = "3";
var host='<?php echo $SERVER['HTTP_REFERER']; ?>';

function checkNetwork(callback) {
  var supportedNetworkName;
  switch (supportedNetworkType) {
    case "1":
      supportedNetworkName = "Main Net";
      break;

    case "3":
      supportedNetworkName = "Ropsten";
      break;

    case "4":
      supportedNetworkName = "Rinkeby";
      break;

    case "42":
      supportedNetworkName = "Kovan";
      break;

    default:
      supportedNetworkName = supportedNetworkType;
      break;
  }

  web3js.version.getNetwork(function (error, netId) {
    if (!error) {
      if (netId == supportedNetworkType) {
        callback();
      }
      else {
        alert("Please switch network to " + supportedNetworkName + ".");
      }
    }
    else {
      console.log(error);
    }
  });
}

function deployOption() {
    checkNetwork(function() {
        var optionName = document.getElementsByName('optionname')[0].value;
        var optionPrice = document.getElementsByName('optionprice')[0].value;
        var optionHigher = document.getElementById('optionhigher');
        var optionLower = document.getElementById('optionlower');
        var activeTime = document.getElementsByName('activetime')[0].value;
        var minSub = document.getElementsByName('minsub')[0].value;
        var totalFund = document.getElementsByName('totalfund')[0].value;

        if (optionName == '' || typeof optionName === 'undefined') {
            alert("Please enter an option name.");
            return;
        }

        if (optionPrice == '' || typeof optionPrice === 'undefined') {
            alert("Please enter an option price.");
            return;
        }

        if (activeTime <= 0 || typeof activeTime === 'undefined') {
            alert("Please enter a valid active time.");
            return;
        }

        if (minSub < 0 || typeof minSub === 'undefined') {
            alert("Please enter a valid minimum subscription.");
            return;
        }

        if (!optionHigher.checked && !optionLower.checked) {
            alert("Please select an option direction (higher or lower).");
            return;
        }

        if (totalFund < 0 || typeof totalFund === 'undefined' || totalFund < minSub) {
            alert("Please enter a valid total fund.");
            return;
        }


        var optionForHigher = optionHigher.checked; 
        var confirmStr = "Deply an option with:\n" +
            "\tOption name: " + optionName + "\n" +
            "\tOption price: " + (optionForHigher ? "> " : "<= ") + optionPrice + "\n" +
            "\tActive time: " + activeTime + " seconds\n" +
            "\tMinimum sub: " + minSub + " ETH\n" + 
            "\tTotal fund: " + totalFund + " ETH\n" + 
            "It takes some time to deploy an option. Please wait patiently for the option link to appear.";
        if (confirm(confirmStr)) {
            document.getElementById('metamask').innerHTML = '';
            document.getElementById('contractlink').innerHTML = 'Your option link will appear here when ready.';
            var binaryoptionContract = web3js.eth.contract(contractABI);
            var minSubWei = web3js.toWei(minSub, 'ether');
            var totalFundWei = web3js.toWei(totalFund, 'ether');
            var optionPriceInCent = optionPrice * 100;
            var binaryoption = binaryoptionContract.new(optionName, optionPriceInCent, optionForHigher, activeTime, minSubWei, {
                from: web3js.eth.defaultAccount,
                data: contractCode,
                value: totalFundWei,
                gasPrice: '1000000000',
                gas: '4700000'
            }, function(error, contract) {
                if (!error) {
                    if (typeof contract.address !== 'undefined') {
                        document.getElementById('contractlink').innerHTML = 'Your option is ready at <a href="' + host + 'option.php?addr=' + contract.address + '" >Option Page</a>';
                        console.log("Contract address: " + contract.address);
                        runApp();
                    }
                }
                else {
                    document.getElementById('contractlink').innerHTML = '';
                    alert("Something is wrong, please try again later.");
                    console.log(error);
                }
            });
        }
    });
}

function runApp() {
    checkNetwork(function() {
        document.getElementById('metamask').innerHTML = '<button type="button" onclick="deployOption()">Deploy</button>';
    });
}

function startApp() {
    console.log("StartApp");
    runApp();
}

window.addEventListener('load', function() {
  // Checking if Web3 has been injected by the browser (Mist/MetaMask)
  if (typeof web3 !== 'undefined') {
    // Use Mist/MetaMask's provider
    web3js = new Web3(web3.currentProvider);
    startApp();
  } else {
    console.log('No web3? You should consider trying MetaMask!')
    // fallback - use your fallback strategy (local node / hosted node + in-dapp id mgmt / fail)
    document.getElementById('metamask').innerHTML = '<a href="https://metamask.io"><img src="download-metamask.png"></a>';
  }
});

</script>
