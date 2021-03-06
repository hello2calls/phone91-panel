<?php
/**
 * @Author sudhir pandey <sudhir@hostnsoft.com>
 * @createdDate 31-07-2013
 * 
 */
include dirname(dirname(__FILE__)).'/config.php';
class transaction_class extends fun
{
    /**
     * define class variables here
     */
    
    var $toUser;
    var $fromUser;
    var $newClosingAmount;
    
   /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $id Id Of User Or Reseller, 
     *           int $type Type to get records(num of rows or balance)
     * @details : This function used to get closing balance of user.
     * @return int (closing Balance)
     */
    
    function getClosingBalance( $id , $type = NULL)
    {
        #- Table name 
        $table = '91_closingAmount';
        
        #- Condition to Find Closing Balance
        $condition = "userId = '" . $id . "'";

        #- Function To Fetch Records( This Is Common function. currently fetching closingAmount )
        $result =  $this->selectData( 'closingAmount', $table, $condition );
        
        if(!$result)
            trigger_error('problem while get closing balance detail ,condition:'.$condition);
        
        #- Getting closingAmount
        if( $result->num_rows > 0 ) 
        {	
            if($type == 1)
                $balanceResponse = $result->num_rows ; #- returnin number of rows in some cases.
            else
            {
                while($row = $result->fetch_array(MYSQL_ASSOC) ) 
                {
                    $balanceResponse = $row["closingAmount"];
                }
            }
        }
        else #- No Records Found
        {
            $balanceResponse = 0;
        }

        return $balanceResponse;
    }

    /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $id Id Of User Or Reseller, 
     *           int $amount Amount To Be updated
     * 
     * @details : This FunctionIs To Update Closing balance.
     * @return :
     */
    
    function updateClosingBalance($id,$amount)
    {
        #- Getting entry of this user in table exists or not.
        $userExists = $this->getClosingBalance( $id ,1);
        
        $table = '91_closingAmount';
        
        #- If user closing balance present then update closing balance otherwise add closing balance into table
        if ($userExists > 0) 
        {	    
            #update closing amount of user 
            $data = array("closingAmount" => $amount , "lastUpdate" => date('Y-m-d H:i:s') );   
            $condition = "userId = ".$id;
            $this->db->update( $table, $data )->where($condition);
        }
        else
        {
            #- Insert closing amount of user
            $data = array( "userId" => (int)$id , "closingAmount" => $amount , "lastUpdate" => date('Y-m-d H:i:s') );
            $this->db->insert( $table, $data );
        }
        
        $query = $this->db->getQuery();
        $result = $this->db->execute();   
        
        if(!$result)
        {
            trigger_error('problem while get closing balance detail ,data:'.json_encode($data));
        }
    }
    
   /**
     * @author sudhir pandey <ankitpatidar@hostnsoft.com> 
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $toUser Id Of User Or Reseller, 
     *           int $talktime talktime To Be updated
     *               $sign (+ or -)
     * @details : This FunctionIs To Update Closing balance.
     * @return : 0 or 1
     */
    
    function updateUserBalance( $toUser, $talktime, $sign = '+')
    {
        #- This is for sql injection
        $talktime = $this->db->real_escape_string($talktime);
        
        if($talktime > 0)
        {
            $updateBalance = "UPDATE 91_userBalance SET balance=balance".$sign.$talktime." WHERE userId='".$toUser."'" ;
            
            $result = mysqli_query( $this->db, $updateBalance ) or $error = 'error::' . mysqli_error();
            
            if(!$result)
            {
                trigger_error('problem while udpate user balance  ,query:'.$updateBalance); 
                return 0;
            }
        }
        else
            return 0;
        
        return 1; 
    }
    
    
   /**
     * @author sudhir pandey <ankitpatidar@hostnsoft.com> 
     * @filesource
     * @since  01/07/2013
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $amount , 
     *           int $talktime talktime To Be updated
     *               $paymentType 
     *               $description
     * @details : Function To Add Transaction Entry In Transaction Table.
     * @return :
     */
    
    function addTransactional($amount,$talktime,$paymentType,$description,$type,$partialAmt = 0,$currency = 0,$partialCurrency = 0 , $status = NULL)
    { 
        #- Check amount limit if amount is greaterthen 1000 then mail send to admin 
        if($talktime > 1000)
        {
            $this->sendErrorMail("sudhir@hostnsoft.com","amount is greater then 1000 rs in transaction log .");
        }
        
        #- Find closing amount form 91_closingAmount table
        $getBalance = $this->getClosingBalance($this->toUser);
       
        $clsamount = $this->closingBalCurrencyCnvt($this->toUser,$currency,$amount);
        
        #- Calculating closing balance
        #- Check for amount add or reduce in transaction 
        if($status == "add")
        {
            $debit = 0;
            $credit = $amount;
            $closingBalance = ((int)$getBalance - (int)$clsamount);
        }
        else
        {
            $debit = $amount;
            $credit = 0;      
            $closingBalance = ((int)$getBalance + (int)$clsamount);
        }
        
        #- Get current balance form 91_userBalance table
        $currBalance = $this->getcurrentbalance($this->toUser);
        
        #- Add transaction in case of voip91(payment type).
        $result = $this->addTransactional_sub($talktime,$currBalance,"voip",$debit,$credit,$closingBalance,$description,$currency);    
        
        #- If type is prepaid (advance) 
        if($type == "prepaid")
        {
            $closingBalance = ((int)$closingBalance - (int)$clsamount);  
            
            #- Add transaction with given payment type (cash,memo,bank or other).
            $closingBalanceResult = $this->addTransactional_sub(0,$currBalance,$paymentType,0,$amount,$closingBalance,$description,$currency);       
        }
        else if($type == "partial")  #- If type is partial
        {
            $partialbal = $this->closingBalCurrencyCnvt($this->toUser,$partialCurrency,$partialAmt);
            $closingBalance = ((int)$closingBalance - (int)$partialbal);
            
            #- Add  partial transaction with given payment type (cash,memo,bank or other).
            $closingBalanceResult = $this->addTransactional_sub(0,$currBalance,$paymentType,0,$partialAmt,$closingBalance,$description,$partialCurrency);
        }
        
        #- Update closing balance of user 
        $updateClosing = $this->updateClosingBalance($this->toUser,$closingBalance);
        
        if(!$updateClosing)
            trigger_error('problem while get closing balance detail ,data:'.json_encode($data));
             
        if($result == 1 || $closingBalanceResult == 1)
        {
            return 1;
        } 
        else
            return 0;
        
    }
    
    /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @since: 01/07/2013
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $amount , 
     *           int $currentBalance ,
     *               $paymentType
     *           int $debit
     *           int $credit
     *           int $closingBalance
     *           varchar $description
     *           varchar $currency
     * 
     * @details : Function To Add Transaction Entry In Transaction Table.
     * @return : 0 or 1 
     */
    
    function addTransactional_sub($amount,$currentBalance,$paymentType,$debit,$credit,$closingBalance,$description,$currency=0)
    {  
        
        #- Converting current debit and credit  amount To USers Base Currency.
        $debitCntAmt = $this->closingBalCurrencyCnvt($this->toUser,$currency,$debit);
        
        $creditCntAmt = $this->closingBalCurrencyCnvt($this->toUser,$currency,$credit);
         
        $this->newClosingAmount = ((int)$debitCntAmt + (int)$creditCntAmt); 
        
        $paymentType = $this->db->real_escape_string($paymentType);
        $description = $this->db->real_escape_string($description);
        
        #- Insert query (insert data into 91_tempEmails table )
        $data = array( "fromUser" => (int)$this->fromUser, 
                        "toUser" => $this->toUser , 
                        "date" => date('Y-m-d H:i:s'), 
                        "amount" => $amount, 
                        "currentBalance" => $currentBalance, 
                        "debit" => $debit, 
                        "credit" => $credit, 
                        "paymentType" => $paymentType, 
                        "closingBalance" => $closingBalance, 
                        "description" => $description, 
                        "currency" => $currency, 
                        "debitConvert" => $debitCntAmt, 
                        "creditConvert" => $creditCntAmt ); 
        
        #- Add taransaction detail into taransation log table 
        $transactionlog = "91_transactionLog";  
        
        $res = $this->db->insert($transactionlog, $data);	
        $qur = $this->db->getQuery();
        
        $savedata = $this->db->execute();
        
        if(!$savedata)
        {
            trigger_error('problem while insert data in trasaction_log data:'.$qur);
            $this->sendErrorMail("sudhir@hostnsoft.com","insert query fail : $qur ");
            return 0;
        }
        else
            return 1;
    }
    
    /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @since: 01/07/2013
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $amount , 
     *           int $toUser
     *           varchar $currency
     * 
     * @details : Function to check and convert closing balance currency .
     * @return : int amount
     */
    
    function closingBalCurrencyCnvt($toUser,$currency,$amount)
    {
        if( $amount == 0 )
             return 0;
        
        #- Getting currency Id.
        $userDetail = $this->getNameAndUserName($toUser);
        
        if($currency != $currencyId)
        {
            $paymentCurrency = $this->getCurrencyViaApc($currency, 1);
            $userCurrency = $this->getCurrencyViaApc( $userDetail['currencyId'] , 1 );
            $debitCntAmt = $this->currencyConvert($paymentCurrency, $userCurrency, $amount); 
        }
        else
        {
            $debitCntAmt = $amount;
        }
        
        return $debitCntAmt;
    }
    
    /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @since: 01/07/2013
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $id , 
     * @details : Function for getting current balance form 91_userbalance table  .
     * @return : int balance
     */
    
    function getcurrentbalance($id)
    {
        #- Table name 
        $table = '91_userBalance';
        
        $condition = "userId = '" . $id . "'";
       
        #- Find current balance of user 
        $result =  $this->selectData( 'balance', $table, $condition );
        
        #- Variable balance use for store current balance data
        if ($result->num_rows > 0) 
        {	
            while ($row= $result->fetch_array(MYSQL_ASSOC) ) 
            {
                $currentBalance = $row["balance"];
            }
        }
        else
        {
            $currentBalance = 0;
            trigger_error('problem while get user balance,condition:'.$condition);
        }
        return $currentBalance;
    }
    
   /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @since: 02-08-2013
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $fromUser , 
     *           int $toUser 
     * @details : Function  for getting transaction log detail.
     * @return : int balance
     */
    
    function getTransactionLogDetail($fromUser,$toUser)
    {
       #- Function To Get Transaction Detail
       $transactionData = $this->getPersonalTransaction($toUser,2,$fromUser); 
       return $transactionData;
    }
    
    
     /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @since: 02-08-2013
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date: 18/12/2013
     * @param :  int $userid , 
     *           int $resellerTrans (Must be 0 ,1 or 2) 
     * @details : Function  for getting transaction log detail.
     * @return : int balance
     */
    
    function getPersonalTransaction($userid,$resellerTrans = 0, $fromUser = NULL)
    {  
      #- Get user currency id 
      $userDetail = $this->getNameAndUserName($userid);//$currencyId
            
      #- Get currency name 
      $currencyName = $this->getCurrencyViaApc( $userDetail['currencyId'] , 1 );  
        
      #- Table name   
      $table = '91_transactionLog';
      
      switch($resellerTrans)
      {
          case '0':
               $condition = "toUser = '".$userid."'";
              break;    
           case '1':
                $condition = "fromUser = '" .$userid. "'order by date desc";
              break;
           case '2':
                $condition = "fromUser = '" .$fromUser. "' and toUser = '".$userid."'";
              break;
      }
     
      #- Get data form transaction log table where form user and touser are given
      $result =  $this->selectData( '*', $table, $condition );
      
      if(!$result)
            trigger_error('Problem while get details from transaction log,condition:'.$condition);
      
      #- Check data total no of row is greater then 0 or not 
      if ($result->num_rows > 0)
      {
          while ($row = $result->fetch_array(MYSQL_ASSOC) ) 
          { 
              switch($resellerTrans)
              {
                  case '0' : 
                      $userDetail = $this->getNameAndUserName($row['fromUser']); // name and userName 
                      $data['name'] = $userDetail['name'];
                      $data['userName'] = $userDetail['userName'];;      
                      break;
                  
                  case '1':
                      $userDetail = $this->getNameAndUserName($row['toUser']);
                      $data['name'] = $userDetail['name'];
                      $data['userName'] = $userDetail['userName'];;
                      break;    
              }
              
              $data['fromUser'] = $row['fromUser'];
              $data['toUser'] = $row['toUser'];
              $data['date'] = $row['date'];
              $data['amount'] = $row['amount'];
              $data['currentBalance'] = $row['currentBalance'];
              $data['credit'] = $row['credit'];
              $data['debit'] = $row['debit'];
              $data['paymentType'] = $row['paymentType'];
              $data['closingBalance'] = $row['closingBalance'];
              $data['description'] = $row['description']; 
              $data['currency'] = $row['currency']; 
              
              $currencyViaApc = $this->getCurrencyViaApc( $data['currency'] , 1 );
              
              if($currencyViaApc == '' || $currencyViaApc == null)
              {
                 $data['currencyName'] = $currencyName;
              }
              else
                $data['currencyName'] = $currencyViaApc;
              
              if($currencyName == $data['currencyName'])
              {
                $data['creditActualCurrency'] = $data['credit']; 
                $data['debitActualCurrency'] =  $data['debit'];
              }
              else
              {
                $data['creditActualCurrency'] = round($row['creditConvert'],3);
                $data['debitActualCurrency'] = round($row['debitConvert'],2);
              }
              
              $transactionData[] = $data;
	}
      }
      else
      {
          $transactionData = array();
      }
      
      return json_encode($transactionData);
        
    }
    
    
    /**
     * @author nidhi<nidhi@walkover.in> 
     * @since 18/12/2013
     * @details :: Function to check validation for amount, description and type.
     * @return int (true/error)
     */
    
    function checkTransactionValidation($parm)
    {
        #- Checking for valid transaction type 
        if(isset($parm['transType']) && (preg_match('/[^a-zA-Z0-9\@\_\-\s]+/', $parm['transType']) || strlen(trim($parm['transType'])) < 1 || strlen(trim($parm['transType'])) > 55))
        {
           return json_encode(array("status"=>"error","msg"=>"please enter a valid Transaction Type must not containg any spacial character other than '@','_','-'"));
        }

        #- Checking for valid description 
        if(isset($parm['description']) && (preg_match('/[^a-zA-Z0-9\@\_\-\s]+/', $parm['description']) || strlen(trim($parm['description'])) < 1 || strlen(trim($parm['description'])) > 200))
        {
           return json_encode(array("status"=>"error","msg"=>"please enter a valid Description must not containg any spacial character other than '@','_','-'"));
        }

        #- Checking for valid amount 
        if(isset($parm['amount']) && (!preg_match('/^[0-9]+/', $parm['amount'])))
        {
           return json_encode(array("status"=>"error","msg"=>"please enter a valid amount !"));
        }
        
        return 1;
        
    }
    
    /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @since 02-08-2013
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in>
     * @details :: function use for add and reduce transaction log into transaction table.
     * @date  18/12/2013
     * @return : 
     */
    
    function addReduceTransaction($parm,$userid)
    {
        #- calling function To check validation
        $validateRes = $this->checkTransactionValidation($parm);
        
         if($validateRes != 1)
            return $validateRes;

        #- Checking permission for add transaction or not 
        $resellerId = $this->getResellerId($parm['toUser']);  

        if($resellerId != $userid)
        {
          return json_encode(array("status" => "error", "msg" => "you have no permission for add transaction ."));
        }
        
        $paymentType = $parm['transType'];

        if($parm['transType'] == "Other")
        {
            $paymentType = $parm['transTypeOther'];
        }
        
        $this->fromUser = $userid;
        $this->toUser =  $parm['toUser'];
        
        $result = $this->addTransactional($parm['amount'],0,$paymentType,$parm['description'],'',0,$parm['currency'], 0, $parm['status'] ); 

        if($result)
        {
            $transData = $this->getTransactionLogDetail($this->fromUser,$this->toUser);
            $str = json_decode($transData,TRUE);
            return json_encode(array("status"=>"success","msg"=>"Successfully Transaction Updated !","str"=>$str));   
        }
    }
    
     /**
     * @author sudhir pandey <sudhir@hostnsoft.com>
     * @since 02-08-2013
     * @filesource
     * @modified by: nidhi<nidhi@walkover.in> 
     * @date  18/12/2013
     * @details ::insert userdetail into database  .
     * @return name,userName,currencyId
     */
    
    function getNameAndUserName($toUser)
    {
        #- Insert userdetail into database 
        $table = '91_manageClient';
        
        #- Condition For finding user detail
        $condition = "userId = '" . $toUser . "'";

        $result = $this->selectData( '*', $table, $condition );

        if($result->num_rows > 0)  #- log error
        {
            $row = $result->fetch_array(MYSQL_ASSOC);
            isset($row['name'])? $name = $row['name'] : $name ='';
            isset($row['userName'])? $userName = $row['userName'] : $userName ='';
            isset($row['currencyId'])? $currencyId = $row['currencyId'] : $currencyId ='';
            return array( "name" => $name, "userName" => $userName , "currencyId" => $currencyId);
        }
        else
        {
          trigger_error('Problen while get details for manage client,condition:'.$condition);  
        }
    }
    
   
}//end of class
?>