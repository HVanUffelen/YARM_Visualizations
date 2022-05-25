<?php

namespace Yarm\Visualizations\Http\Controllers;;

use App\Charts\Echarts;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class VisualisationsOptionsController extends Controller
{

    public static function setWidthAndHeight($request)
    {
        if (isset($request['width']) && isset($request['height'])) {
            $formatChart = array($request['width'], $request['height']);
        } else
            $formatChart = array('100%', '600px');
        return $formatChart;
    }

    public static function checkIfSilent($request)
    {
        if ($request['silent'] == true)
            $silent = $request['silent'];
        else if (!isset($request['silent']) || $request['silent'] == false)
            $silent = false;
        return $silent;
    }


    /**
     * Build chart with received data for Statistics
     * @param $labels //Chart labels
     * @param $data //Chart data
     * @param $titles //Searchterms to identify dataset
     * @param $chartCriteria //Selected options or filters
     * @return Echarts|array
     */
    public static function showStatisticsChart($labels, $data, $titles, $chartCriteria, $valueSilent = false)
    {
        //Set type of chart (bar,line,pie,...)
        $chartType = $chartCriteria['statistics_type'];

        //For each type different options
        switch ($chartType) {
            case 'bar':
                $Chart = new Echarts;

                $Chart->optToolbox($Chart);
                $Chart->optRotateXAxis($Chart);
                $Chart->optRotateYAxis($Chart);
                $Chart->optLegendBottom($Chart);
                if (!empty($titles[0]))
                    $Chart->optTitle($Chart, trans(ucfirst('DS (' . $titles[0]) . ')', [], Session::get('userLanguage')));
                else
                    $Chart->optTitle($Chart, trans(ucfirst('BAR CHART'), [], Session::get('userLanguage')));

                $dataSeriesCounter = 1;

                foreach ($data as $dataSeries) {
                    $Chart->dataset(
                        'DS' . $dataSeriesCounter . ' (' . $titles[$dataSeriesCounter - 1] . ')',
                        $chartType,
                        $dataSeries['items'])->options([
                        'areaStyle' => [],
                        'label' => [
                            'normal' => [
                                'show' => true,
                                'position' => 'inside'
                            ]
                        ],
                        'silent' => $valueSilent,
                    ]);

                    $dataSeriesCounter++;
                }

                $Chart->labels($labels);

                return $Chart;
                break;
            case 'area':
                $Chart = new Echarts;

                $Chart->optToolbox($Chart);
                $Chart->optRotateXAxis($Chart);
                $Chart->optRotateYAxis($Chart);
                $Chart->optLegendBottom($Chart);
                if (!empty($titles[0]))
                    $Chart->optTitle($Chart, trans(ucfirst('DS (' . $titles[0] . ')'), [], Session::get('userLanguage')));
                else
                    $Chart->optTitle($Chart, trans(ucfirst('AREA CHART'), [], Session::get('userLanguage')));
                $dataSeriesCounter = 1;

                foreach ($data as $dataSeries) {

                    $options = ['areaStyle' => [], 'label' => ['normal' => ['show' => true, 'position' => 'top']], 'silent' => $valueSilent,];

                    $Chart->dataset(
                        'DS' . $dataSeriesCounter . ' (' . $titles[$dataSeriesCounter - 1] . ')',
                        'line',
                        $dataSeries['items'])->options($options);

                    $dataSeriesCounter++;
                }

                $Chart->labels($labels);

                return $Chart;
                break;
            case 'pie':
                $datasetCounter = 0;
                foreach ($data as $dataChart) {
                    $Chart = new Echarts;

                    $Chart->optDisableXAndYAxis($Chart);
                    $Chart->optLegendBottom($Chart);
                    $Chart->optToolboxMinimalRight($Chart);
                    $showDataSetCounter = ($datasetCounter > 0) ? $datasetCounter + 1 : '';
                    $Chart->optTitle($Chart, 'DS' . ($showDataSetCounter) . ' (' . $titles[$datasetCounter] . ')');

                    if (count($labels[0]) <= 5) {
                        if ($valueSilent == true) {
                            $options = ['label' => ['normal' => ['position' => 'inside', 'formatter' => "{b}\n{c}"],], 'emphasis' => ['label' => ['fontSize' => 13,]], 'silent' => $valueSilent,];
                        } else {
                            $options = ['label' => ['normal' => ['position' => 'inside', 'formatter' => "{b}"],], 'emphasis' => ['label' => ['fontSize' => 20,]]];
                        }

                    } else {
                        if ($valueSilent == true) {
                            $options = ['label' => ['normal' => ['position' => 'outside', 'formatter' => "{b}\n{c}"],], 'emphasis' => ['label' => ['fontSize' => 13,]], 'silent' => $valueSilent,];
                        } else {
                            $options = ['label' => ['normal' => ['position' => 'outside'],], 'emphasis' => ['label' => ['fontSize' => 20,]]];
                        }
                    }
                    $Chart->dataset($titles[$datasetCounter], 'pie', $dataChart['items'])->options($options);
                    //$Chart->dataset($titles[$datasetCounter], 'pie', $dataChart['items']);
                    $Chart->labels($labels[$datasetCounter]);

                    $multipleChart[] = $Chart;
                    $datasetCounter++;
                }

                return $multipleChart;
                break;
            case 'doughnut':
                $datasetCounter = 0;
                foreach ($data as $dataChart) {
                    $Chart = new Echarts;

                    $Chart->optDisableXAndYAxis($Chart);
                    $Chart->optLegendBottom($Chart);
                    $showDataSetCounter = ($datasetCounter > 0) ? $datasetCounter + 1 : '';
                    $Chart->optTitle($Chart, 'DS' . ($showDataSetCounter) . ' (' . $titles[$datasetCounter] . ')');
                    $Chart->optToolboxMinimalRight($Chart);
                    if (count($labels[0]) <= 5) {
                        if ($valueSilent == true)
                            $options = ['radius' => ['50%', '70%'], 'label' => ['normal' => ['position' => 'inside', 'formatter' => "{b}\n{c}"]], 'emphasis' => ['label' => ['fontSize' => 20]], 'silent' => $valueSilent,];
                        else
                            $options = ['radius' => ['50%', '70%'], 'label' => ['normal' => ['position' => 'inside']], 'emphasis' => ['label' => ['fontSize' => 20]], 'silent' => $valueSilent,];
                    } else {
                        if ($valueSilent == true)
                            $options = ['radius' => ['50%', '70%'], 'label' => ['normal' => ['position' => 'inside', 'formatter' => "{b}\n{c}"]], 'emphasis' => ['label' => ['fontSize' => 20]], 'silent' => $valueSilent,];
                        else
                            $options = ['radius' => ['50%', '70%']];
                    }
                }

                $Chart->dataset($titles[$datasetCounter], 'pie', $dataChart['items'])->options($options);
                $Chart->labels($labels[$datasetCounter]);
                $multipleChart[] = $Chart;
                $datasetCounter++;

                return $multipleChart;
                break;
            case
            'line-bar':
                $Chart = new Echarts;

                $Chart->optToolbox($Chart);
                $Chart->optRotateXAxis($Chart);
                $Chart->optRotateYAxis($Chart);
                $Chart->optLegendBottom($Chart);
                if (!empty($titles[0]))
                    $Chart->optTitle($Chart, trans(ucfirst('DS (' . $titles[0] . ')'), [], Session::get('userLanguage')));
                else
                    $Chart->optTitle($Chart, trans(ucfirst('LINE-BAR CHART'), [], Session::get('userLanguage')));

                $type = ['line', 'bar'];

                $dataSeriesCounter = 1;
                $typeCounter = 0;

                foreach ($data as $dataSeries) {
                    $Chart->dataset(
                        'DS' . $dataSeriesCounter . ' (' . $titles[$dataSeriesCounter - 1] . ')',
                        $type[$typeCounter],
                        $dataSeries['items'])->options(
                        ['label' => ['normal' => ['show' => true, 'position' => 'inside']], 'itemStyle' => ['barBorderRadius' => 5], 'smooth' => true, 'symbolSize' => '15', 'silent' => $valueSilent,]);
                    if ($typeCounter == 1) {
                        $typeCounter = 0;
                    } else {
                        $typeCounter++;
                    }

                    $dataSeriesCounter++;
                }

                $Chart->labels($labels);

                return $Chart;
                break;
            case 'funnel':
                $datasetCounter = 0;
                foreach ($data as $dataChart) {
                    $dataFieldCounter = 0;
                    $dataFunnelChart = [];

                    foreach ($dataChart['items'] as $dataField) {
                        $dataFunnelChart[] = [
                            'value' => $dataField,
                            'name' => $labels[$datasetCounter][$dataFieldCounter]
                        ];
                        $dataFieldCounter++;
                    }

                    $Chart = new Echarts;

                    $Chart->optToolboxMinimalRight($Chart);
                    $Chart->optLegendBottom($Chart);
                    $Chart->optDisableXAndYAxis($Chart);
                    $showDataSetCounter = ($datasetCounter > 0) ? $datasetCounter + 1 : '';
                    $Chart->optTitle($Chart, 'DS' . ($showDataSetCounter) . ' (' . $titles[$datasetCounter] . ')');

                    if ($valueSilent == true)
                        $options = ['gap' => 2, 'label' => ['show' => true, 'position' => 'inside', 'formatter' => "{b} ({c})"], 'emphasis' => ['label' => ['fontSize' => 20]], 'silent' => $valueSilent,];
                    else
                        $options = ['gap' => 2, 'label' => ['show' => true, 'position' => 'inside'], 'emphasis' => ['label' => ['fontSize' => 20]]];

                    $Chart->dataset($titles[$datasetCounter], 'funnel', $dataFunnelChart)->options($options);
                    $Chart->labels($labels[$datasetCounter]);

                    $multipleChart[] = $Chart;
                    $datasetCounter++;
                }

                return $multipleChart;
                break;
            default:
                $Chart = new Echarts;

                $Chart->optToolbox($Chart);
                $Chart->optRotateXAxis($Chart);
                $Chart->optRotateYAxis($Chart);
                $Chart->optLegendBottom($Chart);

                if (!empty($titles[0]))
                    $Chart->optTitle($Chart, trans(ucfirst('DS (' . $titles[0] . ')'), [], Session::get('userLanguage')));
                else
                    $Chart->optTitle($Chart, trans(ucfirst('CHART'), [], Session::get('userLanguage')));


                $dataSeriesCounter = 1;

                foreach ($data as $dataSeries) {
                    $Chart->dataset(
                        'DS' . $dataSeriesCounter . ' (' . $titles[$dataSeriesCounter - 1] . ')',
                        $chartType,
                        $dataSeries['items'])->options([
                        'label' => [
                            'normal' => [
                                'show' => true,
                                'position' => 'top'
                            ]
                        ],
                        'silent' => $valueSilent,
                    ]);

                    $dataSeriesCounter++;
                }

                $Chart->labels($labels);

                return $Chart;
                break;
        }
    }

    /**
     * Build chart with received data for Relations
     * @param $data //Chart data
     * @param $chartCriteria //Selected options or filters
     * @return Echarts
     */
    public static function showRelationsChart($data, $chartCriteria)
    {
        //Chart criteria
        $type = $chartCriteria['relations_type'];
        $treeDepth = $chartCriteria['relations_tree_depth'];

        $Chart = new Echarts;

        //For each type different options
        $Chart->optToolboxMinimalBottom($Chart);
        $Chart->optDisableXAndYAxis($Chart);
        $Chart->optDisableLegend($Chart);

        switch ($type) {
            case 'sunburst':
                $Chart->dataset('Sunburst Chart', 'sunburst', array($data))->options([
                    'type' => 'sunburst',
                    'radius' => [0, '95%'],
                    'levels' => [
                        [],
                        [
                            'r0' => '0%',
                            'r' => '25%',
                            'label' => [
                                'rotate' => 'tangential'
                            ],
                            'itemStyle' => [
                                'color' => '#F2836B'
                            ],
                        ],
                        [
                            'r0' => '25%',
                            'r' => '50%',
                            'label' => [
                                'align' => 'right',
                                'color' => 'white'
                            ],
                            'itemStyle' => [
                                'color' => '#F2B05E'
                            ],
                        ],
                        [
                            'r0' => '50%',
                            'r' => '75%',
                            'label' => [
                                'align' => 'right'
                            ],
                            'itemStyle' => [
                                'color' => '#03738C'
                            ],
                        ],
                        [
                            'r0' => '75%',
                            'r' => '77%',
                            'label' => [
                                'position' => 'outside',
                                'silent' => false,
                                'color' => 'black'
                            ],
                            'itemStyle' => [
                                'borderWidth' => 3,
                                'color' => '#F2836B'
                            ]
                        ]
                    ]
                ]);
                break;
            default:
                $Chart->dataset('Tree Chart', 'tree', array($data))->options([
                    'top' => '1%',
                    'left' => '5%',
                    'bottom' => '1%',
                    'right' => '25%',

                    'symbolSize' => '7',
                    'initialTreeDepth' => $treeDepth,

                    'lineStyle' => [
                        'width' => 3
                    ],

                    'label' => [
                        'position' => 'left',
                        'verticalAlign' => 'middle',
                        'align' => 'right',
                        'fontSize' => 10
                    ],

                    'layout' => $type,

                    'leaves' => [
                        'label' => [
                            'position' => 'right',
                            'verticalAlign' => 'middle',
                            'align' => 'left'
                        ]
                    ],

                    'expandAndCollapse' => true,
                    'animationDuration' => 550,
                    'animationDurationUpdate' => 750
                ]);
                break;
        }

        return $Chart;
    }

    /**
     * Build chart with received data for History
     * @param $data //Chart data
     * @param $links //Links to children/parent , source/target
     * @return Echarts
     */
    public
    static function showHistoryChart($data, $links)
    {
        //Build chart
        $Chart = new Echarts;

        $Chart->optDisableXAndYAxis($Chart);
        $Chart->optToolboxMinimalBottom($Chart);
        $Chart->optDisableLegend($Chart);

        $Chart->dataset('SANKEY CHART', 'sankey', $data['series'])->Options(
            [
                'nodeAlign' => 'left',
                'levels' => [
                    [
                        'depth' => 0,
                        'itemStyle' => [
                            'color' => '#B1BCC7'
                        ],
                        'lineStyle' => [
                            'color' => 'source',
                            'opacity' => 0.2
                        ]
                    ],
                    [
                        'depth' => 1,
                        'itemStyle' => [
                            'color' => 'orange'
                        ],
                        'lineStyle' => [
                            'color' => 'source',
                            'opacity' => 0.4
                        ]
                    ],
                    [
                        'depth' => 2,
                        'itemStyle' => [
                            'color' => 'cyan'
                        ],
                        'lineStyle' => [
                            'color' => 'source',
                            'opacity' => 0.6
                        ]
                    ]
                ],
                'links' => $links
            ]);

        return $Chart;
    }

    /**
     * Build chart with received data for TimeLine
     * @param $labels //Chart labels
     * @param $data //Chart data
     * @param $titles //Searchterms to identify dataset
     * @param $chartCriteria //Selected options or filters
     * @return Echarts
     */
    public
    static function showTimeLineChart($labels, $data, $titles, $chartCriteria)
    {
        $Chart = new Echarts;

        //Set axisPointer option
        if ($chartCriteria['timeline_axis_pointer'] == true) {
            $Chart->optAxisPointerCross($Chart);
        } else {
            $Chart->optAxisPointer($Chart);
        }

        //Set clickable option
        if ($chartCriteria['timeline_clickable'] == true) {
            $clickable = '';
        } else {
            $clickable = 'none';
        }

        //Set line edges option
        switch ($chartCriteria['timeline_line_edges']) {
            case 'sharp':
                $lineEdges = false;
                break;
            case 'smooth':
                $lineEdges = 'smooth';
                break;
        }

        //ColorScheme Rainbow
        $colorGradientStart['rainbow'] = ['#F2B05E', '#3591E8', '#FF0000', '#36EB7F', '#FFF901', '#EB5AFF'];
        $colorGradientEnd['rainbow'] = ['red', '#2CF5EC', '#FFC400', '#249E55', '#FFCA0D', '#894DFF'];
        $colorAccent['rainbow'] = ['#F2836B', '#3835E8', '#BC2C0C', '#16AB43', '#E8CE0C', '#974DFF'];

        //ColorScheme Blue
        $colorGradientStart['blue'] = ['#00F6FF', '#028EE8', '#0A47FF', '#1C02E8', '#7B00FF', '#C500FF'];
        $colorGradientEnd['blue'] = ['#00F6FF', '#028EE8', '#0A47FF', '#1C02E8', '#7B00FF', '#C500FF'];
        $colorAccent['blue'] = ['#028EE8', '#0A47FF', '#1C02E8', '#7B00FF', '#C500FF', '#FF01DF'];

        //ColorScheme Red
        $colorGradientStart['red'] = ['#FF010A', '#FF4E0D', '#FF8C00', '#FFC30D', '#FFDD00', '#FFF200'];
        $colorGradientEnd['red'] = ['#FF010A', '#FF4E0D', '#FF8C00', '#FFC30D', '#FFDD00', '#FFF200'];
        $colorAccent['red'] = ['#FF4E0D', '#FF8C00', '#FFC30D', '#FFDD00', '#FFF200', '#FF010A'];

        //ColorScheme Green
        $colorGradientStart['green'] = ['#E3FF24', '#5BFF30', '#24FF78', '#17FFEF', '#249EFF', '#2252FF'];
        $colorGradientEnd['green'] = ['#E3FF24', '#5BFF30', '#24FF78', '#17FFEF', '#249EFF', '#2252FF'];
        $colorAccent['green'] = ['#5BFF30', '#24FF78', '#17FFEF', '#249EFF', '#2252FF', '#4A0DFF'];

        $Chart->optToolboxWithDataZoom($Chart);
        $Chart->optAxisWithoutGap($Chart);
        $Chart->optDataZoom($Chart);

        $dataSeriesCounter = 1;
        $colorIndex = 0;

        foreach ($data as $dataSeries) {
            $Chart->dataset('DS' . $dataSeriesCounter . ' (' . $titles[$dataSeriesCounter - 1] . ')',
                'line',
                $dataSeries['items'])->options([
                'smooth' => $lineEdges,
                'symbol' => $clickable,
                'itemStyle' => [
                    'color' => $colorAccent[$chartCriteria['timeline_color_scheme']][$colorIndex]
                ],
                'areaStyle' => [
                    'color' => [
                        'type' => 'linear',
                        'x' => 1.5,
                        'y' => 1.5,
                        'z' => 1.5,
                        'colorStops' => [
                            [
                                'offset' => 0,
                                'color' => $colorGradientEnd[$chartCriteria['timeline_color_scheme']][$colorIndex],
                            ],
                            [
                                'offset' => 1,
                                'color' => $colorGradientStart[$chartCriteria['timeline_color_scheme']][$colorIndex],
                            ],
                        ],
                    ]
                ]
            ]);

            if ($colorIndex >= 5) {
                $colorIndex = 0;
            } else {
                $colorIndex++;
            }

            $dataSeriesCounter++;
        }

        $Chart->labels($labels);

        return $Chart;
    }

    /**
     * Build chart with received data for MapCities
     * @param $mapData //Chart data
     * @param $colorAndSizesArray //Sizes and colors for symbols on chart
     * @param $chartCriteria //Selected options or filters
     * @return Echarts
     */
    public
    static function showMapCitiesChart($mapData, $colorAndSizesArray, $chartCriteria)
    {
        $Chart = new Echarts;
        //ColorScheme Modern
        $color['modern'] = 'white';
        $colorborder['modern'] = '#d5e8eb';
        $backgroundColor['modern'] = '#d5e8eb';
        $legendTextColor['modern'] = 'black';
        //ColorScheme Classic
        $color['classic'] = '#B4C67B';
        $colorborder['classic'] = 'black';
        $backgroundColor['classic'] = '#42BFED';
        $legendTextColor['classic'] = 'black';
        //ColorScheme Vintage
        $color['vintage'] = '#EDC69B';
        $colorborder['vintage'] = '#613915';
        $backgroundColor['vintage'] = '#F7D6AD';
        $legendTextColor['vintage'] = 'black';
        //ColorScheme Neon
        $color['neon'] = '#3c473f';
        $colorborder['neon'] = '#05ECF2';
        $backgroundColor['neon'] = 'black';
        $legendTextColor['neon'] = '#05ECF2';
        //ColorScheme Futuristic
        $color['futuristic'] = '#38D2C6';
        $colorborder['futuristic'] = '#54E9E9';
        $backgroundColor['futuristic'] = '#03385A';
        $legendTextColor['futuristic'] = 'white';

        //Toggle 3D or 2D
        if ($chartCriteria['mapcities_toggle_3d'] == true) {
            $chartType = 'bar3D';
            $geoType = 'geo3D';
        } else {
            $chartType = 'effectScatter';
            $geoType = 'geo';
        }

        $Chart->optBackgroundColorMapCities($Chart, $backgroundColor[$chartCriteria['mapcities_color_scheme']]);
        $Chart->optHideAxesMaps($Chart);
        $Chart->optToolboxMaps($Chart);
        $Chart->optTitleMaps($Chart);
        $Chart->optLegendMapCities($Chart, $legendTextColor[$chartCriteria['mapcities_color_scheme']]);
        $Chart->optGeoTypeMapCities($Chart, $geoType, $backgroundColor[$chartCriteria['mapcities_color_scheme']], $color[$chartCriteria['mapcities_color_scheme']], $colorborder[$chartCriteria['mapcities_color_scheme']]);


        $dataSetCounter = 0;
        $dataCounter = 0;
        $symbolTypes['round_rectangle'] = 'roundRect';
        $symbolTypes['circle'] = 'circle';
        $symbolTypes['diamond'] = 'diamond';
        $symbolTypes['pin'] = 'pin';
        $symbolTypes['triangle'] = 'triangle';
        foreach ($mapData as $mapDataSeries) {
            if (count($mapDataSeries) > 0) {
                foreach ($mapDataSeries as $dataSerie) {
                    //Define formatter (shown value above item)
                    $showPlaces['only_values'] = '' . ($dataSerie['value'][2]);
                    $showPlaces['only_cities'] = '{b}';
                    $showPlaces['both'] = '{b}' . ': ' . ($dataSerie['value'][2]);
                    $showPlaces['empty_symbols'] = '';

                    if (isset($dataSerie['searchterm'])) {
                        $labelData = $dataSerie['searchterm'];
                    } else {
                        $labelData = 'Error';
                    }
                    $Chart->dataset($labelData,
                        $chartType,
                        array($dataSerie))->options([
                        'coordinateSystem' => $geoType,
                        'shading' => 'lambert',
                        'barSize' => 0.1,
                        'minHeight' => 0.5,
                        'symbol' => $symbolTypes[$chartCriteria['mapcities_symbol_type']],
                        'symbolSize' => $colorAndSizesArray[$dataSetCounter]['size'][$dataCounter],
                        'animation' => true,
                        'label' => [
                            'formatter' => $showPlaces[$chartCriteria['mapcities_symbol_data']],
                            'show' => true,
                            'trigger' => 'item',
                            'position' => 'inside',
                            'fontSize' => 10,
                            'distance' => 0.1
                        ],
                        'itemStyle' => [
                            'borderColor' => 'black',
                            'borderWidth' => '2',
                            'color' => $colorAndSizesArray[$dataSetCounter]['color'][$dataCounter],
                        ],
                        'emphasis' => [
                            'tooltip' => [
                                'show' => true,
                            ],
                        ],
                        'tooltip' => [
                            'trigger' => 'item',
                            'renderMode' => true,
                            'formatter' => '{b} : ' . $dataSerie['value'][2],
                        ],
                    ]);
                    $dataCounter++;
                }
                $dataSetCounter++;
            }
        }
        return $Chart;

    }

    /**
     * Build chart with received data for MapCountries
     * @param $mapData //Chart data
     * @param $chartCriteria //Selected options or filters
     * @return Echarts
     */
    public
    static function showMapCountriesChart($mapData, $chartCriteria)
    {
        $mapDataCounter = 0;

        //Recreate data array if one is empty after slider selection
        foreach ($mapData as $dataSeries) {
            if ($dataSeries[0]['value'] != 0) {
                foreach ($dataSeries as $dataSerie) {
                    $mapDataNew[$mapDataCounter][] = $dataSerie;
                }
                $mapDataCounter++;
            }
        }

        foreach ($mapDataNew as $dataSeries) {
            foreach ($dataSeries as $dataSerie) {
                $values[] = $dataSerie['value'];
            }
        }

        //Values min and max for ItemStyle option
        if (count($values) > 1) {
            $minValue = min($values);
            $maxValue = max($values);
        } else {
            $minValue = 0;
            $maxValue = max($values);
        }
        $Chart = new Echarts;
        //ColorScheme Modern
        $color1['modern'] = '#AEFFFF';
        $color2['modern'] = '#5465E8';
        $colorborder['modern'] = 'white';
        $backgroundColor1['modern'] = '#d5e8eb';
        $backgroundColor2['modern'] = '#d5e8eb';
        $legendTextColor['modern'] = 'black';

        //ColorScheme Classic
        $color1['classic'] = '#B4C67B';
        $color2['classic'] = '#008F59';
        $colorborder['classic'] = 'black';
        $backgroundColor1['classic'] = '#42BFED';
        $backgroundColor2['classic'] = '#61B5D4';
        $legendTextColor['classic'] = 'white';
        $emphasisLabelColor['classic'] = '';

        //ColorScheme Vintage
        $color1['vintage'] = '#CC9254';
        $color2['vintage'] = '#805B34';
        $colorborder['vintage'] = '#613915';
        $backgroundColor1['vintage'] = '#F9C381';
        $backgroundColor2['vintage'] = '#F7D6AD';
        $legendTextColor['vintage'] = 'black';

        //ColorScheme Neon
        $color1['neon'] = '#4DFFFC';
        $color2['neon'] = '#00CCCA';
        $colorborder['neon'] = '#05ECF2';
        $backgroundColor1['neon'] = '#36364D';
        $backgroundColor2['neon'] = 'black';
        $legendTextColor['neon'] = 'white';

        $dataSeriesCounter = 0;
        $Chart->optBackgroundColorMapCountries($Chart, $backgroundColor1[$chartCriteria['mapcountries_color_scheme']], $backgroundColor2[$chartCriteria['mapcountries_color_scheme']]);
        $Chart->optHideAxesMaps($Chart);
        $Chart->optToolboxMaps($Chart);
        $Chart->optTooltipMapCountries($Chart);
        $Chart->optLegendMapCountries($Chart, $legendTextColor[$chartCriteria['mapcountries_color_scheme']]);
        $Chart->optVisualMapMapCountries($Chart, $minValue, $maxValue, $legendTextColor[$chartCriteria['mapcountries_color_scheme']], $color1[$chartCriteria['mapcountries_color_scheme']], $color2[$chartCriteria['mapcountries_color_scheme']]);
        foreach ($mapDataNew as $dataSeries) {
            if (isset($dataSeries[$dataSeriesCounter]['searchterm'])) {
                $labelData = $dataSeries[$dataSeriesCounter]['searchterm'];
            } else {
                $labelData = '';
            }
            $Chart->dataset($labelData,
                'map',
                $dataSeries)->options([
                'roam' => true,
                'map' => 'world',
                'showLegendSymbol' => false,
                'zoom' => '2',
                'color' => 'yellow',
                'label' => [
                    'show' => false,
                    'color' => $legendTextColor[$chartCriteria['mapcountries_color_scheme']],
                    'formatter' => '{b}' . ' : ' . '{c}'
                ],
                'itemStyle' => [
                    'color' => $color2[$chartCriteria['mapcountries_color_scheme']],
                    'borderWidth' => 1.5,
                    'borderColor' => $colorborder[$chartCriteria['mapcountries_color_scheme']],
                ],
                'emphasis' => [
                    'label' => [
                        'color' => $legendTextColor[$chartCriteria['mapcountries_color_scheme']],
                    ],
                    'itemStyle' => [ // geselecteerd item
                        'shadowColor' => 'rgba(0, 0, 0, 0.5)',
                        'shadowBlur' => 10,
                        'areaColor' => [
                            'type' => 'radial',
                            'x' => 1,
                            'y' => 1,
                            'z' => 1,
                            'colorStops' => [
                                [
                                    'offset' => 0,
                                    'color' => $colorborder[$chartCriteria['mapcountries_color_scheme']],

                                ],
                                [
                                    'offset' => 1,
                                    'color' => $backgroundColor1[$chartCriteria['mapcountries_color_scheme']],

                                ],

                            ],
                        ],

                        'borderColor' => $colorborder[$chartCriteria['mapcountries_color_scheme']],
                        'borderWidth' => 2,
                    ]
                ],
            ]);

            $dataSeriesCounter++;
        }
        return $Chart;
    }

    /**
     * Build chart with received data for MapGlobe
     * @param $mapData //Chart data
     * @param $colorAndSizesArray //Sizes and colors for symbols on chart
     * @param $chartCriteria //Selected options or filters
     * @return Echarts
     */
    public
    static function showMapGlobeChart($mapData, $colorAndSizesArray, $chartCriteria)
    {
        //Globe texture and background source
        $urlWorld = asset('/Images/world.topo.bathy.200401.jpg');
        $urlBackground = asset('/Images/starfield.jpg');

        $Chart = new Echarts;

        $Chart->optHideAxesMaps($Chart);
        $Chart->optToolboxMaps($Chart);
        $Chart->optLegendMapGlobe($Chart);
        $Chart->optTitleMaps($Chart);
        $Chart->optGeoTypeMapGlobe($Chart, $urlWorld, $urlBackground);

        $dataSetCounter = 0;
        $dataCounter = 0;
        foreach ($mapData as $mapDataSeries) {
            foreach ($mapDataSeries as $dataSerie) {
                //Define formatter (shown value above item)
                $showPlaces['only_values'] = '' . ($dataSerie['value'][2]);
                $showPlaces['only_cities'] = '{b}';
                $showPlaces['both'] = '{b}' . ': ' . ($dataSerie['value'][2]);
                $showPlaces['empty_symbols'] = '';
                if (isset($dataSerie['searchterm'])) {
                    $labelData = $dataSerie['searchterm'];
                } else {
                    $labelData = 'Error';
                }
                $Chart->dataset($labelData,
                    'bar3D',
                    array($dataSerie))->options([
                    'coordinateSystem' => 'globe',
                    'shading' => 'realistic',
                    'barSize' => 0.6,
                    'bevelSize' => 5,
                    'minHeight' => 30,
                    'animation' => true,
                    'label' => [
                        'formatter' => $showPlaces[$chartCriteria['mapglobe_symbol_data']],
                        'show' => true,
                        'textStyle' => [
                            'fontSize' => 15,
                            'color' => 'black',
                        ],
                        'distance' => 0.1
                    ],
                    'itemStyle' => [
                        'borderColor' => 'black',
                        'borderWidth' => '2',
                        'color' => $colorAndSizesArray[$dataSetCounter]['color'][$dataCounter],
                    ],
                    'emphasis' => [
                        'tooltip' => [
                            'show' => true,
                        ],
                    ],
                    'tooltip' => [
                        'trigger' => 'item',
                        'renderMode' => true,
                        'formatter' => '{b} : ' . $dataSerie['value'][2],
                    ],
                ]);
                $dataCounter++;
            }
            $dataSetCounter++;
        }
        return $Chart;

    }
}
