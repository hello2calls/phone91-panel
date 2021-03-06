<?php 
include_once('config.php');
include_once(CLASS_DIR."transaction_class.php");
# check user has confirm mobile no or not if yes then go to current page otherwise confirm mobile number .  
if($funobj->getConfirmNumber($_SESSION["userid"]) == 0){
    echo "<script>location.href='#!setting.php|phone.php'</script>";
//    exit();
}
# user id
$userId = $_SESSION['userid'];
#object of transaction class 
$trans_obj = new transaction_class();
#call function getTransactionlogDetial for get all detion of transation 
$transaction = $trans_obj->getPersonalTransaction($userId);
$transData = json_decode($transaction,TRUE);
?>
<!--Transicition Wrapper-->
<div id="callLogWrap" class="Wrap" style="overflow: auto;">
	<!--Inner Wrapper-->
    <div class="clear inner">
        <div class="clear" id="srchrow">
<!--             <label class="transition">
                    <p class="fl">Showing <span>100</span> results by <span>latest</span> whose cost is more than </p>
                    <p class="fl showInfo"> 
                        <span class="ic-8 close"></span>
                        <span class="fl">10200</span>
                        <span class="ic-8 arrow"></span>
                        <p class="rowClose fl cp" title="Close"><span class="ic-16 close"></span></p>
                    </p>
               </label>-->
        </div>
		<!--Content Table-->
        <div id="resultContainer" class="flip-scroll clear mrT2 box-">
        	<table width="100%" border="0" cellspacing="0" cellpadding="0" id="trstable" class="cmntbl boxsize cf">
                <thead>
                    <tr>
                        <th width="10%">Date</th>
                        <th width="8%">Type</th>
                        <th  class="alR" width="5%">Amount</th>
                        <th  class="alR" width="5%">Balance</th>
                        <th width="25%">Description</th>
                        <th  class="alR" width="8%">Debit</th>
                        <th  class="alR" width="5%">Credit</th>
                        <th width="7%" >Closing Balance</th>
                    </tr>
                </thead>
                <tbody>
					 <?php
						 $totalCredit=0;$totalDebit=0;$totalClosingBalance=0;
						 foreach($transData as $trans) { ?>
							<tr class="">
								<td><?php echo $trans['date']; ?></td>
								<td ><?php echo htmlentities($trans['paymentType']); ?></td>
								<td class="alR"><?php if($trans['amount']==0)
                                                                                        echo '';
                                                                                      else 
                                                                                        echo $trans['amount']; ?></td>
								<td class="alR"><?php echo $trans['currentBalance']; ?></td>
								<td><?php echo htmlentities($trans['description']); ?></td>
								<td  class="alR">
                                                                    <span class="debAmnt">&nbsp;</span>
                                                                    <span class="debit"><?php 
                                                                    if($trans['debitActualCurrency']==0)
                                                                     echo '';
                                                                    else
                                                                    {    
                                                                    ?>(<?php  echo $trans['debit'] . " " . $trans['currencyName']; ?>) <?php echo $trans['debitActualCurrency']; 
                                                                    
                                                                    } ?></span></td>
								<td  class="alR"><?php 
                                                                    if($trans['creditActualCurrency']==0)
                                                                     echo '';
                                                                    else
                                                                    {    
                                                                    ?> (<?php echo $trans['credit'] . " " . $trans['currencyName'];?>)<?php echo $trans['creditActualCurrency']; 
                                                                    } ?></td>
								<td class="alR closeBalance"><?php echo $trans['closingBalance']; ?></td>
							</tr>
						 <?php 
						 $totalCredit = $totalCredit + $trans['creditActualCurrency'];
						 $totalDebit = $totalDebit + $trans['debitActualCurrency'];
						 $totalClosingBalance = $totalClosingBalance + $trans['closingBalance'];
              	 } ?>
                <tr class="zerobal">
                    <td colspan="100%"></td>
                </tr>
                <tr class="">
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="alR"><span class="debit"><?php echo $totalDebit ;?></span></td>
                        <td class="alR"><?php echo $totalCredit; ?></td>
                        <td class="alR closeBalance"><?php echo $totalClosingBalance; ?></td>
                </tr>
                </tbody>
            </table>
        </div>
        <!--//Content Table-->
    </div>
    <!--//Inner Wrapper-->
</div>
<!--//Transicition Wrapper-->
<script>
var _W, _H, _header, _lH, _lM;
$(function() {
	_W = $(window).width(); //retrieve current window width
	_H = $(window).height(); //retrieve current window height
	_head = $('#header').outerHeight(true);//retrieve current header height
	_lM = $('#leftsec').outerWidth(true);//retrieve current width of left section
	_lH = _H - _head; //retrieve left height
	_lW = _W - _lM; //retrieve left width for container
	
	$('#callLogWrap').css({height:_lH});
	//$('#settingwrap #rightsec').css({width:_lW});
});
$( document ).ready(function() {
$("#trstable tbody tr:visible:even").addClass("even"); 
$("#trstable tbody tr:visible:odd").addClass("odd");
});

$(window).resize(function() {
	_W = $(window).width(); //retrieve current window width
	_H = $(window).height(); //retrieve current window height
	_head = $('#header').outerHeight(true);//retrieve current header height
	_lM = $('#leftsec').outerWidth(true);//retrieve current width of left section
	_lH = _H - _head; //retrieve left height
	_lW = _W - _lM; //retrieve left width for container
	
	$('#callLogWrap').css({height:_lH});
	//$('#settingwrap #rightsec').css({width:_lW});
});
</script>