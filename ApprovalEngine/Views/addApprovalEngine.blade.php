@extends('layouts.default')
@extends('layouts.header')
@extends('layouts.sideview')
@section('content')

<?php View::share('title', 'Add Approval Workflow'); ?>

<div class="portlet light">
  <div class="portlet-title">
    <div class="caption">Add Approval Workflow</div>
    <div class="tools">&nbsp;</div>
  </div>

  <div class="portlet-body appmargtop">

    <div class="row">

      <div class="col-md-12">
        <form  action="/approvalworkflow/saveapprovalworkflow" method="POST" id ="frm_save_workflow" name = "frm_save_workflow">     
        <input type="hidden" name="_token" id="csrf-token" value="{{ Session::token() }}">
    
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label for="appr_status_name">Approval workflow Name</label>
              <input type="text" class="form-control" name="appr_status_name" id="appr_status_name" @if(!empty($update)) { value = "{{$update->appr_status_name}}" } @endif />
              @if ($errors->has('appr_status_name'))<p style="color:red;">{!!$errors->first('appr_status_name')!!}</p>@endif 
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group">
              <label for="appr_status_name">Redirect URL</label>
              <input type="text" class="form-control" name="redirect_url" id="redirect_url" @if(!empty($update)) { value = "{{$update->redirect_url}}" } @endif />
              @if ($errors->has('redirect_url'))<p style="color:red;">{!!$errors->first('redirect_url')!!}</p>@endif 
              <span class="badge bg-blue"><a class="fullscreen" data-toggle="tooltip" title="Use '##' for Dynamic Object ID!" style="color:#fff;"><i class="fa fa-question"></i></a></span>
            </div>
          </div>

           <div class="col-md-3">
            <div class="form-group">
              <label for="appr_status_for">Approval Status For</label>
              <select id = "appr_status_for"  name =  "appr_status_for" onchange="loadApprovalStatus();" class="form-control">
                <option value="">Select</option>
                @foreach($selecteddata as $selectdata)
                <option value = "{{$selectdata->value}}">{{$selectdata->master_lookup_name}}</option>
                @endforeach
              </select>
            </div>
          </div>

            <div class="col-md-2 input-margtop">
                <label>&nbsp;</label>
                <div class="row">

                    <div class="col-md-5">
                        <div class="form-group">
                        <div class="mt-checkbox-list">
                        <input type="checkbox" name="awf_email" id="awf_email" value="1" /> Email
                        </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="form-group">
                        <div class="mt-checkbox-list">
                        <input type="checkbox" name="awf_notification" id="awf_notification" value="1" /> Notification 
                        </div>
                        </div>
                    </div>
                       
                </div> 
            </div>
         
        </div> 
          

        <input type="hidden" name="_token" id="csrf-token" value="{{ Session::token() }}">  
        
          <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-advance table-hover" id="sample_3" name = "sample_3">
            <thead>
                <tr>
                   <th width="5%">Step</th>
                   <th width="15%">When Status Is</th>
                   <th width="20%">Assigned To</th>
                   <th width="20%">On</th>
                   <th width="20%">Change Status To</th>
                   <th width="10%">Is Final</th>
                   <th width="10%">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
              <tr class="odd gradeX list-head">
               <td><input type="text" data_qty="productQty" class="form-control input-sm" value="1" id="product_qty"></td>
               <td data-status="approval_status"></td>
               <td data-role="data-role"></td>
               <td data-condition="approval_condition"></td>
               <td data-status-to-go="status_to_go"></td>
               <td data-is-final="is_final"></td>
               <td align="center"> 
               <div style="text-align: center;" class="actionsty">
               <a href=""><i class="fa fa-remove"></i></a> &nbsp;
               <a href="" class="moveLeft"><i class="fa fa-plus"></i></a>
               </div>
               </td>
              </tr>
            </tbody>
          </table>

          <div class="row">   
            <div class="col-md-12 text-center">
              <button type="button" class="btn btn-primary" onclick="location.href='/approvalworkflow/index';">Back</button>
              <button type="submit" class="btn btn-primary" id="btn_submit_add" >Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@stop

@section('style')
<style type="text/css">
.input-margtop{margin-top: 5px;}
</style>
<link href="{{ URL::asset('assets/admin/pages/css/approvalflow/addApproval.css') }}" rel="stylesheet" type="text/css" />
@stop

@section('userscript')
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/formValidation.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/bootstrap_framework.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/addApprovalFlow.js') }}" type="text/javascript"></script>
@stop

@extends('layouts.footer')