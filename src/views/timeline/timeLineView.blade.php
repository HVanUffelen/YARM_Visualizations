@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}

@section('content')

    @if (!isset($hideSearchResult))
        <div class="form card">
            <h2 class="card-title">@lang('Search result')</h2>
            <div class="row">
                <div class="col-12 col-sm-7 col-md-6">
                    <p>{!!$search_info['searched']!!}
                        {!!$search_info['selected']!!}</p>
                    <p>{!!$search_info['criteria']!!}</p>
                    <a href={{url('dlbt/visualisations/searchFormRefine?form=TimeLine')}}>{{Form::button(__('Change Search'),['class'=>'btn btn-primary'])}}</a>
{{--                    <a href="{{route('excelTimeLine')}}">{{Form::button(__('Excel Export'),['class'=>'btn btn-primary'])}}</a>--}}
                </div>

                @if (isset($optionsSelector))
                    <div class="col-12 mt-3 mt-sm-0 col-sm-5 col-md-6">
                        <div class="card">
                            <div class="card-body p-2">
                                <h5 class="card-title">
                                    @lang('Chart options')
                                    <a title="Info" data-toggle="popover" data-trigger="hover"
                                       data-content="@lang('You can set options to change the chart. For Example enable the AxisPointer option.')"><i
                                            class="fa fa-info-circle"
                                            style="color: grey"></i></a>
                                </h5>
                                @foreach($optionsCheckBoxes as $checkbox)
                                    <div class="col-3 col-sm-3 form-inline">
                                        <div class="custom-control custom-checkbox ml-2">
                                            {!! Form::checkbox('timeline_axis_pointer', 0 , $checkbox['timeline_axis_pointer_value'], ['id'=>'timeline_axis_pointer','class' => 'custom-control-input visualisations-select']) !!}
                                            {!! Form::label('timeline_axis_pointer', __('AxisPointer'),['class' => 'custom-control-label font-weight-bold']); !!}
                                        </div>
                                    </div>
                                    <div class="col-3 col-sm-3 form-inline">
                                        <div class="custom-control custom-checkbox ml-2">
                                            {!! Form::checkbox('timeline_clickable', 0 , $checkbox['timeline_clickable_value'], ['id'=>'timeline_clickable','class' => 'custom-control-input visualisations-select']) !!}
                                            {!! Form::label('timeline_clickable', __('Clickable'),['class' => 'custom-control-label font-weight-bold']); !!}
                                        </div>
                                    </div>
                                @endforeach

                                <div class="current-path hidden" id="TimeLine"></div>
                                @foreach($optionsSelector as $selector)
                                    {!! Form::label($selector['name_value'], __($selector['label_value']),['class' => 'col-form-label']); !!}
                                    {!! Form::select($selector['name_value'], $selector['select_list'], $selector['select_value'], ['id'=> $selector['name_value'], 'class' => 'custom-select visualisations-select ' . $selector['name_value']]) !!}
                                @endforeach
                                <div style="padding-top: 5%">
                                    <a href="{{route('excelTimeLine')}}">{{Form::button(__('Export to Excel'),['class'=>'btn btn-primary'])}}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="page-content-statistics">
        @if (!isset($hideListViewDataCounter))
            <div class="listing-container mt-3 mb-5">
                @if(Session::has('listViewDataCounter'))
                    <p class="mb-3"><span class='font-weight-bold'>{{ Session::get('listViewDataCounter') }}</span>@lang('items found')</p>
                @endif
            </div>
        @endif
        <div class="row">
            <div class="col-sm">
                <div style="width: {{$Format[0]}};margin: 0 auto;height:{{$Format[1]}};" class="chart-container"
                     id="chart{{$Chart->id}}"
                     data-id="{{ $Chart->id }}" data-chart-index="0">
                </div>

                <script id="json{{$Chart->id}}" type="application/json">
                    {
                        {!! $Chart->formatOptions(false, true) !!},
                        "series": {!! $Chart->formatDatasets() !!}
                    }
                </script>
            </div>
        </div>
    </div>

@endsection

