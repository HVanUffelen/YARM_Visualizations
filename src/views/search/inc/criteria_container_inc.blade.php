@php
    //dd($selvalues);
@endphp

@foreach($selvalues['field_value'] as  $values)
    @php($ID = $datasetID . '_' . $loop->index)
    <div class="form-row criteria-input container-input" name="container-input_0">
        <div class="col-6 col-sm-3">
            {!! Form::label('field_' . $ID, __('Field'),['class' => 'col-form-label sr-only font-weight-bold field-label']); !!}
            {!! Form::select('field' . $datasetID . '[]', $fields, $selvalues['field_value'][$loop->iteration-1], [ 'id'=>'field_' . $ID, 'class' => 'custom-select field']) !!}
        </div>
        @if ($selvalues['criterium_value'][$loop->iteration-1] == 'BETWEEN')
            <div class="col-6 col-sm-3">
                {!! Form::label('criterium_' . $ID, __('Criteria'),['class' => 'col-form-label sr-only font-weight-bold criterium-label']); !!}
                {!! Form::select('criterium' . $datasetID . '[]', $criteria_with_between_and_more, $selvalues['criterium_value'][$loop->iteration-1], ['id'=>'criterium_' . $ID, 'class' => 'custom-select criterium']) !!}
            </div>
        @else
            <div class="col-6 col-sm-3">
                {!! Form::label('criterium_' . $ID, __('Criteria'),['class' => 'col-form-label sr-only font-weight-bold criterium-label']); !!}
                {!! Form::select('criterium' . $datasetID . '[]', $criteria, $selvalues['criterium_value'][$loop->iteration-1], ['id'=>'criterium_' . $ID, 'class' => 'custom-select criterium']) !!}
            </div>
        @endif

        <div class="w-100 d-block d-sm-none mb-2"></div>
        <div class="col col-sm-4 col-xl-5">
            {!! Form::label('search_' . $ID, __('Search string'),['class' => 'col-form-label sr-only font-weight-bold search-label']); !!}
            {!! Form::text('search' . $datasetID . '[]', $selvalues['search_value'][$loop->iteration-1], ['id'=>'search_' . $ID, 'class' => 'form-control search', 'placeholder' => __('Keyword(s)')]) !!}
        </div>
        <div class="col-auto col-sm-2 col-xl-1" style="float:right;">
            @if($loop->iteration == 1)
                {!!Form::button('<i class="fa fa-trash fa-lg" style="color:red;"></i>',['class' =>'btn  remove-criteria', 'hidden'])!!}
            @else
                {!!Form::button('<i class="fa fa-trash fa-lg" style="color:red;"></i>',['class' =>'btn remove-criteria'])!!}
            @endif
        </div>
    </div>
@endforeach
