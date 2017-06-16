<?php
//defining namespace
namespace App\Modules\ApprovalEngineApi\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;
use UserActivity;

class approvalEngineApiModel extends model{

	//protected $table = 'appr_workflow_status';
	protected $table = 'appr_workflow_status_new';
	protected $primaryKey = 'awf_id';


	public function getApprovalHistoryByIDApi($approvalID, $approvalType){
        	$history=DB::table('appr_workflow_history as hs')
                        ->join('users as us','us.user_id','=','hs.user_id')
                        ->join('user_roles as ur','ur.user_id','=','hs.user_id')
                        ->join('roles as rl','rl.role_id','=','ur.role_id')
                        ->join('master_lookup as ml','ml.value','=','hs.status_to_id')
                        ->select('hs.awf_history_id', 'us.profile_picture','us.firstname','us.lastname',DB::raw('group_concat(rl.name) as name'),'hs.created_at','hs.status_to_id','hs.status_from_id','hs.awf_comment','ml.master_lookup_name')
                        ->where('hs.awf_for_id',$approvalID)
                        ->where('hs.awf_for_type',$approvalType)
                        ->groupBy('hs.created_at')
                        ->orderBy('hs.awf_history_id')
                        ->get();

                return $history;
	}

        public function getAPIcalldetailsForApi($flowtype,$callName, $hostIP, $paramData, $finalResponse,$userid){

                $finalData = array(
                        "appr_call_for"                 =>  $flowtype,
                        "appr_name"                     =>  $callName,
                        "appr_call_from"                =>  $hostIP,
                        "appr_call_input"               =>  json_encode($paramData),
                        "appr_call_response"            =>  json_encode($finalResponse),
                        "appr_call_made_at"             =>  date("Y-m-d H:i:s"),
                        "appr_call_user_id"             =>  $userid
                );

        $save = DB::table("appr_workflow_call_details") 
            ->insert($finalData);

        return 1; 

        }

	
}