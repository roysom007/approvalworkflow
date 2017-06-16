<?php
//defining namespace
namespace App\Modules\ApprovalEngineApi\Controllers;

//loading namespaces
use App\Http\Controllers\BaseController;
use App\Central\Repositories\RoleRepo;
use App\Modules\ApprovalEngine\Models\CommonApprovalFlowFunctionModel;
use App\Modules\ApprovalEngineApi\Models\approvalEngineApiModel;
use Illuminate\Http\Request;
use Input;
use Redirect;
use Session;
use Notifications;
use Log;

class approvalEngineApiController extends BaseController{

	public function __construct() {
        $this->objAPIModelForApproval = new CommonApprovalFlowFunctionModel();
        $this->objAPIModelApi = new approvalEngineApiModel();
    }

    public function checkAuthentication($auth_token){
        
        if( $auth_token=='E446F5E53AD8835EAA4FA63511E22' ){
            return true;
        }else{
            return false;
        }
    }

    public function getApprovalDataApi(Request $request){

        $hostIP = $request->ip();

        $paramData = $request->input();
        // check for the header authentication
        $auth_token = $request->header('auth');

        // if authentication does not match then send a return
        if( !$this->checkAuthentication($auth_token) ){
            $finalResponse = array(
                'message'   => 'Invalid authentication! Call aborted',
                'status'    => 'failed',
                'code'      => '400'
            );
            // Save the information into the approval call details table
            $this->objAPIModelApi->getAPIcalldetailsForApi($request->input('FlowType'),"getAllApproval", $hostIP, $paramData, $finalResponse,$request->input('UserID'));
            return $finalResponse;
        }

        // Getting the input
        $flowType           = trim($request->input('FlowType'));
        $currentStatusID    = trim($request->input('CurrentStatus'));
        $userID             = trim($request->input('UserID'));

        if( $flowType=='' || $currentStatusID=='' || $userID=='' ){
            $finalResponse = array(
                'message'   => 'Invalid input send, Mandatory  fields are not matched!',
                'status'    => 'failed',
                'code'      => '400'
            );
            // Save the information into the approval call details table
            $this->objAPIModelApi->getAPIcalldetailsForApi($flowType, "getAllApproval", $hostIP, $paramData, $finalResponse, $userID);
            return $finalResponse;
        }

        // Get Approval Information
        $finalResponse = $this->objAPIModelForApproval->getApprovalFlowDetails($flowType, $currentStatusID, $userID);

        // Save the information into the approval call details table
        $this->objAPIModelApi->getAPIcalldetailsForApi($flowType, "getAllApproval", $hostIP, $paramData, $finalResponse, $userID);
        return $finalResponse;
    }

    // store Approval data into the Table
    public function saveApprovalWorkflowForApi(Request $request){

        $hostIP = $request->ip();
        $paramData = $request->input();

        // check for the header authentication
        $auth_token = $request->header('auth');
        // if authentication does not match then send a return
        if( !$this->checkAuthentication($auth_token) ){
            $finalResponse = array(
                'message'   => 'Invalid authentication! Call aborted',
                'status'    => 'failed',
                'code'      => '400'
            );
             // Save the information into the approval call details table
             $this->objAPIModelApi->getAPIcalldetailsForApi($request->input('FlowType'),"saveApprovalWorkflowForApi", $hostIP, $paramData, $finalResponse,$request->input('UserID'));
            return $finalResponse;
        }    

        // Getting the input
        $flowType           = trim($request->input('FlowType'));
        $flowTypeForID     = trim($request->input('YourTableID'));
        $currentStatusID    = trim($request->input('CurrentStatusID'));
        $nextStatusId       = trim($request->input('NextStatusID'));
        $Comment            = trim($request->input('Comment'));
        $userID             = trim($request->input('UserID'));

        if( $flowType=='' || $currentStatusID=='' || $userID==''){
            $finalResponse = array(
                'message'   => 'Invalid input send, Mandatory  fields are not matched! parameter missing',
                'status'    => 'failed',
                'code'      => '400'
            );
            //save data into approval call details
            $this->objAPIModelApi->getAPIcalldetailsForApi($flowType, "saveApprovalWorkflowForApi", $hostIP, $paramData, $finalResponse, $userID);
            return $finalResponse;

        }
        // Return and call the Approval History Data
        $approvalDataResp = $this->objAPIModelForApproval->storeWorkFlowHistory($flowType, $flowTypeForID, $currentStatusID, $nextStatusId, $Comment, $userID);

        if($approvalDataResp==1){

               $finalResponse = array(
                'message' => 'Call Successful',
                'status' => 'success',
                'code'  => '200'
               );
              }else{
               $finalResponse = array(
                'message' => 'Something went wrong, Please check!',
                'status' => 'failed',
                'code'  => '400'
               );
        }

        // Save the information into the approval call details table
        $this->objAPIModelApi->getAPIcalldetailsForApi($flowType, "saveApprovalWorkflowForApi", $hostIP, $paramData, $finalResponse, $userID);
        return $finalResponse;
    }


    public function getApprovalHistoryByID(Request $request){

        $hostIP = $request->ip();
        $paramData = $request->input();

        // check for the header authentication
        $auth_token = $request->header('auth');

        // if authentication does not match then send a return
        if( !$this->checkAuthentication($auth_token) ){
            $finalResponse = array(
                'message'   => 'Invalid authentication! Call aborted',
                'status'    => 'failed',
                'code'      => '400'
            );
             // Save the information into the approval call details table
            $this->objAPIModelApi->getAPIcalldetailsForApi("","getApprovalHistoryByID", $hostIP, $paramData, $finalResponse,"");
            return $finalResponse;
        }

        // Take the Inputs
        $approvalID         = trim($request->input('approvalid'));
        $approvalType       = trim($request->input('approvaltype'));
        if( $approvalID=='' || $approvalType==''){
            $finalResponse = array(
                'message'   => 'Invalid input send, Mandatory  fields are not matched!',
                'status'    => 'failed',
                'code'      => '400'
            );
            // Save the information into the approval call details table
            $this->objAPIModelApi->getAPIcalldetailsForApi($approvalType,"getApprovalHistoryByID", $hostIP, $paramData, $finalResponse,"");
            return $finalResponse;

        }

        $approvalData = $this->objAPIModelApi->getApprovalHistoryByIDApi($approvalID, $approvalType);

        $finalResponse = array(
                'message'   => 'Call Successful',
                'status'    => 'success',
                'code'      => '200',
                'data'      => json_decode(json_encode($approvalData), true)
            );

            // Save the information into the approval call details table
            $this->objAPIModelApi->getAPIcalldetailsForApi($approvalType,"getApprovalHistoryByID", $hostIP, $paramData, $finalResponse,"");
        return $finalResponse;
    }

    public function notifyUserForFirstApproval(Request $request){

        // Getting the input
        $flowType               = trim($request->input('FlowName'));
        $flowForID              = trim($request->input('YourTableID'));
        $userID                 = trim($request->input('UserID'));

        return $this->objAPIModelForApproval->notifyUserForFirstApproval($flowType, $flowForID, $userID);


    }

}

?>