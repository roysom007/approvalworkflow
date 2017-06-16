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
          APPROVAL TICKETS | <span class="caption-subject bold font-blue uppercase"> FILTER BY :</span>
        <span class="caption-helper sorting">
        <a href="javascript:;" id="All"  onclick = "filterdata('All')" class="active">All (<span class="bold">{{ $allCount }}</span>)</a> &nbsp; 
        <a href="javascript:;" id="Open" onclick = "filterdata('Open')">Open Ticket (<span class="bold">{{ $openCount }}</span>)</a> &nbsp; 
        <a href="javascript:;" id="Close" onclick = "filterdata('Close')">Closed Ticket (<span class="bold">{{ $closeCount }}</span>)</a>
        </span>
        </div>
        <div class="tools">
          <span class="badge bg-blue"><a class="fullscreen" data-toggle="tooltip" title="Hi, This is help Tooltip!" style="color:#fff;"><i class="fa fa-question"></i></a></span>
        </div>
      </div>

      <div class="portlet-body">

        <div class="row">
          <div class="col-md-6 pull-right text-right">
           
            <!-- <a href="/approvalworkflow/addapprovalstatus" class="btn green-meadow">Add Approval Workflow Data</a> -->
         
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

    .fa-link{color:#3598dc !important;}
    .caption-subject{font-size: 12px !important;}

    .ui-iggrid-results{
        height: 30px !important;
    }

</style>

<!-- Ignite UI Required Combined CSS Files -->
<link href="{{ URL::asset('assets/global/plugins/igniteui/infragistics.theme.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/global/plugins/igniteui/infragistics.css') }}" rel="stylesheet" type="text/css" />
<!--Ignite UI Required Combined JavaScript Files-->
<script src="{{ URL::asset('assets/global/plugins/igniteui/infragistics.core.js') }}" type="text/javascript"></script> 
<script src="{{ URL::asset('assets/global/plugins/igniteui/infragistics.lob.js') }}" type="text/javascript"></script>
<link href="{{ URL::asset('assets/global/plugins/igniteui/custom-infragistics.theme.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('assets/global/plugins/igniteui/custom-infragistics.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('css/switch-custom.css') }}" rel="stylesheet" type="text/css" />


@extends('layouts.footer')
<script>

$(function () {

    $('#All').css({"font-weight" : "bold", "text-decoration" : "underline"});

    $("#approvalList").igGrid({
        dataSource: '/approvalworkflow/approvalticketgrid',
        autoGenerateColumns: false,
        mergeUnboundColumns: false,
        responseDataKey: "results",
        generateCompactJSONResponse: false, 
        enableUTCDates: true, 
        width: "100%",
        height: "100%",
        columns: [
            { headerText: "TicketNo", key: "TicketNumber", dataType: "string", width: "10%" },
            { headerText: "Ticket Details", key: "TicketDetails", dataType: "string", width: "20%" },
            { headerText: "Assigned By", key: "UserName", dataType: "string", width: "20%" },
            { headerText: "Assigned On", key: "created_at", dataType: "date", width: "10%", template: "${AssignDate}"  },
            { headerText: "Current Status", key: "CurrentStatus", dataType: "string", width: "10%" },
            { headerText: "Ticket Status", key: "TicketStatus", dataType: "string", width: "10%" },
            { headerText: "Comment", key: "awf_comment", dataType: "string", width: "20%" },
            { headerText: "Actions", key: "CustomAction", dataTpe: "string", width: "5%"},
                ],
            features: [
            {
                name: "Sorting",
                type: "remote",
                columnSettings: [
                {columnKey: 'CustomAction', allowSorting: false },
                {columnKey: 'created_at', allowSorting: true },
                {columnKey: 'TicketNumber', allowSorting: true },
                                ]
            },
            {
                name: "Filtering",
                type: "remote",
                mode: "simple",
                filterDialogContainment: "window",
                columnSettings: [
                    {columnKey: 'TicketNumber', allowFiltering: true },
                    {columnKey: 'TicketDetails', allowFiltering: true },
                    {columnKey: 'PreviousStatus', allowFiltering: false },
                    {columnKey: 'CurrentStatus', allowFiltering: false },
                    {columnKey: 'UserName', allowFiltering: false },
                    {columnKey: 'AssignDate', allowFiltering: true },
                    {columnKey: 'CustomAction', allowFiltering: false },
                    {columnKey: 'awf_comment', allowFiltering: false },
                    {columnKey: 'TicketStatus', allowFiltering: false },
                ]
            },
            { 
                recordCountKey: 'TotalRecordsCount', 
                chunkIndexUrlKey: 'page', 
                chunkSizeUrlKey: 'pageSize', 
                chunkSize: 20,
                name: 'AppendRowsOnDemand', 
                loadTrigger: 'auto', 
                type: 'remote' 
            }
            
            ],
        primaryKey: 'awf_id',
        width: '100%',
        height: '500px',
        initialDataBindDepth: 0,
        localSchemaTransform: false,

  });

});


function filterdata(status)
{
    var sortURL = "/approvalworkflow/approvalticketgrid?filterStatusType="+status+"&page=0&pageSize=20";

    if(status=='All'){

        $('#Open').css({"font-weight" : "", "text-decoration" : ""});
        $('#Close').css({"font-weight" : "", "text-decoration" : ""});
        $('#All').css({"font-weight" : "bold", "text-decoration" : "underline"});

    }else if(status=='Open'){

        $('#All').css({"font-weight" : "", "text-decoration" : ""});
        $('#Close').css({"font-weight" : "", "text-decoration" : ""});
        $('#Open').css({"font-weight" : "bold", "text-decoration" : "underline"});

    }else if(status=='Close'){

        $('#All').css({"font-weight" : "", "text-decoration" : ""});
        $('#Open').css({"font-weight" : "", "text-decoration" : ""});
        $('#Close').css({"font-weight" : "bold", "text-decoration" : "underline"});

    }else{
        $('#All').css({"font-weight" : "bold", "text-decoration" : "underline"});
    }
    
    
    ds = new $.ig.DataSource({
        type: "json",
        responseDataKey: "results",
        dataSource: sortURL,
        callback: function (success, error) {
            if (success) {
                $("#approvalList").igGrid({
                        dataSource: ds,
                        autoGenerateColumns: false
                });
            } else {
                alert(error);
            }
        },
    });
    ds.dataBind();
}


</script>
@stop

@extends('layouts.footer')


