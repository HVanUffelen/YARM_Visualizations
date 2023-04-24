@if (isset($optionsSelector))
<div class="card crit-ident mb-3 bg-light">
    <div class="card-body ids">
        <div class="rowsi">
            <div class="row">
                @foreach($optionsSelector as $selector)
                <div class="col-12 col-md-4 col-xl-4">
                    <div class="form-row form-group search_in">
                        {!! Form::label($selector['name_value'], __($selector['label_value']),['class' => 'col-form-label ml-2 font-weight-bold']); !!}
                        {!! Form::select($selector['name_value'], $selector['select_list'], $selector['select_value'], ['class' => 'custom-select']) !!}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
