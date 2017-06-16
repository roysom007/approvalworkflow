<?php
/*
FileName : approvalEngineIndexModel.php
Author   : eButor
Description : Function needed for Index page.
CreatedDate : 28/Jul/2016
*/

//defining namespace
namespace App\Modules\ApprovalEngine\Models;
use DB;
use Session;

class approvalIndexModel{

	public function viewAprovalDetailsdata($makeFinalSql, $orderBy, $page, $pageSize, $viewApprovalData, $editApprovalData, $deleteApprovalData){

		if($orderBy!=''){
			$orderBy = ' ORDER BY ' . $orderBy;
		}

		$sqlWhrCls = '';
		$countLoop = 0;
		
		foreach ($makeFinalSql as $value) {
			if( $countLoop==0 ){
				$sqlWhrCls .= ' WHERE ' . $value;
			}elseif( count($makeFinalSql)==$countLoop ){
				$sqlWhrCls .= $value;
			}else{
				$sqlWhrCls .= ' AND ' .$value;
			}
			$countLoop++;
		}

		$concatQueryEdit ="";
		$concatQueryDelete = "";
		$concatQuery="CONCAT('<center><a href=\"/approvalworkflow/viewapprovalpage/',innertbl.awf_id,'\" data-toggle=\"tooltip\" title=\"View Approval Flow\">
			<i class=\"fa fa-eye\"></i>
			</a>
			&nbsp;&nbsp;";

		if ($editApprovalData== 1) {
			$concatQueryEdit = "<a href=\"/approvalworkflow/updateapprovalpage/',innertbl.awf_id,'\" data-toggle=\"tooltip\" title=\"Update Approval Flow\">
			<i class=\"fa fa-pencil\"></i>
			</a>";
		}
		if($deleteApprovalData== 1) {
			$concatQueryDelete = "<a href=\"javascript:void(0)\" onclick=\"deleteApprovalId(',innertbl.awf_id,')\" data-toggle=\"tooltip\" title=\"Delete Approval Flow\">
			<i class=\"fa fa-trash-o\"></i>
			</a>";
		}

		$concatQuery = $concatQuery . '&nbsp;&nbsp;' . $concatQueryEdit . '&nbsp;&nbsp;' . $concatQueryDelete . "</center>') 
			AS 'CustomAction', ";
			
        $legaEntityQuery = "";

		if(Session::get('legal_entity_id')!=0){
			$legaEntityQuery=" WHERE aws.legal_entity_id='". Session::get('legal_entity_id') ."'";
		}
		
	    $sqlQuery ="select *, ".$concatQuery."
			@rowcnt:=@rowcnt+1 AS 'slno'
			FROM
			(
			SELECT aws.awf_id, aws.awf_name, aws.awf_for_id, ml.master_lookup_name, ml.`value`, 
			CONCAT(usr.firstname, ' ', usr.lastname) AS 'CreatedBy'
			FROM appr_workflow_status_new AS aws
			INNER JOIN master_lookup AS ml ON ml.`value`=aws.awf_for_id
			INNER JOIN users AS usr ON usr.user_id=aws.created_by ".$legaEntityQuery."
			)
	        AS innertbl, (SELECT @rowcnt:= 0) AS rowcnt " . $sqlWhrCls . $orderBy;

		$allRecallData = DB::select(DB::raw($sqlQuery));
		$TotalRecordsCount = count($allRecallData);

		// prepare for limit
		if($page!='' && $pageSize!=''){
			$page = $page=='0' ? 0 : (int)$page * (int)$pageSize;
			$allRecallData = array_slice($allRecallData, $page, $pageSize);
		}
		return json_encode(array('results'=>$allRecallData, 'TotalRecordsCount'=>(int)($TotalRecordsCount))); 
	}

	public function generateFlowDataForDiagram($awf_main_id,$legal_entity_id){
	    $sqlQuery = "select *,
		(SELECT rls.name FROM roles AS rls WHERE rls.role_id=awf.applied_role_id) AS 'RoleName'
		FROM appr_workflow_status_details AS awf 
		INNER JOIN appr_workflow_status_new AS awfn
		WHERE awf.awf_id='".$awf_main_id."' AND awfn.legal_entity_id='".$legal_entity_id."' GROUP BY awf_status_id ORDER BY awf.awf_det_id";
	    $flowData = DB::select(DB::raw($sqlQuery));
	    return $flowData;
		}

  	public function getSingleFlowByID($for_id, $status_from_id){
    	$sqlQuery = "select awf.awf_id, awf.awf_name, awf.awf_for_id,
			(SELECT ml.master_lookup_name FROM master_lookup AS ml WHERE ml.value=awf.awf_for_id) AS 'ForName',
			det.awf_status_id, (SELECT ml.master_lookup_name FROM master_lookup AS ml WHERE ml.value=det.awf_status_id) AS 'StatusName',
			det.awf_condition_id, (SELECT ml.master_lookup_name FROM master_lookup AS ml WHERE ml.value=det.awf_condition_id) AS 'ConditionName',
			det.awf_status_to_go_id, (SELECT ml.master_lookup_name FROM master_lookup AS ml WHERE ml.value=det.awf_status_to_go_id) AS 'NextStatus'
			FROM appr_workflow_status_new AS awf
			INNER JOIN appr_workflow_status_details AS det
    		WHERE awf.awf_id='" . $for_id . "' AND det.awf_status_id='". $status_from_id ."' ORDER BY awf.awf_id";
    		$flowData = DB::select(DB::raw($sqlQuery));
    	return $flowData;
  	}

  	public function getFlowStatusForView($awf_main_id){

		$sqlQuery = "select awf.awf_id, awf.awf_name, awf.awf_for_id,
		(SELECT ml.master_lookup_name FROM master_lookup AS ml WHERE ml.value=awf.awf_for_id) AS 'ForName',
		det.awf_status_id, (SELECT ml.master_lookup_name FROM master_lookup AS ml WHERE ml.value=det.awf_status_id) AS 'StatusName',
		det.awf_condition_id, (SELECT ml.master_lookup_name FROM master_lookup AS ml WHERE ml.value=det.awf_condition_id) AS 'ConditionName',
		det.awf_status_to_go_id, (SELECT ml.master_lookup_name FROM master_lookup AS ml WHERE ml.value=det.awf_status_to_go_id) AS 'NextStatus', rls.name, det.is_final
		FROM appr_workflow_status_new AS awf
		INNER JOIN appr_workflow_status_details AS det ON det.`awf_id`=awf.`awf_id`
		INNER JOIN roles AS rls ON rls.role_id=det.applied_role_id WHERE awf.`awf_id`='".$awf_main_id."' ORDER BY awf.awf_id";

		$flowData = DB::select(DB::raw($sqlQuery));

		return $flowData;
  	}
}