<?php
/*
FileName : approvalCartOperationController
Author   :eButor
Description :Approval workflow related functions are here
CreatedDate :28/jul/2016
*/
//defining namespace
namespace App\Modules\ApprovalEngine\Controllers;

//loading namespaces
use App\Http\Controllers\BaseController;
use App\Modules\ApprovalEngine\Models\approvalCartModel;
use App\Modules\ApprovalEngine\Models\ApprovalEngineDetails;
use App\Central\Repositories\RoleRepo;
use Illuminate\Http\Request;
use Input;
use Redirect;
use Session;
use Notifications;
use Log;

class approvalCartOperationController extends BaseController{

	public function __construct() {
        $this->_roleRepo = new RoleRepo();
        $this->_approval_request = new approvalCartModel();
        $this->_approval_request_details = new ApprovalEngineDetails();
    }

    public function addApprovalFlow(){
        try{
            //check all access here
            $addAccess = $this->_roleRepo->checkPermissionByFeatureCode('APPR01');
            if (!$addAccess) {
                Redirect::to('/approvalworkflow')->send();
                die();
            }else{
                $breadCrumbs = array('Home' => url('/'),'Administration' =>'#','Approval Flow List' =>'approvalworkflow/index','Add Approval Workflow' =>'#');
                parent::Breadcrumbs($breadCrumbs);

                $selecteddata = $this->_approval_request->approvalFlowForData();
                return view('ApprovalEngine::addApprovalEngine',['selecteddata' => $selecteddata]);
            }
            
        }   
        catch (\ErrorException $ex) {
            Log::error($ex->getMessage());
            Log::error($ex->getTraceAsString());
        }
    }

    public function getApprovalStatus($prnttypeid, $statusid){
        return $this->_approval_request->getApprovalStatusDropdown($prnttypeid, $statusid);
    }

    public function getApprovalRole(){
        return $this->_approval_request->getApprovalRole(Session::get('legal_entity_id'));
    }

    // to display update page
    public function updateApprovalPage($approvalID){
        try{
            $updateAccess = $this->_roleRepo->checkPermissionByFeatureCode('APPR02');
            if (!$updateAccess) {
                Redirect::to('/approvalworkflow')->send();
                die();
            }else{
                
                $breadCrumbs = array('Home' => url('/'),'Administration' =>'#','Approval WorkFlow List' =>url('/approvalworkflow/index'),'Update Approval Workflow' =>'#');
                parent::Breadcrumbs($breadCrumbs);
 
                // get Approval Data by ID and update the Approval page as per legal entity
                $getApprovalData = $this->_approval_request->getAllApprovalData($approvalID,Session::get('legal_entity_id'));

                // if data not found for the legalentiry then return to the index page (ex: has the access by added by defferent entity)
                if(count($getApprovalData)==0){
                    return Redirect::to('approvalworkflow/index');
                
                }else{
                    $prntStatusID = "0";
                    if( isset($getApprovalData[0]) ){
                        $prntStatusID = $getApprovalData[0]->value;
                    }
                    $getStatusData = $this->_approval_request->getApprovalStatusDropdown($prntStatusID,"57");
                    $getCondition = $this->_approval_request->getApprovalStatusDropdown($prntStatusID,"58");
                    $getRoleData = $this->_approval_request->getApprovalRole(Session::get('legal_entity_id'));

                    return view('ApprovalEngine::updateApprovalEngine', ['allApprovalData' => $getApprovalData, 'statusData' => $getStatusData, 'getCondition' => $getCondition, 'roleData'=> $getRoleData]);
                }
            }
        }catch (\ErrorException $ex) {
            Log::error($ex->getMessage());
            Log::error($ex->getTraceAsString());
        }
    }

    // store Approval data into the Table
    public function saveApprovalWorkflow(Request $request){

        $responseMSG="";
        $inputData = $request->Input();
        //get approval workflow name for add notification
        $flowName = $inputData['appr_status_name'];
        if( !isset($inputData['app_status']) ){
            $responseMSG = "Flow data is not available, add aborted!";
        }else{

            //$isFinalData = isset($inputData['final']) ? $inputData['final'] : 0;

            // checing if flow exist for the type
            $checkRecordExits = $this->_approval_request->getMatchingRecords($inputData, Session::get('legal_entity_id'));

            if($checkRecordExits==0){
                // Save the data into the mail table
                $workFlowID = $this->_approval_request->saveApprovalStatusData($inputData);
                $this->prepareDataForDetailsTable($workFlowID, $inputData);
                $responseMSG="Flow added succesfully.";
            }else{
                $responseMSG="Flow already exist.";
            }    
        }

        //Notification for add Approval workflow 
        Notifications::addNotification(['note_code' =>'APR001','note_params' => ['flowName' => $flowName]]);
        flash($responseMSG);
        $addAccess = $this->_roleRepo->checkPermissionByFeatureCode('APPR01');
        if (!$addAccess) {
                Redirect::to('/approvalworkflow')->send();
                die();
            }
        return Redirect::to('approvalworkflow/index');
    }

    // update Approval workflow in the DB
    public function updateApprovalWorkFlow(Request $request){

        $responseMSG ="";
        $inputData = $request->Input();
        //get approval workflow name for update notification
        $apprFlowName = $inputData['appr_flow_name'];
        if( !isset($inputData['app_status']) ){
            $responseMSG = "Flow data is not available, update aborted!";
        }else{
            // Update data into main table
            $workflowname = $this->_approval_request->updateMaintableData($inputData);

            //$isFinalData = isset($inputData['final']) ? $inputData['final'][0] : 0;

            // first Delete data from Details Table
            if( $this->_approval_request_details->deleteDetailsData($inputData['awf_id']) ){
                $this->prepareDataForDetailsTable($inputData['awf_id'], $inputData);
            }
            $responseMSG = "Flow updated succesfully";
        }

        //Notification for update workflow
        Notifications::addNotification(['note_code' =>'APR002','note_params' => ['flowName' => $apprFlowName]]);
        flash($responseMSG);
        return Redirect::to('approvalworkflow/index');
    } 

    // Save the data in detail Table
    public function prepareDataForDetailsTable($workFlowID, $inputData){

        $length = count($inputData['app_status']);
        //$inputDataLoop=0;

        for ($inputDataLoop = 0; $inputDataLoop < $length; $inputDataLoop++) {

            $first_last_flag = 0;
            if( $inputDataLoop==0){
                $first_last_flag = 1;
            }elseif ($inputDataLoop==$length-1) {
                $first_last_flag = 2;
            }

            $isFinalStep = 0;
            /*if($inputDataLoop==(int)$isFinalData-1){
                $isFinalStep=1;
            }*/

            if( isset($inputData['final']) ){
                if( in_array($inputDataLoop+1, $inputData['final']) ){
                    $isFinalStep = 1;
                }
            }

            $workflowData = array(
                'awf_id'                    =>  $workFlowID,
                'awf_status_id'             =>  $inputData['app_status'][$inputDataLoop],
                'awf_condition_id'          =>  $inputData['status_condition'][$inputDataLoop],
                'awf_status_to_go_id'       =>  $inputData['status_to'][$inputDataLoop],
                'applied_role_id'           =>  $inputData['role_ids'][$inputDataLoop],
                'awf_fast_last_flag'        =>  $first_last_flag,
                'is_final'                  =>  $isFinalStep
            );

            // call model here to save the data
            $this->_approval_request_details->saveApprovalDetailsData($workflowData);
        }
    }

    public function deleteApprovalStatusId($awf_id){

        $approvalData_byid = $this->_approval_request->deleteApprovalData($awf_id);

        if($approvalData_byid){
            $this->_approval_request_details->deleteDetailsData($awf_id);
        }
        return 'RecordDeleted';
    }
}

?>