<?php
if (isset($_GET["addr"])) {
  $contract = $_GET["addr"];
}
else {
  $str = 'No addr parameter. Use a link generated from <a href="' . $SERVER['HTTP_REFERER'] . 'dploy.php" >deploy.php</a>.';
  exit($str);
}
?>

<html>
<head>
<title>Subscribe Option</title>
</head>
<body>
<p id="bet">
<h2 id="optionname"></h2>
<table>
<tr><th>Option Price (in USD):</th><th id="optionprice"></th><th id="actualprice"></th></tr>
<tr><th>Total Fund (in ETH):</th><th id="totalfund"></th><th></th></tr>
<tr><th>Subscribed Fund (in ETH):</th><th id="subfund"></th><th></th></tr>
</table>
<p id="endtime"></p>
<p id="button"></p>
<p id="claim"></p>
</p>
<p id="metamask">
</p>
</body>
</html>

<script type='text/javascript' src='BinaryOption.js'></script>
<script type='text/javascript'>

// designate network
// "1" for main net
// "3" for Ropsten test net
var supportedNetworkType = "3";
var binaryoption;

function doClaimBuyer(address) {
  binaryoption.optionAlreadyClaimed(address, function(error, claimed) {
    if(!error) {
      if (claimed) {
        alert("You already claimed your option!");
      }
      else {
        binaryoption.buyerClaim(function(error, result) {
          if (!error) {
            if (result) {
              alert("Your option is claimed. Please wait a moment for the funds to be transfered to your account.");
              refreshApp(result);
            }
            else {
              alert("Something went wrong. Please try again later.");
            }
          }
          else {
            console.log(error);
          }
        });
      }
    }
    else {
      console.log(error);
    }
  });
}

function claimBuyer() {
  checkNetwork(function() {
    var address = web3js.eth.defaultAccount;
    binaryoption.subscribedAmount(address, function(error, result) {
      if (!error) {
        if (result.isZero()) {
          alert("You did not subscribe to this option!");
        }
        else {
          if (!confirm("Do you want to claim your option?\nNOTE: Fees may apply when claiming your option. Do not repeat or you may lose fees.")) {
            return;
          }

          doClaimBuyer(address);
        }
      }
      else {
        console.log(error);
      }
    });
  });
}

function doClaimSeller() {
  binaryoption.sellerClaimedTheOption(function(error, result) {
    if (!error) {
      if (result) {
        alert("Seller fund was already claimed.");
      }
      else {
        binaryoption.sellerClaim(function(error, result) {
          if (!error) {
            alert("Seller fund is successfully claimed.");
            refreshApp(result);
          }
          else {
            alert("Something is wrong, please try again later.");
            console.log(error);
          }
        });
      }
    }
    else {
      console.log(error);
    }
  });
}

function claimSeller() {
  checkNetwork(function() {
    binaryoption.seller(function(error, seller) {
      if(!error) {
        if (seller == web3js.eth.defaultAccount) {
          if (!confirm("Do you want to claim your fund?\nNOTE: Fees may apply when claiming fund. Do not repeat or you may lose fees.")) {
            return;
          }

          doClaimSeller();
        }
        else {
          alert("Only seller can claim seller fund.");
        }
      }
      else {
        console.log(error);
      }
    });
  });
}

function refreshApp(transaction) {
  document.getElementById('totalfund').innerHTML = "...Refreshing...";
  document.getElementById('subfund').innerHTML = "...Refreshing...";
  document.getElementById('button').innerHTML = '';
  document.getElementById('claim').innerHTML = '';

  var filter = web3js.eth.filter({ fromBlock: 'latest', toBlock: 'latest', address: web3js.eth.defaultAddress});

  filter.watch(function(error, result) {
    if (!error) {
      if (result.transactionHash == transaction) {
        filter.stopWatching();
        runApp();

        if (result.removed) {
          alert("The transaction did not complete. Please try again later.");
        }
      }
    }
    else {
      console.log(error);
      filter.stopWatching();
    }
  });
}

function subscribeWithAddress(address) {
  binaryoption.minSubscription(function(error, result) {
    var minSub = web3js.fromWei(result, 'ether');
    var prompt = "You are about to subscribe to this option.\nThe minimum amount is " + minSub + " ETH.\nPlease enter amount:";
    var sub = window.prompt(prompt, minSub);
    if (sub === null) {
      return;
    }
    else if (sub === '') {
      alert("You need to enter something to subscribe.");
    }

    var amount = web3js.toWei(sub, 'ether');
    if (confirm("Subscribe with " + sub + " ETH?")) {
      binaryoption.subscribe.sendTransaction({ from: address, value: amount, gasPrice: '1000000000' }, function(error, result) {
        if (!error) {
          alert("You have subscribed to this option! It will take some time for it to reflect on the blockchain.");
          // refresh the page
          //console.log(result);
          refreshApp(result);
        }
        else {
          alert("Something is wrong, please try again.");
          console.log(error);
        }
      });
    }
  });
}

function subscribe() {
  checkNetwork(function() {
    var address = web3js.eth.defaultAccount;
    binaryoption.seller(function(error, result) {
      if (address == result) {
        alert("Seller can not subscribe.");
      }
      else {
        subscribeWithAddress(address);
      }
    });
  });
}

function runApp() {
  checkNetwork(function() {
    binaryoption.name(function(error, name) {
      document.getElementById('optionname').innerHTML = name;
    });
    binaryoption.optionPrice(function(error, optionPrice) {
      binaryoption.optionForHigherPrice(function(error, optionForHigherPrice) {
        document.getElementById('optionprice').innerHTML = (optionForHigherPrice ? "> " : "<= ") + "$" + (optionPrice / 100);
      });
    });
    binaryoption.totalSellingAmount(function(error, leftAmount) {
      document.getElementById('totalfund').innerHTML = web3js.fromWei(leftAmount, 'ether') + " ETH";
    });
    binaryoption.totalSubscribedAmount(function(error, rightAmount) {
      document.getElementById('subfund').innerHTML = web3js.fromWei(rightAmount, 'ether') + " ETH";
    });

    document.getElementById('actualprice').innerHTML = '';
    document.getElementById('button').innerHTML = '';
    document.getElementById('claim').innerHTML = '';

    binaryoption.ended(function(error, ended) {
      if (ended) {
        // option ended
        binaryoption.actualOptionPrice(function(error, actualOptionPrice) {
          document.getElementById('actualprice').innerHTML = 'Actual Price: $' + (actualOptionPrice / 100);
        });

        binaryoption.seller(function(error, seller) {
          if(!error) {
            if (seller == web3js.eth.defaultAccount) {
              // show seller UI
              binaryoption.sellerClaimedTheOption(function(error, result) {
                if (!error) {
                  if (result) {
                    document.getElementById('button').innerHTML = 'Seller fund has already been claimed.';
                  }
                  else {
                    document.getElementById('button').innerHTML = '<button type="button" onclick="claimSeller()">Claim Fund</button>';
                    binaryoption.totalSellingAmount(function(error, totalSellingAmount) {
                      binaryoption.totalSubscribedAmount(function(error, totalSubscribedAmount) {
                        binaryoption.sellerWonTheOption(function(error, sellerWonTheOption) {
                          if (sellerWonTheOption) {
                            var sellerAmount = totalSellingAmount.plus(totalSubscribedAmount);
                            document.getElementById('claim').innerHTML = "Seller's claim is " + web3js.fromWei(sellerAmount, 'ether') + ' ETH (fees may apply)';
                          }
                          else {
                            var sellerAmount = totalSellingAmount.minus(totalSubscribedAmount);
                            document.getElementById('claim').innerHTML = "Seller's claim is " + web3js.fromWei(sellerAmount, 'ether') + ' ETH (fees may apply)';
                          }
                        });
                      });
                    });
                  }
                }
                else {
                  console.log(error);
                }
              });
            }
            else {
              // check if this account is elegible
              var address = web3js.eth.defaultAccount;
              binaryoption.subscribedAmount(address, function(error, subscribedAmount) {
                if (!error && !subscribedAmount.isZero()) {
                  binaryoption.sellerWonTheOption(function(error, sellerWonTheOption) {
                    if (!error && !sellerWonTheOption) {
                      binaryoption.optionAlreadyClaimed(address, function(error, optionAlreadyClaimed) {
                        if (!error && !optionAlreadyClaimed) {
                          document.getElementById('button').innerHTML = '<button type="button" onclick="claimBuyer()">Claim Fund</button>';
                          var buyerAmount = subscribedAmount * 2;
                          document.getElementById('claim').innerHTML = "Your claim is " + web3js.fromWei(buyerAmount, 'ether') + ' ETH (fees may apply)';
                        }
                        else {
                          document.getElementById('claim').innerHTML = 'You have already claimed your option.';
                        }
                      });
                    }
                    else {
                      document.getElementById('claim').innerHTML = 'This option is won by the seller.';
                    }
                  });
                }
                else {
                  document.getElementById('claim').innerHTML = "You didn't subscribe to this option.";
                }
              });
            }

            binaryoption.optionEndTime(function(error, optionEndTime) {
              if (!error) {
                var endTime = new Date(optionEndTime * 1000);
                document.getElementById('endtime').innerHTML = 'Option ended at ' + endTime.toString();
              }
              else {
                console.log(error);
              }
            });
          }
          else {
            console.log(error);
          }
        });
      }
      else {
        binaryoption.optionEndTime(function(error, optionEndTime) {
          var endTime = new Date(optionEndTime * 1000);
          if (Date.now() > endTime) {
            document.getElementById('endtime').innerHTML = 'Option ended at ' + endTime.toString() + ", please wait for the result.";
            document.getElementById('button').innerHTML = '';
            document.getElementById('claim').innerHTML = "";
          }
          else {
            document.getElementById('endtime').innerHTML = 'Option will end at ' + endTime.toString();

            binaryoption.seller(function(error, seller) {
              if(!error) {
                if (seller == web3js.eth.defaultAccount) {
                  document.getElementById('button').innerHTML = '';
                  document.getElementById('claim').innerHTML = 'Seller can not subscribe.';
                }
                else {
                  binaryoption.totalSellingAmount(function(error, totalSellingAmount) {
                    binaryoption.totalSubscribedAmount(function(error, totalSubscribedAmount) {
                      var remainingAmount = totalSellingAmount.minus(totalSubscribedAmount);
                      if (remainingAmount.isZero()) {
                        document.getElementById('claim').innerHTML = "All available options were already subscribed.";
                      }
                      else {
                        document.getElementById('button').innerHTML = '<button type="button" onclick="subscribe()">Subscribe</button>';
                        document.getElementById('claim').innerHTML = "Remaining available amount is " + web3js.fromWei(remainingAmount, 'ether') + ' ETH';
                      }
                    });
                  });
                }
              }
              else {
                console.log(error);
              }
            });
          }
        });
      }
    });
  });
}

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

function startApp() {
  console.log("StartApp");
  runApp();

  var account = web3js.eth.defaultAccount;
  var accountInterval = setInterval(function() {
    if (web3js.eth.defaultAccount !== account) {
      account = web3js.eth.defaultAccount;
      runApp();
    }
  }, 100);
}

window.addEventListener('load', function() {
  // Checking if Web3 has been injected by the browser (Mist/MetaMask)
  if (typeof web3 !== 'undefined') {
    // Use Mist/MetaMask's provider
    web3js = new Web3(web3.currentProvider);
    var contractAddr = "<?php echo $contract; ?>";
    binaryoption = web3js.eth.contract(contractABI).at(contractAddr);
    startApp();
  } else {
    console.log('No web3? You should consider trying MetaMask!')
    // fallback - use your fallback strategy (local node / hosted node + in-dapp id mgmt / fail)
    document.getElementById('metamask').innerHTML = '<a href="https://metamask.io"><img src="download-metamask.png"></a>';
  }
})
</script>
