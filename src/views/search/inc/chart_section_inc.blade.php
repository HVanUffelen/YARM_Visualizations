{{ Form::hidden('dataSetCounter','2',['class' => 'dataSetCounter'])}}
{!! Form::hidden('SearchForm', 'Search') !!}
<div class="criteria-container">
        <div class="data-sets bg-light">
            @foreach($selector as $selvalues)
                @php($datasetID = $loop->index)
                @include('visualizations::search.inc.dataset_container_inc')
            @endforeach
        </div>


        @if($add_chart)
            <div class="form-row form-group inputButton">
                <div class="col-12 pl-3">
                    {{--Todo lang--}}
                    {{Form::button(__('Add Dataset / Chart').' <i class="fa-solid fa-plus"></i>',['class'=>'btn btn-sm btn-primary add-chart shadow'])}}
                </div>
            </div>
        @endif
</div>
