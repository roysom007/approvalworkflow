<?php
/*
FileName :CommonApprovalFlowFunctionModel.php
Author   :eButor
Description : Approval Flow  related functions are here.
CreatedDate :22/jul/2016
*/

//defining namespace
namespace App\Modules\ApprovalEngine\Models;

use Illuminate\Database\Eloquent\Model;
use App\Central\Repositories\ProductRepo;
use Carbon;
use Mail;
use DB;
use Notifications;

class CommonApprovalFlowFunctionModel extends model
{
    protected $table = 'appr_workflow_history';
  	protected $primaryKey = 'awf_history_id';

    /*
    Name : notifyUserForFirstApproval
    Desc : This function will notify the user which is related to the first lbl Approval, also will send them the first Status
    Params : @flowType, @userID
    */
    public function notifyUserForFirstApproval($flowType, $flowTypeForID, $userID){

        $params = array(
            'FlowName'      => $flowType,
            'UserID'        => $userID
        );

        // Checking for the Invalid Input
        if( $flowType=='' || $userID==''){
            $finalFlowData['status'] = "400";
            $finalFlowData['message'] = "Invalid input";

            // insert the call details into the table
            $tableID = DB::table('appr_workflow_call_details')->insert(
                ['appr_call_for' => $flowType, 'appr_name' => "notifyUserForFirstApproval", 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData), 'appr_call_input' => json_encode($params)]
            );

            return $finalFlowData;
        }else{

            // Get the Approval Flow ID from Master-lookup
            $flowID = DB::table("master_lookup")
                        ->select("value")
                        ->where("mas_cat_id","=","56")
                        ->where("master_lookup_name","=",$flowType)
                        ->first();

            // Get the UserID and information
            $userDetails = DB::table("users")
                        ->where("user_id","=", $userID)
                        ->first();

            // IF User or the FlowName not found in the Table
            if(!$flowID || !$userDetails){

                $finalFlowData['status'] = "400";
                $finalFlowData['message'] = "Invalid User or Flow Name, Bad request!";

                $tableID = DB::table('appr_workflow_call_details')->insert(
                    ['appr_call_for' => $flowType, 'appr_name' => "notifyUserForFirstApproval", 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData), 'appr_call_input' => json_encode($params)]
                );

                return $finalFlowData;
            }

            // Get the First Status of the Flow to send the Current Status ID
            $firstStatus = DB::table("appr_workflow_status_new AS awf")
                        ->join("appr_workflow_status_details AS det", "det.awf_id", "=", "awf.awf_id")
                        ->where("awf.awf_for_id","=", $flowID->value)
                        ->where("awf.legal_entity_id", "=", $userDetails->legal_entity_id)
                        ->orderBy("awf_det_id", "ASC")
                        ->limit(1)
                        ->first();

            // IF User or the FlowName not found in the Table
            if(!$firstStatus){

                $finalFlowData['status'] = "400";
                $finalFlowData['message'] = "Flow status not found with the user combination (".$userDetails->legal_entity_id.")!";

                $tableID = DB::table('appr_workflow_call_details')->insert(
                    ['appr_call_for' => $flowType, 'appr_name' => "notifyUserForFirstApproval", 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData), 'appr_call_input' => json_encode($params)]
                );

                return $finalFlowData;
            }

            // Check for the Role is a Imidiate Reporter or not
            $roleDetails = DB::table("roles")
                        ->select("name")
                        ->where("role_id", "=", $firstStatus->applied_role_id)
                        ->first();

            // if Role is ImmidiateRepoter then send the mail to his reporting manager
            $toEmails = array();
            $userIDs = array();

            if($roleDetails->name=='ImmediateReporter'){

                $getUserForMail = DB::table("users")
                        ->where("user_id", "=", $userDetails->reporting_manager_id)
                        ->first();

                $toEmails[] = $getUserForMail->email_id;
                $userIDs[] = $getUserForMail->user_id;

            }elseif ($roleDetails->name=='Initiator') {

                 $getUserIdForMail = DB::table("users")
                        ->where("user_id", "=", $userID)
                        ->first();


                $toEmails[] = $getUserIdForMail->email_id;
                $userIDs[] = $getUserIdForMail->user_id;

            }else{

                $getUserForMail = DB::table("users AS usr")
                        ->join("user_roles AS rls", "rls.user_id", "=", "usr.user_id")
                        ->where("rls.role_id", "=", $firstStatus->applied_role_id)
                        ->get();

                foreach($getUserForMail as $userData){
                    $toEmails[] = $userData->email_id;
                    $userIDs[] = $userData->user_id;
                }
            }

            // =======================================================
            // Save Information Into the History Table
            // ======================================================
            $dataToSave = array(
                    'awf_for_type'          => $flowType,
                    'awf_for_type_id'       => $flowID->value,
                    'awf_for_id'            => $flowTypeForID,
                    'awf_comment'           => "First Ticket for Initiator, Created by System",
                    'status_from_id'        => $firstStatus->awf_status_id,
                    'status_to_id'          => $firstStatus->awf_status_to_go_id,
                    'user_id'               => $userID,
                    'next_lbl_role'         => $firstStatus->applied_role_id,
                    'is_final'              => 0,
                    'condition_id'          => $firstStatus->awf_condition_id,
                    'ticket_created_by'     => $userID,
                    'created_by_manager'    => $userDetails->reporting_manager_id,
                    'created_by'            => $userID,
                    'created_at'            => date('Y-m-d H:i:s')
            );

            //Insert the data into History table and then send the email notification
            $this->insert($dataToSave);

            // =========================================================
            // Get user Name || Prepare the Email Content 
            // =========================================================
            $userName = isset($userDetails->firstname) ? $userDetails->firstname : 'Unknown User';

            $emailContent = "A Ticket is raised for " . $flowType . "(<a href='".$firstStatus->redirect_url."'>".$flowTypeForID."</a>)<br><br>";
            $emailContent .= "Ticket No :  TKT" . $flowTypeForID."<br>";
            $emailContent .= "Assigned By : " . $userName . "<br><br>";
            $emailContent .= "Please reffer to <a href='".url("/")."/approvalworkflow/approvalticket'>Approval Ticket Page</a> for more details.<br><br> Thanks";

            $emailFlag = 1;
            $notificaionFlag = 1;
            if($emailFlag == 1 ){
                if( count($toEmails)>0 ){

                    Mail::send('emails.approvalWorkflowNotificationMail', ['emailContent' => $emailContent], function ($message) use ($toEmails, $flowTypeForID) {
                        $message->to($toEmails);
                        $message->subject('Your Approval Is Pending For - TKT' . $flowTypeForID);
                    });

                }
            }

            // Send Mobile Notifications
            $this->sendMobileNotification($userIDs);

            $finalFlowData['status']            = "200";
            $finalFlowData['message']           = "Call Successful";
            $finalFlowData['currentStatusId']   = $firstStatus->awf_status_id;

            // insert the call details into the table
            $tableID = DB::table('appr_workflow_call_details')->insert(
                ['appr_call_for' => $flowType, 'appr_name' => "notifyUserForFirstApproval", 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData), 'appr_call_input' => json_encode($params)]
            );

            return $finalFlowData;

        }
    }


    /*
    Name : getApprovalFlowDetails
    Desc : Get all the approval flow and retuns it as per the user roles
    Params : @flowType, @currentStatusID, @userID
    */
    public function getApprovalFlowDetails($flowType, $currentStatusID, $userID, $yourTableID=""){

        // start the flow here
        $finalFlowData = array();
        $responseBody = array();

		if( $flowType=='' || $currentStatusID=='' || $userID==''){
			$finalFlowData['status'] = "0";
            $finalFlowData['message'] = "Invalid input";

            // insert the call details into the table
            $tableID = DB::table('appr_workflow_call_details')->insert(
                ['appr_call_for' => $flowType, 'appr_current_status_id' => $currentStatusID, 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData)]
            );

			return $finalFlowData;
		}else{

            // get LegalEntiry ID
            $legalEntiryID = $this->getUserLegalEntity($userID);
            if($legalEntiryID==0 || $legalEntiryID==''){

                $finalFlowData['status'] = "0";
                $finalFlowData['message'] = "Invalid UserID or legalEntityID";

                // insert the call details into the table
                $tableID = DB::table('appr_workflow_call_details')->insert(
                    ['appr_call_for' => $flowType, 'appr_current_status_id' => $currentStatusID, 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData)]
                );

                return $finalFlowData; 
            }

			$flow_for_id = $this->getFlowForID($flowType);

            // Take the currentStat if it is Drafted
            if($currentStatusID=='drafted'){
                $sqlQuery = "select * 
                    FROM master_lookup AS ml 
                    WHERE ml.`parent_lookup_id`= (SELECT ml.`master_lookup_id` parent_lookup_id FROM master_lookup AS ml WHERE ml.`value`=".$flow_for_id." LIMIT 0,1)
                    AND ml.`master_lookup_name`='drafted'";

                $allData = DB::select(DB::raw($sqlQuery));
                if( isset($allData[0]->value) ){
                    $currentStatusID = $allData[0]->value;
                }else{
                    $currentStatusID =0;
                }
            }

            
			// check for the valid status
	        $checkStatusExist = DB::table('appr_workflow_status_new AS aws')
                      ->join("appr_workflow_status_details AS det", "det.awf_id", "=", "aws.awf_id")
                      ->where("aws.awf_for_id", "=", $flow_for_id)
                      ->where("aws.legal_entity_id", "=", $legalEntiryID)
                      ->where('det.awf_status_id', '=', $currentStatusID)
                      ->get();

            $checkStatusToGoExist = DB::table('appr_workflow_status_new AS aws')
                      ->join("appr_workflow_status_details AS det", "det.awf_id", "=", "aws.awf_id")
                      ->where("aws.awf_for_id", "=", $flow_for_id)
                      ->where("aws.legal_entity_id", "=", $legalEntiryID)
                      ->where('det.awf_status_to_go_id', '=', $currentStatusID)
                      ->count();

            // if status not exit send response
            if($checkStatusExist==0 && $checkStatusToGoExist==0){
            	$finalFlowData['status'] = "0";
            	$finalFlowData['message'] = "Invalid Status Sent or wrong legalEntityID";
                // insert the call details into the table
                $tableID = DB::table('appr_workflow_call_details')->insert(
                    ['appr_call_for' => $flowType, 'appr_current_status_id' => $currentStatusID, 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData)]
                );
            	return $finalFlowData;
            }elseif($checkStatusExist==0 && $checkStatusToGoExist==1){
            	$finalFlowData['status'] = "1";
            	$finalFlowData['message'] = "No next lavel found";
            	$finalFlowData['data'] = array(
            			'0' => 'Done'
            		);
                // insert the call details into the table
                $tableID = DB::table('appr_workflow_call_details')->insert(
                    ['appr_call_for' => $flowType, 'appr_current_status_id' => $currentStatusID, 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData)]
                );
            	return $finalFlowData;
            }

	        // get all the flow as per current status
	        $nextFlowData = DB::table('appr_workflow_status_new AS aws')
                      ->select('aws.awf_id', 'det.awf_status_id', 'aws.awf_name', 'aws.awf_for_id', 'det.awf_condition_id', 'det.awf_status_to_go_id', 'det.applied_role_id', 'det.is_final')
                      ->join("appr_workflow_status_details AS det", "det.awf_id", "=", "aws.awf_id")
                      ->where("aws.awf_for_id", "=", $flow_for_id)
                      ->where("aws.legal_entity_id", "=", $legalEntiryID)
                      ->where('det.awf_status_id', '=', $currentStatusID)
                      ->get();

            if(count($nextFlowData)>0){
            	$loopCounter = 0;
            	foreach($nextFlowData as $data){

                    $getdataDet = DB::table("master_lookup")
                                    ->where("value","=",$data->awf_condition_id)
                                    ->where("mas_cat_id","=",58)
                                    ->first();

                    $isFinal = $data->is_final;
                    if($getdataDet->master_lookup_name == "Rejected" && $data->is_final=="1"){
                        $isFinal = 0;
                    }

                    // Check if the rolese assigned to Immidiate repoter
                    $roleName = "";
                    $roleName = DB::table("roles")->select("name")->where("role_id","=",$data->applied_role_id)->first();
                    if($roleName){
                        $roleName=$roleName->name;
                    }

                    $countUserRole = 0;
                    if($roleName=='ImmediateReporter'){

                        // get Record Submitted by ID
                        $submittedByID = DB::table("appr_workflow_history AS hist")
                                        ->where("hist.awf_for_type", "=", $flowType)
                                        ->where("hist.awf_for_id", "=", $yourTableID)
                                        ->first();


                        if($submittedByID){

                            $countUserRole = DB::table("users")
                                    ->select("reporting_manager_id")
                                    ->where("user_id","=", $submittedByID->ticket_created_by)
                                    ->where("reporting_manager_id","=", $submittedByID->created_by_manager)
                                    ->count();
                        }

                    }elseif($roleName=='Initiator'){

                        // get Record Submitted by ID
                        $submittedByID = DB::table("appr_workflow_history AS hist")
                                        ->where("hist.awf_for_type", "=", $flowType)
                                        ->where("hist.awf_for_id", "=", $yourTableID)
                                        ->first();

                        if($submittedByID->ticket_created_by == $userID){
                            $countUserRole = 1;
                        }

                    }else{

                        // checks for the user access to the flow
                        $countUserRole = DB::table('user_roles AS rls')
                            ->where('rls.user_id', '=', $userID)
                            ->where('rls.role_id', '=', $data->applied_role_id)
                            ->count();
                    }

		          	if($countUserRole>0){

		          		$conditionName = DB::table('master_lookup')
		          						->where('value','=',$data->awf_condition_id)
		          						->first();
		          		$conditionName = $conditionName->master_lookup_name;
		          		$statusToGoName = DB::table('master_lookup')
		          						->where('value','=',$data->awf_status_to_go_id)
		          						->first();
		          		$statusToGoName = $statusToGoName->master_lookup_name;

		            	$responseBody[$loopCounter]['conditionId'] = $data->awf_condition_id;
		            	$responseBody[$loopCounter]['condition'] = $conditionName;
						$responseBody[$loopCounter]['nextStatusId'] = $data->awf_status_to_go_id;
						$responseBody[$loopCounter]['nextStatus'] = $statusToGoName;
						$responseBody[$loopCounter]['isFinalStep'] = $isFinal;
						$loopCounter++;
		          	}
	        	}
            }

            if(count($responseBody)==0){

                $addedMsg = $yourTableID=="" ? " or table ID could be blanck!" : "";

            	$finalFlowData['status'] = "0";
            	$finalFlowData['message'] = "User does not have role".$addedMsg;


            }else{

            	$currentStatusName = DB::table('master_lookup')
          						->where('value','=',$data->awf_status_id)
          						->first();
          		$currentStatusName = $currentStatusName->master_lookup_name;


            	$finalFlowData['status'] = "1";
            	$finalFlowData['message'] = "Flow found";
            	$finalFlowData['currentStatusName']=$currentStatusName;
            	$finalFlowData['currentStatusId']=$data->awf_status_id;
            	$finalFlowData['data'] = $responseBody;
            }     
            // insert the call details into the table
            $tableID = DB::table('appr_workflow_call_details')->insert(
                ['appr_call_for' => $flowType, 'appr_current_status_id' => $currentStatusID, 'appr_call_user_id'=> $userID, 'appr_call_made_at' => date('Y-m-d h:i:s'), 'appr_call_response' =>  json_encode($finalFlowData)]
            );
        	return $finalFlowData;
      	}
    }

    /*
    Name : storeWorkFlowStory
    Desc : Store work flow story in a flat table 
    Params : @flowType, @flowTypeForID, @currentStatusID, @nextStatusId, @userID
    */
    public function storeWorkFlowHistory($flowType, $flowTypeForID, $currentStatusID, $nextStatusId, $flowComment, $userID){

    	$currentTime = Carbon\Carbon::now();
    	$currentTime = $currentTime->toDateTimeString();

        // get LegalEntiry ID
        $legalEntiryID = $this->getUserLegalEntity($userID);

        // get the ID for Approval Type
        $flow_for_id = $this->getFlowForID($flowType);

        // get next Role ID
        $nextLblRole = DB::table('appr_workflow_status_new AS awf')
                    ->select("awf.awf_id", "awf.awf_name", "det.applied_role_id", "det.is_final")
                    ->join("appr_workflow_status_details AS det", "det.awf_id", "=", "awf.awf_id")
                    ->where("awf.awf_for_id", '=', $flow_for_id)
                    ->where("det.awf_status_id", '=', $nextStatusId)
                    ->where("awf.legal_entity_id", '=', $legalEntiryID)
                    ->first();
        $nextLblRoleID = count($nextLblRole)>0 ? $nextLblRole->applied_role_id : 0;

        // get flow is final flag
        $currentStatusData = DB::table('appr_workflow_status_new AS awf')
                    ->select("awf.awf_id", "awf.awf_name", "det.applied_role_id", "det.is_final", "det.awf_condition_id")
                    ->join("appr_workflow_status_details AS det", "det.awf_id", "=", "awf.awf_id")
                    ->where("awf.awf_for_id", '=', $flow_for_id)
                    ->where("det.awf_status_id", '=', $currentStatusID)
                    ->where("det.awf_status_to_go_id", "=", $nextStatusId)
                    ->where("awf.legal_entity_id", '=', $legalEntiryID)
                    ->first();
        $isFinalFlag = count($currentStatusData)>0 ? $currentStatusData->is_final : 0;
        $conditionID = count($currentStatusData)>0 ? $currentStatusData->awf_condition_id : 0;

        // Update previous data with isfinal 1
        $getPreviousHistoryID = DB::table('appr_workflow_history AS hist')
                                ->where("hist.awf_for_id", "=", $flowTypeForID)
                                ->orderBy("hist.awf_history_id", "desc")
                                ->first();
        $previousHistoryID = count($getPreviousHistoryID)>0 ? $getPreviousHistoryID->awf_history_id : 0;
        // update Table with 1
        DB::table('appr_workflow_history AS hist')
            ->where("hist.awf_history_id", "=", $previousHistoryID)
            ->update( ['is_final' => '1'] );


        //==================================================================================//
        // Save the data in history table
        //==================================================================================//
        // Get the User ID and Reporting Manager from History Table,  assuming this two data are same for a ticket
        $ticketCrID = count($getPreviousHistoryID)>0 ? $getPreviousHistoryID->ticket_created_by : 0;
        $ticketCrMgrID = count($getPreviousHistoryID)>0 ? $getPreviousHistoryID->created_by_manager : 0;

        $dataToSave = array(
            'awf_for_type'                  => $flowType,
            'awf_for_type_id'               => $flow_for_id,
            'awf_for_id'                    => $flowTypeForID,
            'awf_comment'		            => $flowComment,
            'status_from_id'	            => $currentStatusID,
            'status_to_id'		            => $nextStatusId,
            'user_id'			            => $userID,
            'next_lbl_role'                 => $nextLblRoleID,
            'is_final'                      => $isFinalFlag,
            'condition_id'                  => $conditionID,
            'ticket_created_by'             => $ticketCrID,
            'created_by_manager'            => $ticketCrMgrID,
            'created_by'		            => $userID,
            'created_at'		            => $currentTime
        );

    	//Insert the data into History table and then send the email notification
    	if ($this->insert($dataToSave)){
        //if(true){

    		// On successfully save, sending the mail to the next lavel Users

            // get approval details for notificaion or email
            $getApprovalDetails = DB::table("appr_workflow_status_new")
                                ->where("awf_for_id","=",$flow_for_id)
                                ->where("legal_entity_id", '=', $legalEntiryID)
                                ->first();

            $emailFlag = 0;
            $notificaionFlag = 0;
            $redirectURL = url("/")."/approvalworkflow/approvalticket";

            if($getApprovalDetails){
                $emailFlag = $getApprovalDetails->awf_email;
            }
            if($getApprovalDetails){
                $notificaionFlag = $getApprovalDetails->awf_notification;
            }
            if($getApprovalDetails){
                $redirectURL = $getApprovalDetails->redirect_url;
                $redirectURL = str_replace("##", $flowTypeForID, $redirectURL);
            }

            // Get the First record of the history
            $getFirstRecord = DB::table("appr_workflow_history")
                            ->where("awf_for_type_id", "=",  $flow_for_id)
                            ->where("awf_for_id", "=", $flowTypeForID)
                            ->where("status_to_id", "=", $nextStatusId)
                            ->orderBy("awf_history_id", "DESC")
                            ->limit(1)
                            ->first();

            $toEmails = array();
            $userIDs = array();

            // Check for the Role is a Imidiate Reporter or not
            $roleDetails = DB::table("roles")
                    ->select("name")
                    ->where("role_id", "=", $getFirstRecord->next_lbl_role)
                    ->first();

            if($roleDetails){


                if($roleDetails->name=='ImmediateReporter'){

                    $getUserForMail = DB::table("users")
                            ->where("user_id", "=", $ticketCrMgrID)
                            ->first();

                    $toEmails[] = $getUserForMail->email_id;
                    $userIDs[] = $getUserForMail->user_id;

                }elseif ($roleDetails->name=='Initiator') {

                     $getUserIdForMail = DB::table("users")
                            ->where("user_id", "=", $ticketCrID)
                            ->first();

                    $toEmails[] = $getUserIdForMail->email_id;
                    $userIDs[] = $getUserIdForMail->user_id;

                }else{


                    $userInformation = DB::table('appr_workflow_status_new AS awf')
                        ->select("awf.awf_id", "awf.awf_name", "rls.user_roles_id", "urs.user_id", "urs.firstname", "urs.lastname", "urs.email_id", "det.applied_role_id")
                        ->join("appr_workflow_status_details AS det", "det.awf_id", "=", "awf.awf_id")
                        ->join("user_roles as rls","rls.role_id", "=", "det.applied_role_id")
                        ->join("users as urs", "urs.user_id", "=", "rls.user_id")
                        ->where("awf.awf_for_id", '=', $flow_for_id)
                        ->where("det.awf_status_id", '=', $nextStatusId)
                        ->where("awf.legal_entity_id", '=', $legalEntiryID)
                        ->distinct()
                        ->get();

                    foreach($userInformation as $userData){
                        $toEmails[] = $userData->email_id;
                        $userIDs[] = $userData->user_id;
                    }
                }
            }

            // =========================================================
            // Get user Name || Prepare the Email Content 
            // =========================================================
            $userName = DB::table('users')
                        ->where("user_id", "=", $userID)
                        ->first();
            $userName = isset($userName->firstname) ? $userName->firstname : 'Unknown User';

            $emailContent = "A Ticket is raised for " . $flowType . "(<a href='".$redirectURL."'>".$flowTypeForID."</a>)<br><br>";
            $emailContent .= "Ticket No :  TKT" . $flowTypeForID."<br>";
            $emailContent .= "Assigned By : " . $userName . "<br><br>";
            $emailContent .= "Please reffer to <a href='".url("/")."/approvalworkflow/approvalticket'>Approval Ticket Page</a> for more details.<br><br> Thanks";
            //==========================================================

            if($emailFlag == 1 ){
    			if( count($toEmails)>0 ){

    	            Mail::send('emails.approvalWorkflowNotificationMail', ['emailContent' => $emailContent], function ($message) use ($toEmails, $flowTypeForID) {
    	                $message->to($toEmails);
    	                $message->subject('Your Approval Is Pending For - TKT' . $flowTypeForID);
    	            });

    			}
            }

            if($notificaionFlag == 1){
                if( count($userIDs)>0 ){
                    Notifications::addNotification(['note_code' => 'DEFAULT','note_message' => 'You have an Approval for '.$flowType.' pending', 'note_users' => $userIDs]);
                }
            }
    		return true;
    	}else{
    		return false;
    	}
    }

    private function sendMobileNotification($userIds){

        // Push Notification Function
        $message = "";
        $tokenDetails = "";
        if($userIds){

            // Get User as per Role
            $userIds = implode($userIds, ",")
            $RegId = $this->getRegId($userIds[0]->UserIDs);
            $tokenDetails = json_decode((json_encode($RegId)), true);

            // Get value from user table
            $submitedByName = $this->objAPIModel->getDataFromTable("users", "user_id", $mainTableData[0]->submited_by_id);
            if($submitedByName){
                $submitedByName = $submitedByName[0]->firstname . ' ' . $submitedByName[0]->lastname;
            }else{
                $submitedByName = 'An employee ID : ' . (isset($submitedByName[0]->user_id));
            }
            
            // get value from master lookup table
            $requestTypeName = $this->objAPIModel->getDataFromTable("master_lookup", "value", $mainTableData[0]->exp_req_type);
            if($requestTypeName){
                $requestTypeName = $requestTypeName[0]->master_lookup_name;
            }else{
                $requestTypeName = ' ';
            }

            $message = $submitedByName . ' requested for ' . $requestTypeName . ' of : Rs. ' .  $mainTableData[0]->exp_actual_amount . ', Waiting for your Action!';
            
            $pushNotification = $this->objPushNotification->pushNotifications($message, $tokenDetails);
        }

    }

    //Get Registration Id 
    private function getRegId($userIds){

        $sqlUser = "select registration_id, platform_id FROM device_details WHERE user_id IN (".$userIds.")";
        $allData = DB::select(DB::raw($sqlUser));
        return $allData;
    }


    private function getFlowForID($flowForName){

        $flowForID = DB::table("master_lookup")
                    ->where("mas_cat_id","=","56")
                    ->where("master_lookup_name","=",$flowForName)
                    ->first();

        if(count($flowForID)>0){
            return $flowForID->value;
        }else{
            return "0";
        }
    }

    // This function returns legalentity ID for the user
    private function getUserLegalEntity($userID){
        $userLGL = DB::table("users")
                    ->where("user_id", "=", $userID)
                    ->first();

        if($userLGL){
            return $userLGL->legal_entity_id;
        }else{
            return 0;
        }
    }
}