@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}
{{--@php(dd($Format))--}}
@section('content')

    @if (!isset($hideSearchResult))
        <div class="form card">
            <h2 class="card-title">@lang('Search result')</h2>
            <div class="row">
                <div class="col-12 col-sm-7 col-md-6">
                    <p>{!!$search_info['searched']!!}
                        {!!$search_info['selected']!!}</p>
                    <p>{!!$search_info['criteria']!!}</p>
                    <a href={{url('dlbt/visualisations/searchFormRefine?form=Statistics')}}>{{Form::button(__('Change Search'),['class'=>'btn btn-primary'])}}</a>
                </div>

                @if (isset($optionsSelector))
                    <div class="col-12 mt-3 mt-sm-0 col-sm-5 col-md-6">
                        <div class="card">
                            <div class="card-body p-2">
                                <h5 class="card-title">
                                    @lang('Chart options')
                                    <a title="Info" data-toggle="popover" data-trigger="hover"
                                       data-content="@lang('You can set options to change the chart. For Example set the chart type to Pie.')"><i
                                            class="fa fa-info-circle"
                                            style="color: grey"></i></a>
                                </h5>
                                <div class="current-path hidden" id="Statistics"></div>
                                @foreach($optionsSelector as $selector)
                                    {!! Form::label($selector['name_value'], __($selector['label_value']),['class' => 'col-form-label']); !!}
                                    {!! Form::select($selector['name_value'], $selector['select_list'], $selector['select_value'], ['id'=> $selector['name_value'], 'class' => 'custom-select visualisations-select ' . $selector['name_value']]) !!}
                                @endforeach
                                <div style="padding-top: 5%">
                                    <a href="{{route('excelStatistics')}}">{{Form::button(__('Export to Excel'),['class'=>'btn btn-primary'])}}</a>
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
            <div style="float: right">

                <a title="Info" data-toggle="popover" data-trigger="hover"
                   data-placement="bottom"
                   data-content="@lang('You can double click on specific places in the chart to open its details.')"><i
                        class="fa fa-info-circle"
                        style="color: grey"></i></a>


            </div>
            <div class="listing-container mt-3 mb-5">
            @if(Session::has('listViewDataCounter'))
                    <p class="mb-3"><span class='font-weight-bold'>{{ Session::get('listViewDataCounter') }}</span> @lang('items found')</p>
                @endif
            </div>
        @endif

        @foreach($Chart as $singleChart)
            <div class="row">
                <div class="col-sm">

                    <div style="width: {{$Format[0]}};margin: 0 auto;height:{{$Format[1]}};" class="chart-container"
                               id="chart{{$singleChart->id}}"
                               data-id="{{ $singleChart->id }}" data-chart-index="{{ $loop->iteration - 1}}">
                    </div>

                    <script id="json{{$singleChart->id}}" type="application/json">
                        {
                                {!! $singleChart->formatOptions(false, true) !!},
                                "series": {!! $singleChart->formatDatasets() !!}
                        }
                    </script>

                </div>
            </div>
        @endforeach
    </div>

@endsection
