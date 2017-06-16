<?php
/*
FileName : approvalTicketModel.php
Author   : eButor
Description : Function needed for Index page.
CreatedDate : 28/Jul/2016
*/

//defining namespace
namespace App\Modules\ApprovalEngine\Models;
use DB;
use Session;

class approvalTicketModel{


	public function viewAprovalTicketdata($makeFinalSql, $orderBy, $page, $pageSize, $countBy='All'){

		if($orderBy!=''){

			$tmpOrderBy = explode(" ", $orderBy);

			if($tmpOrderBy[0]=='TicketDetails'){
				$orderBy = isset($tmpOrderBy[1]) ? $tmpOrderBy[1] : 'ASC';
				$orderBy = ' ORDER BY awf_for_type ' . $orderBy;
			}else{
				$orderBy = ' ORDER BY ' . $orderBy;
			}

		}else{
			$orderBy = ' ORDER BY created_at desc';
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
		
		$sqlWhrCls = str_replace("TicketDetails", "CONVERT(TicketDetails USING utf8)", $sqlWhrCls);
		
		// get User Roles from Session
		$role = Session::get('roles');
		$userID = Session::get('userId');

		// get Immidiate Role ID
		$getImmidiateRoleId = DB::table('roles')
							->where('name', '=','ImmediateReporter')
							->first();
		$ImmidiateRptID = 0;
		if($getImmidiateRoleId){
			$ImmidiateRptID = $getImmidiateRoleId->role_id;
		}


		// get Initator Role ID
		$getInitiatorRoleId = DB::table('roles')
							->where('name', '=','Initiator')
							->first();
		$InitatorRptID = 0;
		if($getInitiatorRoleId){
			$InitatorRptID = $getInitiatorRoleId->role_id;
		}

		// get Count Flag
		$statusFilter="";
		$CustomActionQuery = "CONCAT('<center>
					    <code>
					    <a href=\"',if(awh.redirect_url is null, '', REPLACE(awh.`redirect_url`,'##',hist.`awf_for_id`)),'\" target=\"_blank\">
					    <i class=\"fa fa-link\"></i>
					    </a>

					    </code>
					    </center>')";
		$ticketDetailsQueryOpen = "CONCAT('<span style=\"color:#3598dc; font-weight: bold;\">Approval For - <a style=\"color:red;\" href=\"',IF(redirect_url IS NULL, '', REPLACE(`redirect_url`,'##',`awf_for_id`)),'\" target=\"_blank\">', awf_for_type, '(', awf_for_id, ')</a></span> <br> <span style=\"color:#3598dc; font-style: italic;\">Previous ', getMastLookupValue(condition_id), ' By ' , UserName )";
		$ticketDetailsQueryClose = "CONCAT('<span style=\"color:#3598dc; font-weight: bold;\">Approval For - ', awf_for_type, '(', awf_for_id, ')</span> <br> <span style=\"color:#3598dc; font-style: italic;\">Previous ', getMastLookupValue(condition_id), ' By ' , UserName )";

		if ($countBy=='Open') {
			$statusFilter = "AND hist.`is_final` = 0";
		}elseif ($countBy=='Close') {
			$statusFilter = "AND hist.`is_final` = 1";
		}

		// arrange the query part
		$sqlCountPart = "select count(*) as cnt from (";
		$sqlGeneralPart = "select * from (";
	    $sqlQuery ="select *,
	    			case is_final
	    				when 0 then ".$ticketDetailsQueryOpen."
	    				when 1 then ".$ticketDetailsQueryClose."
	    				else ".$ticketDetailsQueryClose."
	    			end AS 'TicketDetails',
					CONCAT('TKT-', awf_for_id) AS 'TicketNumber' 
					FROM
					(
						SELECT 
						  case hist.is_final
							when 0 then ".$CustomActionQuery."
							when 1 then ''
							else ''
						  end as 'CustomAction',
						  hist.`awf_history_id`,
						  hist.`awf_for_id`,
						  hist.`awf_for_type`,
						  hist.`status_from_id`,
						  getMastLookupValue(hist.`status_from_id`) AS 'PreviousStatus',
						  hist.`status_to_id`,
						  getMastLookupValue(hist.`status_to_id`) AS 'CurrentStatus',
						  hist.condition_id,
						  det.applied_role_id,
						  hist.user_id,
						  hist.awf_comment,
						  awh.redirect_url,
						  hist.is_final,
						  case hist.is_final
							when 0 then '<span style=\"color:#f00; font-weight: bold;\">Open</span>'
							when 1 then '<span style=\"color:#3598dc; font-weight: bold;\">Closed</span>'
							else 'No Status'
						  end as 'TicketStatus',
						  (SELECT 
						      CONCAT(usr.firstname, ' ', usr.lastname) 
						    FROM
						      users AS usr 
						    WHERE usr.user_id = hist.user_id) AS 'UserName',
						  date_format(hist.created_at, '%d-%m-%Y') as 'AssignDate',
						  hist.created_at,
						  (SELECT 
						    detinner.applied_role_id 
						  FROM
						    appr_workflow_status_details AS detinner 
						  WHERE detinner.awf_status_id = hist.`status_to_id` 
						    AND detinner.awf_id = awh.awf_id 
						  LIMIT 1) AS 'NextLblRole'
						FROM
						  appr_workflow_history AS hist 
						  INNER JOIN appr_workflow_status_new AS awh 
						    ON awh.awf_for_id = hist.`awf_for_type_id` 
						  INNER JOIN appr_workflow_status_details AS det 
						    ON awh.awf_id = det.awf_id 
						WHERE hist.`status_from_id` = det.awf_status_id 
						  AND hist.`status_to_id` = det.awf_status_to_go_id 
						  AND hist.`awf_for_type_id` = awh.awf_for_id 
						  AND ( hist.`next_lbl_role` IN (".$role.") OR (hist.`created_by_manager`='".$userID."' and hist.`next_lbl_role`='".$ImmidiateRptID."') OR (hist.`ticket_created_by`='".$userID."' and hist.`next_lbl_role`='".$InitatorRptID."'))
						  ".$statusFilter." GROUP BY hist.awf_for_id, hist.awf_for_type, hist.is_final) as innertbl) as innertbl1 " . $sqlWhrCls . $orderBy;

		// Run the query part for TotalCount
		$allData = DB::select(DB::raw( $sqlCountPart . $sqlQuery ));
		$TotalRecordsCount = $allData[0]->cnt;

		// Arrange the Data Limit Part
		$pageLimit = '';
		if($page!='' && $pageSize!=''){
			$pageLimit = " LIMIT " . (int)($page*$pageSize) . ", " . $pageSize;
		}

		$allTicketData = DB::select(DB::raw( $sqlGeneralPart . $sqlQuery . $pageLimit ));

		return json_encode(array('results'=>$allTicketData, 'TotalRecordsCount'=>(int)($TotalRecordsCount))); 
	}

	public function getTicketCount($countBy='Open'){

		$isFinalQuery="";

		if ($countBy=='Open') {
			$isFinalQuery = "AND hist.`is_final` = 0";
		}elseif ($countBy=='Close') {
			$isFinalQuery = "AND hist.`is_final` = 1";
		}
		
		// get User Roles from Session
		$role = Session::get('roles');
		$userID = Session::get('userId');

		// get Immidiate Role ID
		$getImmidiateRoleId = DB::table('roles')
							->where('name', '=','ImmediateReporter')
							->first();
		$ImmidiateRptID = 0;
		if($getImmidiateRoleId){
			$ImmidiateRptID = $getImmidiateRoleId->role_id;
		}


		// get Initator Role ID
		$getInitiatorRoleId = DB::table('roles')
							->where('name', '=','Initiator')
							->first();
		$InitatorRptID = 0;
		if($getInitiatorRoleId){
			$InitatorRptID = $getInitiatorRoleId->role_id;
		}
		// get User Roles from Session
	    $sqlQuery ="select count(*) as 'TotalCount' from
					(
					select hist.* 
					FROM
					  appr_workflow_history AS hist 
					  INNER JOIN appr_workflow_status_new AS awh 
					    ON awh.awf_for_id = hist.`awf_for_type_id` 
					  INNER JOIN appr_workflow_status_details AS det 
					    ON awh.awf_id = det.awf_id 
					WHERE hist.`status_from_id` = det.awf_status_id 
					  AND hist.`status_to_id` = det.awf_status_to_go_id 
					  AND hist.`awf_for_type_id` = awh.awf_for_id 
					  AND (hist.`next_lbl_role` IN (".$role.")  OR (hist.`created_by_manager`='".$userID."' and hist.`next_lbl_role`='".$ImmidiateRptID."') OR (hist.`ticket_created_by`='".$userID."' and hist.`next_lbl_role`='".$InitatorRptID."')) 
					  ".$isFinalQuery." GROUP BY hist.awf_for_id, hist.awf_for_type, hist.is_final) as innertbl";

					// keeping this part if needed
					/* ."
					  AND hist.`awf_history_id` IN 
					  (SELECT 
					    MAX(hist1.`awf_history_id`) 
					  FROM
					    appr_workflow_history AS hist1 
					  WHERE hist1.`awf_for_id` != 0 
					  GROUP BY hist1.`awf_for_id`)" */

		$allTicketData = DB::select(DB::raw($sqlQuery));

		$totalTicket = 0;
		if( isset($allTicketData[0]) ){
			$totalTicket = $allTicketData[0]->TotalCount;
		}
		
		return $totalTicket;
	}
}