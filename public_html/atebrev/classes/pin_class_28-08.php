<?php
/**
 * @Author Rahul <rahul@hostnsoft.com>
 * @createdDate 03-06-13
 * 
     */
include dirname(dirname(__FILE__)).'/config.php';
class pin_class extends fun
{
     
	function create_pin($length=5){		
		/**
		 * @author  Rahul <rahul@hostnsoft.com>
		 * @package Pin Generator
		 * @
		 */
		$new = false;
		while($new == false)
		{
			$pin = substr(md5(microtime()),0,$length);		
			$table = '91_pinDetails';
			$this->db->select('pinCode')->from($table)->where("pinCode = '" . $pin . "' ");
			$result = $this->db->execute();
			//	    var_dump($result);
			// processing the query result
			if ($result->num_rows > 0) {
			    $new=false;		    
			}
			else
			    $new=true;
		}
		return $pin;	
	}

        #modified by sudhir pandey (sudhir@hostnsoft.com)
        #modified date 19/07/2013
        #function use for add pin batch 
	function generateBatch($request,$userid)
	{
		//recharge_pin;
		extract($request); //$bname,$totalPins,$expiry_date,$amountPerPin,$pType,$partialAmount,$currency,$tariff_Plan
                
                #check total no of pins is numeric or not 
                if (!preg_match("/^[0-9]+$/", $totalPins)){  
                    $response["msg"]="Number Of PINs Are Not Numeric !";
                    $response["msgtype"]="error";
                    return json_encode($response);
                }
                
                #check amount of  per pins is numeric or not 
                if (!preg_match("/^[0-9]+$/", $amountPerPin)){  
                    $response["msg"]="Amount Per PIN Is Not Numeric !";
                    $response["msgtype"]="error";
                    return json_encode($response);
                }
                
                #check batch name validation
                if($bname == ''){
                    $response["msg"]="Batch Name Required !";
                    $response["msgtype"]="error";
                    return json_encode($response);
                }
                
                if((int)$tariff_Plan == 0){
                    $response["msg"]="Select Tariff Plan !";
                    $response["msgtype"]="error";
                    return json_encode($response);
                }
                
                
                #value for store in database 
                $data=array("userId"=>(int)$userid,"batchName"=>$bname,"noOfPins"=>(int)$totalPins,"pinFormat"=>$pinFormate,"amountPerPin"=>(int)$amountPerPin,"tariffId"=>(int)$tariff_Plan,"expiryDate"=>date('Y-m-d H:i:s',strtotime($expiry_date)),"paymentType"=>$pType,"partialAmount"=>(int)$partialAmount); 
                #table name 
                $table = '91_pin';
		#insert query (insert data into 91_pin table )
                $this->db->insert($table, $data);	
                $this->db->getQuery();
		$result = $this->db->execute();
                
                #save total no of pin with pincode 
		$batchId="";
		if($result)
		{
                        #last inserted id 
			$batchId=$this->db->insert_id;
                        #variabel for store total no to row inserted in pindetail table 	
			$totalInsertedRow=0;
			for($totalPins;$totalPins>0;$totalPins--)
			{
				$pinCode= $batchId.$this->create_pin(8);
                                #data for store in pin details table 
				$data=array("batchId"=>(int)$batchId, "pincode"=>$pinCode,"status"=>0);
				$table = '91_pinDetails';
		                #insert data into details table
				$this->db->insert($table, $data);
                                $this->db->getQuery();
				$result = $this->db->execute();
				$totalInsertedRow +=$this->db->affected_rows;
			}
                        $str = $this->pinListdata($userid);
                        
                        #array for return response
			$response["msg"]="Batch Created Successfully";
			$response["msgtype"]="success";
			$response["batchId"]=$batchId;
			$response["totalInsertedRow"]=$totalInsertedRow;
                        $response["str"]=$str;
                        
		}
		else
		{
			$response["msg"]="Error While Creating Batch";
			$response["msgtype"]="error";
		}
		
		return json_encode($response);
		
	}
        #created by sudhir pandey (sudhir@hostnsoft.com)
        #creation date 20/07/2013
        #function use for edit pin batch detail 
        function editPinBatch($parm,$userid){
            extract($parm); //$bname,$totalPins,$expiry_date,$amountPerPin,$pType,$partialAmount,$currency,$tariff_Plan
            $table='91_pin';
            #check tariff plan is selected or not 
            if((int)$tariffPlan == 0){
                 return json_encode(array("msgtype"=>"error","msg"=>"Select Tariff Plan !"));
                }
            #check amount of  per pins is numeric or not 
                if (!preg_match("/^[0-9]+$/", $amountperpin)){  
                   return json_encode(array("msgtype"=>"error","msg"=>"Please Enter Numeric Value For Amount!"));
                }
                    
                
            #value for store in database 
            $data=array("batchName"=>$batchName,"amountPerPin"=>(int)$amountperpin,"tariffId"=>(int)$tariffPlan,"expiryDate"=>date('Y-m-d H:i:s',strtotime($batchExpiry))); 
            $condition = "userId=".$userid." and batchId=".$batchid." ";
                $this->db->update($table, $data)->where($condition);	
                $this->db->getQuery();
                $result = $this->db->execute();
                $str = $this->pinListdata($userid);
             return json_encode(array("msgtype"=>"success","msg"=>"Batch Updated Successfully !","str"=>$str));
        }
        
        #modified by sudhir pandey (sudhir@hostnsoft.com)
        #modify date 19/07/2013
        #function use for get all pin details batch id and userid given 
	function getPinDetails($batchid,$userid)
	{
		$limit=30;
//		if (isset($page_number))
//		{
//		    $start = ($page_number - 1) * $limit;
//		}
//		else
//			$start = 0;
		$response["batchId"]=$batchid;
		
		$table = '91_pin';
		$this->db->select('*')->from($table)->where("userId = '" . $userid . "' and batchId = '".$batchid."'");
		$this->db->getQuery();
                $batch_result = $this->db->execute();
		//var_dump($result);
		// processing the query result
		if ($batch_result->num_rows > 0) {		
			while ($batch_row= $batch_result->fetch_array(MYSQL_ASSOC) ) {

				$returnResult["id"]=$batch_row["batchId"];
				$returnResult["batch_name"]=$batch_row["batchName"];
				$returnResult["total_pin"]=$batch_row["noOfPins"];				
				//$returnResult["reseller_id"]=$batch_row["reseller_id"];

//				if(isset($tariffArray[$batch_row["tariffId"]]))
//					$returnResult["tariff"]=$tariffArray[$batch_row["tariffId"]];
//				else
//					$returnResult["tariff"]="Name Not Found. id =".$batch_row["tariffId"];

				$returnResult["created_date"]=$batch_row["createDate"];
				$returnResult["expire_date"]=$batch_row["expiryDate"];
				$returnResult["paymentType"]=$batch_row["paymentType"];
                                $returnResult["amountPerPin"]=$batch_row["amountPerPin"];
                                
                                
                                
//				$returnResult["paid_type_flag"]="Postpaid";
//				if($batch_row["paid_type_flag"]==1)
//					$returnResult["paid_type_flag"]="Prepaid";

				$batch_details[] = $returnResult;
			}
		}
		else
			$batch_details=array();
		$response["batch_details"]=$batch_details;
		
		
		$table = '91_pinDetails';
		$this->db->select('*')->from($table)->where("batchId = '" . $batchid . "'");
                $this->db->getQuery();
		$result = $this->db->execute();
//		var_dump($totallresult);
		// processing the query result
		
//		if ($totallresult->num_rows > 0) {
//////			$pages = ceil($totallresult->num_rows / $limit);
//////			$response["totalpage"]=$pages;
////			$this->db->select('*')->from($table)->where("batch_id = '" . $batchId . "' ")->limit($limit)->offset($start);
////			$result = $this->db->execute();
////			//var_dump($result);
////			// processing the query result
			if ($result->num_rows > 0) {	
				while ($row= $result->fetch_array(MYSQL_ASSOC) ) {
					$returnResult["pin_code"]=$row["pincode"];
					//$returnResult["amount"]=$row["amount"];
					$returnResult["status"]=$row["status"];
					if($row["status"]==1)
					{
						$returnResult["status"]="Used";
						$returnResult["used_date"]=$row["usedDate"];
                                                $returnResult["userBy"] = $row["usedBy"];
					}
					elseif($row["status"]==0){
						$returnResult["status"]="Valid";
						$returnResult["used_date"]="0000-00-00 00:00:00";
                                                $returnResult["userBy"] = " - ";
					}
                                        
						$pin[] = $returnResult;
				}
			}
			else
			    $pin=array();			
		
			
		
		    
		
		$response["myPins"]=$pin;
                
		return json_encode($response);
	}
	
        #created by sudhir pandey (sudhir@hostnsoft.com)
        #creation  date 08-08-2013
        #function use for searching batch list by batch name
        function searchBatch($parm,$userid){
           
           #table name  
           $table = '91_pin';
           
           #check for data is not null
           if(isset($parm['data']) &&  strlen($parm['data'])>0){
            $condition = "userId = " . $userid . " and batchName like '%".$parm['data']."%'";
            $this->db->select('*')->from($table)->where($condition);
            $this->db->getQuery();
            $result = $this->db->execute();
            #call function for get all batch detail by result 
            $batch = $this->searchBatch_sub($result);
                
            }else
                $batch = array();
            
            //return $batch;
            return json_encode($batch);
         }
            
                
        
        #created by sudhir pandey (sudhir@hostnsoft.com)
        #creation date 08/08/2013
        #function use for find all batch detail by result  
        function searchBatch_sub($result){
            if ($result->num_rows > 0) {
				while ($row= $result->fetch_array(MYSQL_ASSOC) ) {
				
					$returnResult["id"]=$row["batchId"];
					$returnResult["batch_name"]=$row["batchName"];
					$returnResult["total_pin"]=$row["noOfPins"];				
//					$returnResult["reseller_id"]=$row["reseller_id"];

					if(isset($tariffArray[$row["tariffId"]]))
						$returnResult["tariff"]=$tariffArray[$row["tariffId"]];
					else
						$returnResult["tariff"]="Name Not Found. id =".$row["tariffId"];

					$returnResult["created_date"]=$row["createDate"];
					$returnResult["expire_date"]=$row["expiryDate"];
					$returnResult["amountPerPin"]=$row["amountPerPin"];
					$returnResult["paymentType"]=$row["paymentType"];
					
					$returnResult["action"] = $row["action"];
                                        $returnResult["amountStatus"] = $row["amountStatus"];
					
                                        #find total no of pin used
                                        $pindetail = '91_pinDetails';
                                        $this->db->select('*')->from($pindetail)->where("batchId = '" . $row["batchId"] . "' and status = 1");
                                        $this->db->getQuery();
                                        $usedpin = $this->db->execute();
                                        $returnResult['used_pin'] = $usedpin->num_rows;

					$pin[] = $returnResult;
				}
			}
			else
				$pin=array();
                        
                        return $pin;
        }
        
        
	function getMyPin($userId,$start=0,$limit=30)
	{
		
		$table = '91_pin';
		$condition = "userId = '" . $userId . "' ";
		$this->db->select('*')->from($table)->where($condition);
		$totallresult = $this->db->execute();
//		var_dump($result);
		// processing the query result
		if ($totallresult->num_rows > 0) {	
			$pages = ceil($totallresult->num_rows / $limit);
			$response["totalpage"]=$pages;
					
			$this->db->select('*')->from($table)->where($condition)->limit($limit)->offset($start);
			$result = $this->db->execute();
//			var_dump($result);
//			die();
//			include_once dirname(dirname(__FILE__)).'/action.php';
//					managePlans			
			
			$table="91_plan";
			$this->db->select('*')->from($table);
			$tariffResult = $this->db->execute();
			if ($tariffResult->num_rows > 0) {
				
				while ($tariffRow= $tariffResult->fetch_array(MYSQL_ASSOC) ) {
				$tariffArray[$tariffRow["tariffId"]]=$tariffRow["planName"];					
				}
			}
			$pin = $this->searchBatch_sub($result);
		}
		else{
			$pin=array();
			$response["totalpage"]=0;
		}
		$response["myPins"]=$pin;
		return json_encode($response);
	}
	
	public function findByPin($pin, $start){
	    
	    $totalresult = $this->db->query("SELECT recharge_pin. * , recharge_pin_code.pin_code, tariffsnames.description
					    FROM recharge_pin_code 
					    INNER JOIN recharge_pin ON recharge_pin.id = recharge_pin_code.batch_id
					    INNER JOIN tariffsnames ON recharge_pin.tariffid = tariffsnames.id_tariff
					    WHERE recharge_pin_code.pin_code LIKE '".$pin."%' LIMIT ".$start.",10");
	    $response["myPins"] = $this->getPin($totalresult);
	    $response["totalpage"] = ceil($totalresult->num_rows /10);
	    return json_encode($response);
	}
	
	public function getpin($result){
	    $pin = array();
	    if ($result->num_rows > 0) {
		while ($row= $result->fetch_array(MYSQL_ASSOC) ) {
		    
			$returnResult["id"]=$row["id"];
			$returnResult["batch_name"]=$row["batch_name"];
			$returnResult["total_pin"]=$row["total_pin"];				
			$returnResult["reseller_id"]=$row["reseller_id"];

			if(isset($tariffArray[$row["tariffid"]]))
				$returnResult["tariff"]=$tariffArray[$row["tariffid"]];
			else
				$returnResult["tariff"]="Name Not Found. id =".$row["tariffid"];

			$returnResult["created_date"]=$row["created_date"];
			$returnResult["expire_date"]=$row["expire_date"];
			$returnResult["paid_type_flag"]="Postpaid";
			$returnResult["b_status"] = $row["b_status"];
			$returnResult["tariff"] = $row["description"];
			if($row["paid_type_flag"]==1)
				$returnResult["paid_type_flag"]="Prepaid";

			$pin[] = $returnResult;
		}
	    }
	    return $pin;
	}
	
	
	function rechargeByPin($request,$session)
	{
		extract($request);
		$userid = $session["userid"];
		$batchId;
		if(!isset($pin) || strlen($pin)<5)
		{
			$returnResult["msg"]="Invalid Pin";;
			return json_encode($returnResult["msg"]);
		}	
		$table = 'recharge_pin_code';
		$this->db->select('*')->from($table)->where("pin_code = '" . $pin . "'");
		$pinresult = $this->db->execute();
		// processing the query result
//		echo " ddd ".$pinresult->num_rows." aa ";
		if ($pinresult->num_rows > 0) {
			while ($row= $pinresult->fetch_array(MYSQL_ASSOC) ) {
				$returnResult["pin_code"]=$row["pin_code"];
				if($row["status"]==0)
				{
				    $batchId = $row['batch_id'];
				    $this->db->select('b_status')->from('recharge_pin')->where('id = '.$batchId);
				    $result = $this->db->execute();
				    while ($row = $result->fetch_array(MYSQL_ASSOC) ) {
					if($row['b_status']){
					    $data = array('status'=>1);
					    $condition = " batch_id=".$batchId." AND pin_code='".$pin."'";
					    $this->db->update($table, $data)->where($condition);		
					    if($result = $this->db->execute())
						    echo 1;
					}
					   
				    } 
					
					
				}
				
			}
			
			//$response["totalpage"]=$pages;
			$this->db->select('*')->from($table)->where("batch_id = '" . $batchId . "' ")->limit($limit)->offset($start);
			$result = $this->db->execute();
			
			//var_dump($result);
			// processing the query result
			if ($result->num_rows > 0) {	
				while ($row= $result->fetch_array(MYSQL_ASSOC) ) {
					$returnResult["pin_code"]=$row["pin_code"];
					$returnResult["amount"]=$row["amount"];
					$returnResult["status"]=$row["status"];
					if($row["status"]==1)
					{
						$returnResult["status"]="Used";
						$returnResult["used_date"]=$row["used_date"];
					}
					elseif($row["status"]==0){
						$returnResult["status"]="Valid";
						$returnResult["used_date"]="0000-00-00 00:00:00";
					}
						$pin[] = $returnResult;
				}
		
			}
			else
			    $pin=array();			
		}
		else{
			$pin=array();
			$response["totalpage"]=0;
		}
		    
		
		$response["myPins"]=$pin;
		return json_encode($response);
	}
	function batchStatus($request){
	    $explode_value = explode("_",$request['value']);
	    $col = ($explode_value[0] == 'paid' || $explode_value[0] == 'unPaid')? 'paid_type_flag':'b_status';
	    $value = (($explode_value[0] == 'paid') || ($explode_value[0] == 'resume'))?1:0;
	    $data = array($col=>$value);
	    $table = "recharge_pin";
	    $this->db->update($table, $data)->where("id =".$explode_value[1]);		
	    $result = $this->db->execute();
	    return $result;
	}
	
        #created by sudhir pandey (sudhir@hostnsoft.com)
        #creation date 26-07-2013
        #function ues for create design of pin list 
        function pinListdata($userid){
            
            $pindata = $this->getMyPin($userid);
            $pinData = json_decode($pindata, true);  
            $str='';
            foreach($pinData['myPins'] as $pinDetails){
        $str.='<li class="selected" onclick="showPinDetail(this);" batchid="'.$pinDetails['id'].'" >
                    <div class="mpinrow clear">
                            <p>
                            <i class="ic-24 und"></i>
                            <span class="ellp">'.$pinDetails["batch_name"].'</span>
                        </p>

                        <p>
                            <span>
                                <label class="ic-24 playR cp" for="changeAct" onclick="$(this).toggleClass(stopR)"></label>
                                <input type="checkbox" checked="checked" style="display:none" id="changeAct">
                            </span>
                            <span class="biller">
                                <label class="ic-bl paid cp" for="changebillType" onclick="$(this).toggleClass(unpaid)"></label>
                                <input type="checkbox" checked="checked" style="display:none" id="changebillType">
                            </span>
                        </p>
                    </div>
                    <h3 class="ellp">'.$pinDetails["batch_name"].'</h3>
                    <p class="dt">Tariff '.$pinDetails["tariff"].'</p>
                    <h3 class="mrT1">'.$pinDetails["used_count"].'/'.$pinDetails["total_pin"].'<span>pins</span> | '.$pinDetails["amountPerPin"].'<span>'.$pinDetails["batch_name"].'/pin</span></h3>
                    <p class="exp">Expire on '.$pinDetails["expire_date"].'| '.$pinDetails["paymentType"].'</p>
                </li>
                    ';
            }
            return $str;
        }

        #created by sudhir pandey (sudhir@hostnsoft.com)
        #creation date 07-08-2013
        #function used to change pin batch action enable or disable (1 for enable and 0 for disable)
        function changeBatchAction($parm,$userId){
            
            
            #get batch pin id for request 
            $batchId = $parm['batchId'];
            
            #get action for update batchpin 1 for enable and 0 for disable
            $action = $parm['batchAction'];
            
            #check permission for change action or not , if pin generated by login user then change pin action otherwise can't change 
            $table = '91_pin';
            $condition = "batchId = '" . $batchId . "' ";
            $this->db->select('*')->from($table)->where($condition);
            $totallresult = $this->db->execute();

            // processing the query result
            if ($totallresult->num_rows > 0) {	
                $row= $totallresult->fetch_array(MYSQL_ASSOC);
                $batchCreator = $row['userId'];
            }
            if($batchCreator != $userId){
                 return json_encode(array('status'=>'error','msg'=>'You have no permission for change Batch Pin action!')); 
            }
            
            #update action in batch pin (enable or disable )
            $data=array("action"=>$action); 
            $condition = "batchId=".$batchId." ";
            $this->db->update($table, $data)->where($condition);	
            $this->db->getQuery();
            $result = $this->db->execute();
            
            if(!$result){
                return json_encode(array('status'=>'error','msg'=>'Batch Pin action not updated!')); 
            }else
                return json_encode(array('status'=>'success','msg'=>'successfully update Batch Pin action!')); 
            
        }
        
        
        #created by sudhir pandey (sudhir@hostnsoft.com)
        #creation date 12-08-2013
        #function used to change pin batch amount status paid or unpaid (1 for paid and 0 for unpaid).
        function pinBatchAmountStatus($parm,$userId){
            
            
            #get batch pin id for request 
            $batchId = $parm['batchId'];
            
            #get action for update batchpin 1 for paid and 0 for unpaid
            $amountStatus = $parm['amountStatus'];
            
            #check permission for change batch status or not , if pin generated by login user then change pin action otherwise can't change 
            $table = '91_pin';
            $condition = "batchId = '" . $batchId . "' ";
            $this->db->select('*')->from($table)->where($condition);
            $totallresult = $this->db->execute();

            // processing the query result
            if ($totallresult->num_rows > 0) {	
                $row= $totallresult->fetch_array(MYSQL_ASSOC);
                $batchCreator = $row['userId'];
            }
            if($batchCreator != $userId){
                 return json_encode(array('status'=>'error','msg'=>'You have no permission for change Batch status!')); 
            }
            
            #update status of batch pin (paid or unpaid)
            $data=array("amountStatus"=>$amountStatus); 
            $condition = "batchId=".$batchId." ";
            $this->db->update($table, $data)->where($condition);	
            $this->db->getQuery();
            $result = $this->db->execute();
            
            if(!$result){
                return json_encode(array('status'=>'error','msg'=>'Batch Pin status not updated!')); 
            }else
                return json_encode(array('status'=>'success','msg'=>'successfully update Batch Pin status!')); 
            
        }
        
        #Created by Balachandra<balachandra@hostnsoft.com>
        #Date 31/01/2013      
        #function used to delete the Batchpin
        function deleteBatchPin($batchid,$userid)
        {
            
            #$table name of the table in database
            $table = '91_pinDetails';
            
            #selecting the item from table 91_pinDetails where batchid must be matching and status will be 1
            $this->db->select('*')->from($table)->where("batchId ='".$batchid."' and status = 1");
            $this->db->getQuery();
            
            
            
            
            #execute query
            $result=$this->db->execute();

            #check the resulting value exists or not 
            if($result->num_rows > 0)
              {
                    #return the message in json format
                    return json_encode(array('msgtype'=>'error','msg'=>'Pin Batch Can Not Be Deleted Because Some PINs Are Used'));
              }
            
            #then no data undergoing if condition
            else 
              {
                    #delete the data from the table 91_pinDetails with matching batchid         
                    $this->db->delete($table)->where("batchId = '" . $batchid."'" );
                    $this->db->getQuery();
                    #execute the query
                    $result1 = $this->db->execute(); 

                    #$temp conatains the table name 91_pin 
                    $temp='91_pin';
                    
                    #delete the data from table 91_pin
                    $this->db->delete($temp)->where("userid = '" .$userid."' and  batchId = '".$batchid."'");
                    $this->db->getQuery();
                    
                    #execute the query
                    $result2 = $this->db->execute();
                    
                    #listing all pindata and keep it in variable $str, $str calling function pinListdata
                    $str = $this->pinListdata($userid);
                    if($result1 && $result2)
                        {
                            #return in the json format
                            return json_encode(array('msgtype'=>'success','msg'=>'Deleted Successfully',"str"=>$str));
                        } 
                    else 
                        {
                            #deletion condition failed
                            return json_encode(array('msgtype'=>'error','msg'=>'Not Possible To Delete')); 
                         }
              }
                        
                    

         } 
 }//end of class
?>