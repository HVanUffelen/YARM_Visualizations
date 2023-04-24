@php
    //dd($selvalues)
@endphp
<div class="card crit-ident mb-3 dataset-container bg-light" data-dsetid="{{$datasetID}}">
    <div class="card-header font-weight-bold">
        <span class="num-chart">{{($datasetID+1)}}.</span> @lang('Chart')
        @if ($datasetID == 0)
            <div style="float:right; padding-left: 5%;">
                {!!Form::button('<i class="fa fa-trash fa-lg" style="color:red;"></i>',['class' =>'btn remove-dataset', 'hidden'])!!}
            </div>
        @else
            <div style="float:right; padding-left: 5%;">
                {!!Form::button('<i class="fa fa-trash fa-lg" style="color:red;"></i>',['class' =>'btn remove-dataset'])!!}
            </div>
        @endif

    </div>
    <div class="col-auto col-sm-2 col-xl-1">
    </div>
    <div class="card-body criteria">
        <div class="rowscrit">
                @include('visualizations::search.inc.criteria_container_inc')
        </div>
        <div class="form-row inputButton">
            <div class="col-12">
                {{Form::button(__('Add Search Criteria').' <i class="fa-solid fa-plus"></i>',['class'=>'btn btn-success rounded btn-sm mb-2 add-criteria shadow'])}}
            </div>
        </div>
    </div>
</div>
