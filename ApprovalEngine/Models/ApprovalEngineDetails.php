<?php
/*
FileName :ApprovalEngineDetails.php
Author   :eButor
Description : Save role details.
CreatedDate :28/jul/2016
*/

//defining namespace
namespace App\Modules\ApprovalEngine\Models;
use Illuminate\Database\Eloquent\Model;
use DB;

class ApprovalEngineDetails extends model
{  
	protected $table = 'appr_workflow_status_details';
  	protected $primaryKey = 'awf_det_id';

	public function saveApprovalDetailsData($workflowData){

		if ($this->insert($workflowData)) {
			return true;
		}else{
			return false;
		}
	}

	// Delete Data from Detals Table
	public function deleteDetailsData($flowID){

		$approvalDetail = DB::table('appr_workflow_status_details')
						->where('awf_id', '=', $flowID)
                  		->delete();	
	    if( $approvalDetail ){
	    	return true;
		}else{
			return false;
		}

	}
}