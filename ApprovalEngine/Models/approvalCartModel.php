<?php
/*
FileName :ApprovalEngineModel.php
Author   :eButor
Description : Approval workflow related functions are here.
CreatedDate :15/jul/2016
*/
//defining namespace
namespace App\Modules\ApprovalEngine\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;
use UserActivity;

class approvalCartModel extends model{

	//protected $table = 'appr_workflow_status';
	protected $table = 'appr_workflow_status_new';
	protected $primaryKey = 'awf_id';

	public function approvalFlowForData(){
		$selectedData = DB::table('master_lookup')->where('mas_cat_id', '=', 56)
		              ->get();
		return $selectedData;
	}
   
    public function getApprovalStatusDropdown($prnttypeid, $statusID=""){
        $approvalStatus = DB::table("master_lookup")
                    ->where("value", "=", $prnttypeid)
                    ->first();

        $statusID = explode(",", $statusID);
        $approvalStatus = DB::table("master_lookup")
                    ->where("parent_lookup_id", "=", $approvalStatus->master_lookup_id)
                    ->whereIn("mas_cat_id", $statusID)
                    ->get();
        return $approvalStatus;
    }
  	public function getApprovalRole($legal_entity_id){
    	$approvalRole = DB::table("roles")
                ->select('role_id','name')
                ->where("legal_entity_id","=",$legal_entity_id)
                ->get();
    	return $approvalRole;
  	}

  	public function getMatchingRecords($inputData,$legal_entity_id){
    	$checkDetails = DB::table('appr_workflow_status_new')
                  ->where('appr_workflow_status_new.awf_for_id' , '=', $inputData['appr_status_for'])
                  ->where('appr_workflow_status_new.legal_entity_id' , '=',$legal_entity_id)
                  ->count();
    	return $checkDetails;
  	}

  	public function saveApprovalStatusData($inputData){

        // write a data into Mongo for log
        $approvalActivityData = array(
            'NEWVALUES'         =>  $inputData
        );

	    $this->awf_name = $inputData['appr_status_name'];          
	    $this->awf_for_id = $inputData['appr_status_for'];
        $this->redirect_url = $inputData['redirect_url'];
        $this->awf_email = isset($inputData['awf_email']) ? 1 : 0;
        $this->awf_notification = isset($inputData['awf_notification']) ? 1 : 0;
	    $this->legal_entity_id = Session::get('legal_entity_id');
	    $this->created_by = Session::get('userId');

	    if ($this->save()) {
            // User Activivity Log Added
            UserActivity::userActivityLog('ApprovalworkFlow', $approvalActivityData, 'Approval Added by the User');
            return $this->awf_id;
	    }else{
            return false;
	    }
	}

    // Update data into Main table
    public function updateMaintableData($inputData){

        $approvalUpdateData = DB::table("appr_workflow_status_new")
                    ->where('awf_id', '=', $inputData['awf_id'])
                    ->first();

        $approvalUpdateData = array(
                    'OLDVALUES'         =>  json_decode(json_encode($approvalUpdateData)),
                    'NEWVALUES'         =>  $inputData
        );

        $updateData = approvalCartModel::find($inputData['awf_id']);
        $updateData->awf_email = isset($inputData['awf_email']) ? 1 : 0;
        $updateData->awf_notification = isset($inputData['awf_notification']) ? 1 : 0;
        $updateData->awf_name= $inputData['appr_flow_name'];
        $updateData->redirect_url = $inputData['redirect_url'];
        $updateData->save();

        //userActivity log
        UserActivity::userActivityLog('ApprovalworkFlow', $approvalUpdateData, 'ApprovalData Updated by the User');

        return true;

    }

    public function getAllApprovalData($mainTableId,$legal_entity_id){
        $allApprovalData = DB::table("appr_workflow_status_new AS aws")
                    ->join("appr_workflow_status_details AS aws_det", "aws.awf_id", "=", "aws_det.awf_id")
                    ->join("master_lookup AS ml", "ml.value", "=", "aws.awf_for_id")
                    ->where('aws.legal_entity_id' , '=',$legal_entity_id) 
                    ->where('aws.awf_id','=', $mainTableId)
                    ->get();
                    
        return $allApprovalData;
    }

    public function deleteApprovalData($awf_id){

        $deleteapproval = DB::table("appr_workflow_status_new")
                ->where('awf_id', '=', $awf_id)
                ->first();

        $deleteapproval = array(
                'OLDVALUES'         =>  json_decode(json_encode($deleteapproval)),
                'NEWVALUES'         => 'Deleted Approval data',
            );

        $deleteDataObj = approvalCartModel::find($awf_id);

        if( $deleteDataObj->delete() ){
            //userActivity log
            UserActivity::userActivityLog('ApprovalworkFlow', $deleteapproval, 'Approval Deleted by the User');
        	return true;

        }else{
        	return false;
        }
    }
}