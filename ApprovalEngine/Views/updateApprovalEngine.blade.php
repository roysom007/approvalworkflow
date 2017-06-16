@extends('layouts.default')
@extends('layouts.header')
@extends('layouts.sideview')
@section('content')

@section('content')

<?php View::share('title', 'Update Approval Workflow'); ?>
 <div class="portlet light">
  <!-- Header Title Part -->
  <div class="portlet-title">

    <div class="caption">Update Approval Workflow</div>
    <div class="tools">&nbsp;</div>
    </div>
  <form  action="/approvalworkflow/updateapprovalworkflow" method="POST" id ="frm_update_workflow" name = "frm_update_workflow">
  <input type="hidden" name="_token" id="csrf-token" value="{{ Session::token() }}">
  <input type="hidden" name="awf_id" id="awf_id" value="{{ $allApprovalData[0]->awf_id }}">
  <input type="hidden" id="prnt_status_id" value="{{ $allApprovalData[0]->value }}">

  <!-- Detail Part -->
  <div class="portlet-body appmargtop">

    <div class="row">
      <div class="col-md-3">
        <div class="form-group">
          <label for="appr_status_name">Workflow Name</label>
          <input type="text" class="form-control"  name="appr_flow_name" id="appr_flow_name" value = "{{ $allApprovalData[0]->awf_name }}" />
        </div>
      </div>

      <div class="col-md-4">
        <div class="form-group">
          <label for="appr_status_name">Redirect URL</label>
          <input type="text" class="form-control" name="redirect_url" id="redirect_url" value = "{{ $allApprovalData[0]->redirect_url }}" />
            
          <span class="badge bg-blue"><a class="fullscreen" data-toggle="tooltip" title="Use '##' for Dynamic Object ID!" style="color:#fff;"><i class="fa fa-question"></i></a></span>
          
        </div>
      </div>

      <div class="col-md-3">
        <div class="form-group">
          <label for="appr_status_name">Workflow Created For</label>
          <input type="text" class="form-control" disabled name="appr_flow_for" id="appr_flow_for" value = "{{ $allApprovalData[0]->master_lookup_name }}" />
        </div>
      </div>
      <div class="col-md-2 input-margtop">
                <label>&nbsp;</label>
                <div class="row">

                    <div class="col-md-5">
                        <div class="form-group">
                        <div class="mt-checkbox-list">
                        <input type="checkbox" name="awf_email" id="awf_email" value="1" @if($allApprovalData[0]->awf_email==1) {{ 'checked'}} @endif/> Email
                        </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="form-group">
                        <div class="mt-checkbox-list">
                        <input type="checkbox" name="awf_notification" id="awf_notification" value="1" @if($allApprovalData[0]->awf_notification==1) {{ 'checked'}} @endif/> Notification 
                        </div>
                        </div>
                    </div>
                       
                </div> 
            </div>
    </div>

    <div class="row">
      
      <div class="col-md-12">
        
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-advance table-hover" id="approval_data_table" name = "sample_3">

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

          @foreach($allApprovalData as $data)

          <tbody>
            <tr class="odd gradeX list-head">
             <td><input type="text" data_qty="productQty" class="form-control input-sm" value="1" id="product_qty"></td>
             <td data-status="approval_status">
               
              <select id = "state"  name= "app_status[]" class="form-control input-sm">
                @foreach($statusData as $status)
                  <option value = "{{$status->value}}" @if($status->value == $data->awf_status_id) {{ 'selected'}} @endif >{{$status->master_lookup_name}}</option>
                @endforeach
              </select>

             </td>
             <td data-role="data-role">
                <select id = "state"  name= "role_ids[]" class="form-control input-sm">
                @foreach($roleData as $roledata)
                  <option value = "{{$roledata->role_id}}" @if($roledata->role_id == $data->applied_role_id) {{ 'selected'}} @endif >{{$roledata->name}}</option>
                @endforeach
                </select>
             </td>
             <td data-condition="approval_condition">
               
              <select id = "state"  name= "status_condition[]" class="form-control input-sm">
                @foreach($getCondition as $status)
                  <option value = "{{$status->value}}" @if($status->value == $data->awf_condition_id) {{ 'selected'}} @endif >{{$status->master_lookup_name}}</option>
                @endforeach
              </select>

             </td>
             <td data-status-to-go="status_to_go">
               
              <select id = "state"  name= "status_to[]" class="form-control input-sm">
                @foreach($statusData as $status)
                  <option value = "{{$status->value}}" @if($status->value == $data->awf_status_to_go_id) {{ 'selected'}} @endif >{{$status->master_lookup_name}}</option>
                @endforeach
              </select>

             </td>

            <td data-is-final="is_final" align="center">
                <input type="checkbox" @if($data->is_final==1) {{ 'checked'}} @endif name="final[]" value="{{$data->awf_id}}">
            </td>

             <td align="center"> 
               <div style="text-align: center;" class="actionsty">
               <a href="" class="delList"><i class="fa fa-remove"></i></a> &nbsp;
               <a href="" class="moveLeft"><i class="fa fa-plus"></i></a>
               </div>
             </td>
            </tr>
          </tbody>

          @endforeach

        </table>

        <div class="row">   
            <div class="col-md-12 text-center">
              <button type="button" class="btn btn-primary" onclick="location.href='/approvalworkflow/index';">Back</button>
              <button type="button" class="btn btn-primary" id="btn_submit_update" >Submit</button>
            </div>
        </div>

      </div>

    </div>

  </div>

  </form>
</div>
@stop

@section('style')
<link href="{{ URL::asset('assets/admin/pages/css/approvalflow/updateApproval.css') }}" rel="stylesheet" type="text/css" />

<style type="text/css">
    /** this style applied for Drag and Drop */
    body.dragging, body.dragging * {
      cursor: move !important;
    }

    .dragged {
      position: absolute;
      opacity: 0.7;
      z-index: 2000;
    }

    tr.placeholder {
      position: relative;
      /** More li styles **/
    }
    tr.placeholder:before {
      position: absolute;
      /** Define arrowhead **/
    }
</style>

@stop

@section('userscript')
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/jquery-sortable.min.js') }}" type="text/javascript"></script>
<script src="{{ URL::asset('assets/admin/pages/scripts/approvalflow/updateApprovalFlow.js') }}" type="text/javascript"></script>
@stop

@extends('layouts.footer')