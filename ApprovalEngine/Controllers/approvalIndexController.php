<?php
/*
FileName :approvalIndexController
Author   :eButor
Description :
CreatedDate :15/jul/2016
*/
//defining namespace
namespace App\Modules\ApprovalEngine\Controllers;

//loading namespaces
use App\Http\Controllers\BaseController;
use App\Modules\ApprovalEngine\Controllers\commonIgridController;
use App\Central\Repositories\RoleRepo;
use App\Modules\ApprovalEngine\Models\approvalIndexModel;
use Illuminate\Http\Request;
use Input;
use Session;
use Redirect;
use Log;

class approvalIndexController extends BaseController{

    private $_approval_request = '';
    private $objCommonGrid = '';
    private $functionForIndex = '';

    public function __construct() {
        // get common controller reff
        $this->_roleRepo = new RoleRepo();
        $this->objCommonGrid = new commonIgridController();

        // get Model reff
        $this->functionForIndex = new approvalIndexModel();

        try {
            if (!Session::has('userId')) {
                Redirect::to('/login')->send();
            }
            parent::Title('ApprovalEngine');
            if(Session::get('legal_entity_id')!=0){
                $access = $this->_roleRepo->checkPermissionByFeatureCode('AW001');
            }
            if (!$access) {
                Redirect::to('/')->send();
                die();
            }
        } catch (\ErrorException $ex) {
            Log::error($ex->getMessage());
            Log::error($ex->getTraceAsString());
        }
    }

    public function ApprovalIndex(){
        try{
            $breadCrumbs = array('Home' => url('/'),'Administration' =>'#','Approval Flow List' =>'#');
            parent::Breadcrumbs($breadCrumbs);
            
            //check all access here
            $addApprovalAccess=1;
            if(Session::get('legal_entity_id')!=0){
                $addApprovalAccess = $this->_roleRepo->checkPermissionByFeatureCode('APPR01');
            }

            return view('ApprovalEngine::index')->with(['addApprovalAccess'=>$addApprovalAccess]);
        }
        catch (\ErrorException $ex) {
            Log::error($ex->getMessage());
            Log::error($ex->getTraceAsString());
        }
    }

    public function approvalList(Request $request){

        $makeFinalSql = array();
        $filter = $request->input('%24filter');
        if( $filter=='' ){
            $filter = $request->input('$filter');
        }

        // make sql for version name
        $fieldQuery = $this->objCommonGrid->makeIGridToSQL("awf_name", $filter);
        if($fieldQuery!=''){
            $makeFinalSql[] = $fieldQuery;
        }

        // make sql for outbound_order_id
        $fieldQuery = $this->objCommonGrid->makeIGridToSQL("master_lookup_name", $filter);
        if($fieldQuery!=''){
            $makeFinalSql[] = $fieldQuery;
        }

        // make sql for outbound_order_id
        $fieldQuery = $this->objCommonGrid->makeIGridToSQL("CreatedBy", $filter);
        if($fieldQuery!=''){
            $makeFinalSql[] = $fieldQuery;
        }

        $orderBy = "";
        $orderBy = $request->input('%24orderby');
        if($orderBy==''){
            $orderBy = $request->input('$orderby');
        }

        // Arrange data for pagination
        $page="";
        $pageSize="";
        if( ($request->input('page') || $request->input('page')==0)  && $request->input('pageSize') ){
            $page = $request->input('page');
            $pageSize = $request->input('pageSize');
        }
         // Access for View,Edit and Delete Approval Data
        $deleteApprovalData=1;
        $editApprovalData=1;
        $viewApprovalData=$this->_roleRepo->checkPermissionByFeatureCode('APPR03');

        // check for the role access
        if(Session::get('legal_entity_id')!=0){
            $editApprovalData=$this->_roleRepo->checkPermissionByFeatureCode('APPR02');
            $deleteApprovalData=$this->_roleRepo->checkPermissionByFeatureCode('APPR04');
        }

        return $this->functionForIndex->viewAprovalDetailsdata($makeFinalSql, $orderBy, $page, $pageSize, $viewApprovalData, $editApprovalData,$deleteApprovalData);
    }

    // FUNCTION IS NEEDED FOR APPROVAL VIEW PAGE
    public function viewApprovalPage($flowid){

        $flowData = $this->functionForIndex->generateFlowDataForDiagram( $flowid ,Session::get('legal_entity_id'));
        
        // checking the flow is exist or it is with the LegalEntity or now
        if(count($flowData)==0){

            // this is not return view /// this should be redirect (change it)
           return Redirect::to('approvalworkflow/index');
        }
        else
        {
            $flowPattern = "";

            $flowPattern .= "st=>start\r\n";
            $flowPattern .= "e=>end\r\n";

            $loopCounter = 1;
            foreach ($flowData as $data) {
                $innerData = $this->functionForIndex->getSingleFlowByID( $data->awf_id, $data->awf_status_id );

                if(count($innerData)>1){
                    $flowPattern .= "op".$loopCounter."=>operation: " . $innerData[0]->StatusName . ' -> ' . $data->RoleName ."\r\n";
                    $flowPattern .= "sub".$loopCounter."=>subroutine: ".$innerData[1]->ConditionName."\r\n";
                    $flowPattern .= "cond".$loopCounter."=>condition: ".$innerData[0]->ConditionName." / ".$innerData[1]->ConditionName."?\r\n";
                    $loopCounter++;
                }
            }

            $flowCondition = "st->op1->cond1\r\n";


            $loopCounter = $loopCounter-1;
            for($i=1; $i<=$loopCounter; $i++){

                $nextlbl = $i+1;

                if($i==$loopCounter){
                    $flowCondition .= "cond" . $i . "(yes)->e\r\n";
                    $flowCondition .= "cond" . $i . "(no)->sub" . $i. "(right)->op1\r\n";
                }else{
                    $flowCondition .= "cond" . $i . "(yes)->op" . $nextlbl . "->cond". $nextlbl ."\r\n";
                    $flowCondition .= "cond" . $i . "(no)->sub" . $i. "(right)->op1\r\n";
                }
            }

            try{
                $breadCrumbs = array('Home' => url('/'),'Administration' =>'#','Approval Flow List' =>'approvalworkflow/index','View Approval Workflow' =>'#');
                parent::Breadcrumbs($breadCrumbs);

                $flowTextData = $this->functionForIndex->getFlowStatusForView( $flowid );
                return view('ApprovalEngine::viewApprovalData', ['flowdata' => $flowPattern . $flowCondition, "detailsData"=>$flowTextData]);
            }
            catch (\ErrorException $ex) {
                Log::error($ex->getMessage());
                Log::error($ex->getTraceAsString());
            }
        }
    }
}