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
use App\Modules\ApprovalEngine\Controllers\commonIgridController;
use App\Modules\ApprovalEngine\Models\approvalTicketModel;
use Illuminate\Http\Request;
use Input;
use Redirect;
use Session;
use Notifications;
use Log;

class approvalTicketController extends BaseController{

    private $objCommonGrid = '';


     public function __construct() {
        if (!Session::has('userId')) {
            Redirect::to('/login')->send();
        }
        // get common controller reff
        $this->objCommonGrid = new commonIgridController();
        $this->objTicketModel = new approvalTicketModel();
    }

    public function approvalTicketIndex(){

        try{
            $breadCrumbs = array('Home' => url('/'),'Approval Ticket' => '#');
            parent::Breadcrumbs($breadCrumbs);

            // Get ticket Count
            $allCount=$this->objTicketModel->getTicketCount('All');
            $openCount=$this->objTicketModel->getTicketCount('Open');
            $closeCount=$this->objTicketModel->getTicketCount('Close');
            return view('ApprovalEngine::approvalTicketIndex', ['allCount'=>$allCount, 'openCount'=>$openCount, 'closeCount'=>$closeCount]);
        }
        catch (\ErrorException $ex) {
            Log::error($ex->getMessage());
            Log::error($ex->getTraceAsString());
          }
    }  

    public function approvalTicketData(Request $request){
        // Arrange data for pagination
        $makeFinalSql = array(); 

        $filter = $request->input('%24filter');
        if( $filter=='' ){
            $filter = $request->input('$filter');
        }


        // make sql for Assigned On
        $fieldQuery = $this->objCommonGrid->makeIGridToSQL("created_at", $filter, true);
        if($fieldQuery!=''){
            $makeFinalSql[] = $fieldQuery;
        }

        // make sql for version name
        $fieldQuery = $this->objCommonGrid->makeIGridToSQL("TicketDetails", $filter);
        if($fieldQuery!=''){
            $makeFinalSql[] = $fieldQuery;
        }

        // make sql for version name
        $fieldQuery = $this->objCommonGrid->makeIGridToSQL("TicketNumber", $filter);
        if($fieldQuery!=''){
            $makeFinalSql[] = $fieldQuery;
        }

        // arrange Order By
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

        // Process data for Status Filter
        $countBy = '';
        if($request->input('filterStatusType')!='All'){
            $countBy = $request->input('filterStatusType')!='' ? $request->input('filterStatusType') : '';
        }
        
        return $this->objTicketModel->viewAprovalTicketdata($makeFinalSql, $orderBy, $page, $pageSize,$countBy);
    }

    // This function will return the number of open ticket for the current user
    public function getUserTicketCount(){
        $role = Session::get('roles');
        $ticketNumber = 0;
        if($role!=""){
            $ticketNumber = $this->objTicketModel->getTicketCount();
        }
        return $ticketNumber;
    }
}

?>