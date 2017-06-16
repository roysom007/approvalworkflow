@extends('layouts.default')
@extends('layouts.header')
@extends('layouts.sideview')
@section('content')

<?php View::share('title', 'Approval Flow List'); ?>
<span id="success_message">@include('flash::message')</span>
<span id="success_message_ajax"></span>
<div class="row">
  <div class="col-md-12 col-sm-12">
    <div class="portlet light tasks-widget" style="height:650px;">
      <div class="portlet-title">
        <div class="caption"> 
          APPROVAL WORKFLOW
        </div>
        <div class="tools">
          <span class="badge bg-blue"><a class="fullscreen" data-toggle="tooltip" title="Hi, This is help Tooltip!" style="color:#fff;"><i class="fa fa-question"></i></a></span>
        </div>
      </div>

      <div class="portlet-body">

        <div class="row">
          <div class="col-md-6 pull-right text-right">
            @if($addApprovalAccess == 1)
            <a href="/approvalworkflow/addapprovalstatus" class="btn green-meadow">Add Approval Workflow Data</a>
            @endif
          </div>
        </div>
        <input type="hidden" name="_token" id="csrf-token" value="{{ Session::token() }}">
        <div class="row">
          <div class="col-md-12">
            <div class="table-scrollable">
              <table id="approvalList"></table>
            </div>
          </div>
        </div>  
      </div>
    </div>
  </div>
</div>
@stop
@section('userscript')
<style type="text/css">
.fa-eye {
    color: #5b9bd1 !important;
}
</style>

<!-- Ignite UI Required Combined CSS Files -->
<link href="{{ URL::asset('assets/global/plugins/igniteui/infragistics.theme.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/global/plugins/igniteui/infragistics.css') }}" rel="stylesheet" type="text/css" />
<!--Ignite UI Required Combined JavaScript Files-->
<script src="{{ URL::asset('assets/global/plugins/igniteui/infragistics.core.js') }}" type="text/javascript"></script> 
<script src="{{ URL::asset('assets/global/plugins/igniteui/infragistics.lob.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/approvalIndex.js') }}" type="text/javascript"></script>
<link href="{{ URL::asset('assets/global/plugins/igniteui/custom-infragistics.theme.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/global/plugins/igniteui/custom-infragistics.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('css/switch-custom.css') }}" rel="stylesheet" type="text/css" />
@stop
@extends('layouts.footer')