;(function ($) {
    var getJSONData = function (key) {
        try {
            return JSON.parse($('script[type="application/json"]#' + key).text());
        } catch (err) { // if we have not valid json or dont have it
            return null;
        }
    };

    var onready = function () {
        //popovers
        $(function () {
            $('[data-toggle="popover"]').popover()
        })

        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
        //onReady load chart container
        var chartsContainer = $('.chart-container');

        //For popover
        if (chartsContainer.length === 0) {
            return;
        }

        chartsContainer.each(function () {
            var chartContainer = $(this);


            if (chartContainer.data('rendered'))
                return;

            var json = getJSONData('json' + chartContainer.data('id'));

            chartContainer.data('rendered', 1);
            $('#' + chartContainer.data('id') + "_loader").css('display', 'none');
            //document.getElementById(chartContainer.data('id') + "_loader").style.display = 'none';


            //Initialize chart for onCLick()
            var chart = echarts.init(document.getElementById(chartContainer.attr('id')), 'default');
            chart.setOption(json);

            //check if object is 3D to change dblclick to click
            var clickEvent = 'dblclick';

            if (window.location.href.indexOf('Globe') > -1 || window.location.href.indexOf('mapcities_toggle_3d_new=true') > -1) {
                clickEvent = 'click';
            }

            //chart onclick event
            chart.on(clickEvent, function (params) {
                //TODO: make dynamic for future expanding of types
                var linkSelectedDataOnclick = '';
                var seriesIndex = '';

                //If multiple charts underneath each other
                if (chartContainer.data('chart-index') > 0) {
                    seriesIndex = chartContainer.data('chart-index');
                } else {
                    seriesIndex = params.seriesIndex;
                }

                //If data.ids is defined
                if (params.data.ids && params.data.ids !== undefined) {
                    linkSelectedDataOnclick = 'selectedDataOnclick' + "?listData=" + params.name +
                        "&dataIds=" + params.data.ids;
                } else {
                    linkSelectedDataOnclick = 'selectedDataOnclick' + "?listData=" + params.name +
                        "&seriesIndex=" + seriesIndex +
                        "&dataIndex=" + params.dataIndex;
                }

                window.location.href = linkSelectedDataOnclick;
            });
        });

        //Build href for onChange selectBoxes
        var buildOnChangeHref = function (path) {
            var options = $("[id^=" + path.toLowerCase() + "]");
            var href = window.location.origin + '/dlbt/visualisations/showChart' + path;
            var symbol = '?';
            var value = '';
            $.each(options, function (index, val) {
                if (index >= 1) {
                    symbol = '&';
                }
                if ($('.' + val.id).val() !== undefined) {
                    value = $('.' + val.id).val();
                } else {
                    value = $('#' + val.id)[0].checked;
                    $('#' + val.id).addClass('checked').prop('checked', value);
                }
                href += symbol + val.id + '_new=' + value;
            });

            return href;
        };

        //Onchange selectBoxes Visualisations
        $('.visualisations-select').change(function () {
            var current_path = document.getElementsByClassName('current-path')[0].id;
            href = buildOnChangeHref(current_path);
            window.location.href = href;
        });

        //Create slider with years for map charts
        function createSlider(sliderClass) {
            var sliderContainer = $(sliderClass);
            var slider = sliderContainer.find('.slider').get(0);

            noUiSlider.create(slider, {
                start: [sliderContainer.data('start-left'), sliderContainer.data('start-right')],
                step: sliderContainer.data('step'),
                tooltips: true,
                format: {
                    to: function (value) {
                        return value + '';
                    },
                    from: function (value) {
                        return Number(value.replace('', ''));
                    }
                },
                connect: true,
                behaviour: 'drag',
                range: {
                    'min': sliderContainer.data('min'),
                    'max': sliderContainer.data('max')
                },
                pips: {
                    mode: 'range',
                },
            });
            return slider;
        }

        //Onchange selectBoxes Maps
        $('.map-slider').each(function () {
            var slider = createSlider(".map-slider");
            var sliderContainer = $(this);
            $(".map-select").change(function () {
                var current_path = document.getElementsByClassName('current-path')[0].id;

                href = buildOnChangeHref(current_path);

                var first_year = sliderContainer.data('start-left');
                var last_year = sliderContainer.data('start-right');

                href += "&" + current_path.toLowerCase() + "_first_year=" + first_year +
                    "&" + current_path.toLowerCase() + "_last_year=" + last_year;

                window.location.href = href;
            });

            slider.noUiSlider.on('change', function (values, handle, unencoded, tap, positions, noUiSlider) {
                var current_path = document.getElementsByClassName('current-path')[0].id;

                href = buildOnChangeHref(current_path);

                var first_year = values[0].substr(0, 4);
                var last_year = values[1].substr(0, 4);

                href += "&" + current_path.toLowerCase() + "_first_year=" + first_year +
                    "&" + current_path.toLowerCase() + "_last_year=" + last_year;

                window.location.href = href;
            });
        });
    };
    $(document).ready(onready);
}(jQuery));
