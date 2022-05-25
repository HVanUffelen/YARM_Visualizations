@extends('layouts.app')
{{App()->setLocale(Session::get('userLanguage'))}}

@section('content')

    <style>
        .noUi-tooltip {
            border-color: black;
            font-size: 12px;
        }

        .noUi-connect {
            background-color: #DFDFDF;
        }
    </style>

    @if (!isset($hideSearchResult))
        <div class="form card">
            <h2 class="card-title">@lang('Search result')</h2>
            <div class="row">
                <div class="col-12 col-sm-7 col-md-6">
                    <p>{!!$search_info['searched']!!}
                        {!!$search_info['selected']!!}</p>
                    <p>{!!$search_info['criteria']!!}</p>
                    <a href={{url('dlbt/visualisations/searchFormRefine?form=MapGlobe')}}>{{Form::button(__('Change Search'),['class'=>'btn btn-primary'])}}</a>
                </div>
                <div class="col-12 mt-3 mt-sm-0 col-sm-5 col-md-6">
                    <div class="card">
                        <div class="card-body p-2">
                            <h5 class="card-title">
                                @lang('Chart options')
                                <a title="Info" data-toggle="popover" data-trigger="hover"
                                   data-content="@lang('You can set options to change the chart. For Example choose which data you wanna see above the bars.')"><i
                                            class="fa fa-info-circle"
                                            style="color: grey"></i></a></h5>
                            <div class="current-path hidden" id="MapGlobe"></div>
                            @foreach($optionsSelector as $selector)
                                {!! Form::label($selector['name_value'], $selector['label_value'],['class' => 'col-form-label']); !!}
                                {!! Form::select($selector['name_value'], $selector['select_list'], $selector['select_value'], ['class' => 'custom-select map-select ' . $selector['name_value']]) !!}
                            @endforeach
                            <div style="padding-top: 5%">
                                <a href="{{route('excelMapGlobe')}}">{{Form::button(__('Export to Excel'),['class'=>'btn btn-primary'])}}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="page-content-statistics">
        @if (!isset($hideListViewDataCounter))
            <div class="listing-container" id="listing-container">
                @if(Session::has('dataCounterInfo'))
                    @foreach(session('dataCounterInfo') as $sessionArray)
                        @if($sessionArray['totalItems'] !== 0)
                            <p class="mb-3">
                                <span class='font-weight-bold'>{{$sessionArray['totalItems']}}</span>
                                @lang('items found for')
                                <span class='font-weight-bold'>{{$sessionArray['searchterm']}}</span>
                                @lang('between')
                                <span class='font-weight-bold'>{{$sessionArray['firstYear']}}</span>
                                @lang('and')
                                <span class='font-weight-bold'>{{$sessionArray['lastYear']}}</span>
                            </p>
                        @else
                            <p class="mb-3"><span
                                        class='font-weight-bold'>{{$sessionArray['totalItems']}}</span>@lang('items found for')
                                <span class='font-weight-bold'>{{$sessionArray['searchterm']}}</span>
                            </p>
                        @endif
                    @endforeach
                    @if(count(session('dataCounterInfo')) > 1)
                        <p class="mb-3">@lang('Total items:')
                            <span class='font-weight-bold'>{{ Session::get('listViewDataCounter') }}</span> @lang('between')
                            <span class='font-weight-bold'>{{$ButtonEarliestYear}}</span> @lang('and') <span
                                    class='font-weight-bold'>{{$ButtonLatestYear}}</span>
                        </p>
                    @endif
                @endif
                <p style="text-align: center"><a title="Info" data-toggle="popover" data-trigger="hover"
                                                 data-content="@lang('When there is no specific data for your selection, the years will automatically adjust. To see the data you only have to click one time.')"><i
                                class="fa fa-info-circle" style="color: grey"></i> </a></p>
            </div>
        @endif

        <div class="row mapChart">
            <div class="col-sm  border rounded-sm bg-light p-0">
                <div style="width: 100%;margin: 0 auto;height:600px;" class="chart-container"
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

        @if (!isset($hideMapSlider))
            <div class="row" style="text-align: center">
                <div class="col-sm mt-1">
                    <a title="{{$AllYearsEarliest}} - {{$AllYearsLatest}} " data-trigger="hover" data-toggle="popover"
                       data-placement="top"
                       data-content="@lang('The years') {{$AllYearsEarliest}}  & {{$AllYearsLatest}} @lang('are the first and last years your search request has found.')"><i
                                class="fa fa-info-circle" style="color: grey;"></i></a>
                    <br/>
                    <br/>
                    <div class="map-slider" data-min="{{$AllYearsEarliest}}" data-max="{{$AllYearsLatest}}"
                         data-step="1"
                         data-start-left="{{$ButtonEarliestYear}}" data-start-right="{{$ButtonLatestYear}}"
                         data-callback-id="abc">
                        <div class="slider">
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>




    {{--    <div>--}}
    {{--        <h2>Test 3d Charts</h2>--}}
    {{--    </div>--}}
@endsection

@section("additionaljsscripts")

@endsection
