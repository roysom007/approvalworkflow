<?php
Route::group(['middleware' => ['web']], function () {
	Route::group(['prefix' => 'approvalworkflow', 'namespace' => 'App\Modules\ApprovalEngine\Controllers'], function () {

	    Route::get('/index', 'approvalIndexController@ApprovalIndex');
	    Route::get('/', 'approvalIndexController@ApprovalIndex');
	    Route::get('/approvallist', 'approvalIndexController@approvalList');
	    Route::get('/viewapprovalpage/{flowid}', 'approvalIndexController@viewApprovalPage');

	    Route::get('/addapprovalstatus', 'approvalCartOperationController@addApprovalFlow');
	    Route::get('/approvalstatus/{prnttypeid}/{statusid}', 'approvalCartOperationController@getApprovalStatus');
	    Route::get('/approvalrole', 'approvalCartOperationController@getApprovalRole');
	    Route::post('/saveapprovalworkflow', 'approvalCartOperationController@saveApprovalWorkflow');

	    Route::get('/updateapprovalpage/{apprid}', 'approvalCartOperationController@updateApprovalPage');
		Route::post('/updateapprovalworkflow', 'approvalCartOperationController@updateApprovalWorkFlow');

		Route::get('/deleteapprovalstatusid/{myId}', 'approvalCartOperationController@deleteApprovalStatusId');

		/*--------------------Approval Ticket----------------------*/
		Route::get('/approvalticket', 'approvalTicketController@approvalTicketIndex');
		Route::get('/approvalticketgrid', 'approvalTicketController@approvalTicketData');


		/*--------------------Route for header notification----------------------*/
		Route::get('/getuserticketcount', 'approvalTicketController@getUserTicketCount');
		

	});
}); 