@extends('layouts.default')
@extends('layouts.header')
@extends('layouts.sideview')
@section('content')

<?php View::share('title', 'View Approval Workflow'); ?>

<div class="row">
  <div class="col-md-12 col-sm-12">
    <div class="portlet light tasks-widget">
      <div class="portlet-title">
        <div class="caption"></div>
         <div class="tools"> <span class="badge bg-blue"><a class="fullscreen" data-toggle="tooltip" title="" style="color:#fff;" data-original-title="Hi, This is help Tooltip!"><i class="fa fa-question-circle-o"></i></a></span> </div>
          </div>
           <div class="portlet-body">
	         <div class="tabbable-line">
			     <ul class="nav nav-tabs ">
  				<li class="active"><a href="#tab_1" data-toggle="tab">View Approval Chart</a></li>
  				<li><a href="#tab_2" data-toggle="tab">View Approval Details</a></li>
		     </ul>
        <div class="tab-content headings">
       <div class = "tab-pane active" id = "tab_1">
      <div class="flowchart">
   {!! $flowdata !!}
   </div>
</div>       
<div class = "tab-pane" id = "tab_2">
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table table-striped table-hover table-advance" id="sample_3" name = "sample_3">
           <thead>
             <tr>
              <th>Status</th>
              <th>Condition</th>
              <th>Next</th>
              <th>Role</th>
              <th>Final Step</th>
             </tr>
           </thead>
          <tbody>
          @foreach($detailsData as $data)
          	<tr class="gradeX odd">
          		<td>{{ $data->StatusName }}</td>
          		<td>{{ $data->ConditionName }}</td>
          		<td>{{ $data->NextStatus }}</td>
          		<td>{{ $data->name }}</td>
              <td>@if($data->is_final=='1') {{'Yes'}} @endif</td>
          	</tr>
          @endforeach
          </tbody>
         </table>
         </div>
         </div>
			  </div>
       </div>
      </div>
   </div>
</div>
@stop
@section('style')
<link href="{{ URL::asset('assets/admin/pages/css/approvalflow/viewApproval.css') }}" rel="stylesheet" type="text/css" />
@stop
@section('userscript')
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/raphael.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/flowchart.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/jquery.flowchart.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/viewApprovalFlow.js') }}" type="text/javascript"></script>
@stop
@extends('layouts.footer')