<?php
Route::group(['middleware' => ['mobile']], function () {
	Route::group(['prefix' => 'approvalworkflowapi', 'namespace' => 'App\Modules\ApprovalEngineApi\Controllers'], function () {
		
		Route::post('/saveapprovalworkflowforapi', 'ApprovalEngineApiController@saveApprovalWorkflowForApi');

		Route::get('/getapprovaldataforapi', 'ApprovalEngineApiController@getApprovalDataApi');

		Route::get('/getapprovalhistorybyid', 'ApprovalEngineApiController@getApprovalHistoryByID');

		Route::get('/notifyuserforfirstapproval', 'ApprovalEngineApiController@notifyUserForFirstApproval');

	});
}); 