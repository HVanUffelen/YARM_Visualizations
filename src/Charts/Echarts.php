<?php

namespace Yarm\Visualizations\Charts;

class Echarts extends BaseChart
{
    /**
     * Chartjs dataset class.
     *
     * @var object
     */
    public $dataset = Dataset::class;

    /**
     * Initiates the Chartjs Line Chart.
     *
     * @return \Yarm\Visualizations\Charts\BaseChart
     */
    public function __construct()
    {
        parent::__construct();

        return $this->options([
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
            ],
            'xAxis' => [
                'show' => true,
            ],
            'yAxis' => [
                'show' => true,
            ],
        ]);
    }

    /**
     * Formats the options.
     *
     * @return self
     */
    public function formatOptions(bool $strict = false, bool $noBraces = false)
    {
        $this->options([
            'xAxis' => [
                'data' => json_decode($this->formatLabels()),
            ],
        ]);

        return parent::formatOptions($strict, $noBraces);
    }

    /**
     * Options to set the chart title.
     *
     * @param $Chart
     * @param $title
     * @return  $Chart
     */
    public function optTitle($Chart,$title)
    {
        $Chart->options([
            'title' => [
                'left' => 'center',
                'text' => $title
            ],
            'grid' => [
                //'left' => 100,
                //'top' => 50,
                //'width' => '100%',
                //'bottom' => '45%',
                'containLabel' => 'true',
            ],
        ]);

        return $Chart;
    }

    /**
     * Options to disable the X and Y axis.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optDisableXAndYAxis($Chart)
    {
        $Chart->options([
            'xAxis' => [
                'show' => false
            ],
            'yAxis' => [
                'show' => false
            ]
        ]);

        return $Chart;
    }

    /**
     * Options to rotate the Y axis.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optRotateYAxis($Chart)
    {
        $Chart->options([
            'yAxis' => [
                //'type' => 'amount',//not possible to set type <-> is set by dataset!
                //'type' => 'category',//not possible to rotate graph, therefore we will have to change plugin (dataset)
                'axisLabel' => [
                    'show' => true,
                    //'showMaxLabel' => true,
                    //'inside' => true,
                    //'align' => 'right',
                    //'right' => true,
                    //'fontStyle' => 'oblique',
                    'interval' => 0,
                    'rotate' => 0,
                ],
            ]
        ]);

        return $Chart;
    }

    /**
     * Options to rotate the X axis.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optRotateXAxis($Chart)
    {
        $Chart->options([
            'xAxis' => [
                //'type' => 'amount',//not possible to rotate graph, therefore we will have to change plugin (dataset)
                'type' => 'category',
                'axisLabel' => [
                    'show' => true,//set show to false for pie and donuth
                    'showMaxLabel' => true,
                    //'inside' => true,
                    //'align' => 'right',
                    //'right' => true,
                    //'fontStyle' => 'oblique',
                    'interval' => 0,
                    'rotate' => 45,
                ],
                'normal' => [
                    'show' => true
                ],
                //'splitLine' => [
                //    'show' => true
                //]
            ],

        ]);

        return $Chart;
    }

    /**
     * Options to set axis without gap.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optAxisWithoutGap($Chart)
    {
        $Chart->options([
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false
            ],
            'yAxis' => [
                'type' => 'value',
                'boundaryGap' => false
            ]
        ]);
        return $Chart;
    }

    /**
     * Options to set toolbox.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optToolbox($Chart)
    {
        $Chart->options([
            'toolbox' => [
                'show' => true,
                'orient' => 'vertical',
                'left' => 'right',
                'top' => 'center',
                'feature' => [
                    'mark' => ['show' => true],
                    'magicType' => ['show' => true,
                        'type' => ['line', 'bar', 'stack'],
                        'title' => [
                            'stack' => 'for stacked charts',
                            'line' => 'for line charts',
                            'bar' => 'for bar charts',
                            //'tiled' => 'for tiled charts',
                        ]
                    ],
                    'restore' => ['show' => true, 'title' => 'restore'],
                    'saveAsImage' => ['show' => true, 'title' => 'export'],
                ],
            ],
        ]);

        return $Chart;
    }

    /**
     * Options to set toolbox with dataZoom.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optToolboxWithDataZoom($Chart)
    {
        $Chart->options([
            'toolbox' => [
                'orient' => 'vertical',
                'left' => 'right',
                'top' => 'center',
                'feature' => [
                    'dataZoom' => [
                        'yAxisIndex' => 'none'
                    ],
                    'restore' => ['title' => 'restore'],
                    'saveAsImage' => ['show' => true, 'title' => 'export'],
                ]
            ]
        ]);
        return $Chart;
    }

    /**
     * Options to set a minimal toolbox floating right.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optToolboxMinimalRight($Chart)
    {
        $Chart->options([
            'toolbox' => [
                'show' => true,
                'orient' => 'vertical',
                'left' => 'right',
                'top' => 'center',
                'feature' => [
                    'mark' => ['show' => true],
//                    'dataView' => ['show' => true, 'readOnly' => false, 'title' => 'view data', 'lang' => ['Data view', 'turn off', 'refresh']],
                    'saveAsImage' => ['show' => true, 'title' => 'export'],
                ],
            ],
        ]);

        return $Chart;
    }

    /**
     * Options to set a minimal toolbox floating bottom.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optToolboxMinimalBottom($Chart)
    {
        $Chart->options([
            'toolbox' => [
                'show' => true,
                'orient' => 'horizontal',
                'left' => 'center',
                'top' => 'bottom',
                'feature' => [
                    'mark' => ['show' => true],
//                    'dataView' => ['show' => true, 'readOnly' => false, 'title' => 'view data', 'lang' => ['Data view', 'turn off', 'refresh']],
                    'saveAsImage' => ['show' => true, 'title' => 'export'],
                ],
            ],
        ]);

        return $Chart;
    }

    /**
     * Options to set dataZoom (slider).
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optDataZoom($Chart)
    {
        $Chart->options([
            'dataZoom' => [
                [
                    'type' => 'inside',
                    'start' => 0,
                    'end' => 10
                ],
                [
                    'start' => 0,
                    'end' => 10,
                    'handleIcon' => 'M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z',
                    'handleSize' => '80%',
                    'handleStyle' => [
                        'color' => '#fff',
                        'shadowBlur' => 3,
                        'shadowColor' => 'rgba(0, 0, 0, 0.6)',
                        'shadowOffsetX' => 2,
                        'shadowOffsetY' => 2
                    ]
                ]
            ]
        ]);
        return $Chart;
    }

    /**
     * Options to set axisPointer standard.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optAxisPointer($Chart)
    {
        $Chart->options(
            [
                'tooltip' => [
                    'trigger' => 'axis'
                ],
            ]);
        return $Chart;
    }

    /**
     * Options to set axisPointer cross.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optAxisPointerCross($Chart)
    {
        $Chart->options(
            [
                'tooltip' => [
                    'trigger' => 'axis',
                    'axisPointer' => [
                        'type' => 'cross'
                    ]
                ],
            ]);
        return $Chart;
    }

    /**
     * Options to align legend to the bottom.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optLegendBottom($Chart)
    {
        $Chart->options([
            'legend' => [
                'show' => true,
                'type' => 'scroll',
                'orient' => 'horizontal',
                //'right' => 10,
                //'top' => 40,
                'bottom' => true,
                //'data' => 'data.legendData',
                //'selected' => 'data.selected'
            ],

        ]);
        return $Chart;
    }

    /**
     * Options to disable the legend.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optDisableLegend($Chart)
    {
        $Chart->options([
            'legend' => [
                'show' => false
            ]
        ]);
        return $Chart;
    }


    /**
     * Options to hide axes for Map charts.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optHideAxesMaps($Chart)
    {
        $Chart->options([
            'xAxis' => [
                'show' => false
            ],

            'yAxis' => [
                'show' => false
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set toolbox options for Map charts.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optToolboxMaps($Chart)
    {
        $Chart->options([
            'toolbox' => [
                'show' => true,
                'left' => 'left',
                'top' => 'top',
                'feature' => [
//                    'dataView' => [
//                        'readOnly' => false,
//                    ],
                    'saveAsImage' => [],
                ]
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set Title options for Map charts.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optTitleMaps($Chart)
    {
        $Chart->options([
            'title' => [
                'left' => 'center',
                'top' => '5',
                'itemGap' => '0',
                'z' => '200',
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set the legend for MapGlobe.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optLegendMapGlobe($Chart)
    {
        $Chart->options([
            'legend' => [
                'type' => 'scroll',
                'orient' => 'horizontal',
                'bottom' => true,
                'color' => '#Bd556',
                'textStyle' => [
                    'color' => 'white'
                ],
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set the geo type for MapGlobe.
     *
     * @param $Chart
     * @param $urlWorld
     * @param $urlBackground
     * @return  $Chart
     */
    public function optGeoTypeMapGlobe($Chart,$urlWorld,$urlBackground)
    {
        $Chart->options([
            'globe' => [
                'baseTexture' => $urlWorld,
                'heightTexture' => $urlWorld,
                'environment' => $urlBackground,
                'shading' => 'realistic',
                'viewControl' => [
                    'autoRotate' => false,
                ],

                'light' => [
                    'main' => [
                        'intensity' => 2,
                        'shadow' => true,
                        'shadowQuality' => 'high',
                        'alpha' => 15
                    ],
                    'ambient' => [
                        'intensity' => 1.5
                    ],
                ],
                'realisticMaterial' => [
                    'roughness' => 10,
                ],
                'postEffect' => [
                    'enable' => true,
                    'bloom' => [
                        'enable' => true,
                        'bloomIntensity' => 1
                    ]
                ],
                'temporalSuperSampling' => [
                    'enable' => true,
                ],
                'displacementScale' => 0.03,
                'displacementQuality' => 'high',
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set the legend for MapCities.
     *
     * @param $Chart
     * @param $color
     * @return  $Chart
     */
    public function optLegendMapCities($Chart, $color)
    {
        $Chart->options([
            'legend' => [
                'type' => 'scroll',
                'orient' => 'horizontal',
                'bottom' => true,
                'color' => '#Bd556',
                'textStyle' => [
                    'color' => $color
                ],
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set geo type options for MapCities.
     *
     * @param $Chart
     * @param $geoType
     * @param $backgroundColor
     * @param $color
     * @param $borderColor
     * @return  $Chart
     */
    public function optGeoTypeMapCities($Chart,$geoType,$backgroundColor,$color,$borderColor)
    {
        $Chart->options([
            $geoType => [
                'map' => 'world',
                'boxHeight' => 5,
                'regionHeight' => 1,
                'silent' => true,
                'shading' => 'lambert',
                'light' => [
                    'main' => [
                        'intensity' => 2,
                        'shadow' => true,
                        'shadowQuality' => 'high',
                        'alpha' => 23.2
                    ],
                ],
                'groundPlane' => [
                    'show' => true,
                    'color' => $backgroundColor,
                ],
                'emphasis' => [
                    'label' => [
                        'show' => true,
                        'areaColor' => '#eee',
                        'bottom' => true,
                    ]
                ],
                'viewControl' => [
                    'distance' => 100,
                    'maxDistance' => 100,
                    'minDistance' => 4,
                    'projection' => 'perspective',
                    'panMouseButton' => 'left',
                    'panSensitivity' => 2,
                    'rotateMouseButton' => 'right',
                    'rotateSensitivity' => 4,
                    'zoomSensitivity' => 4,
                ],
                'itemStyle' => [
                    'color' => $color,
                    'borderWidth' => 1.5,
                    'borderColor' => $borderColor,
                ],
                'roam' => true,
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set the backgroundColor for MapCities.
     *
     * @param $Chart
     * @param $color
     * @return  $Chart
     */
    public function optBackgroundColorMapCities($Chart,$color)
    {
        $Chart->options([
            'backgroundColor' => $color,
        ]);
        return $Chart;
    }


    /**
     * Options to set the legend for MapCountries.
     *
     * @param $Chart
     * @param $color
     * @return  $Chart
     */
    public function optLegendMapCountries($Chart, $color)
    {
        $Chart->options([
            'legend' => [
                'selectedMode' => 'single',
                'textStyle' => [
                    'color' => $color,
                ],
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set the backgroundColor for MapCountries.
     *
     * @param $Chart
     * @param $backgroundColor1
     * @param $backgroundColor2
     * @return  $Chart
     */
    public function optBackgroundColorMapCountries($Chart,$backgroundColor1,$backgroundColor2)
    {
        $Chart->options([
            'backgroundColor' => [
                'type' => 'radial',
                'x' => 0.5,
                'y' => 0.5,
                'z' => 0.5,
                'colorStops' => [
                    [
                        'offset' => 0,
                        'color' => $backgroundColor1,
                    ],
                    [
                        'offset' => 1,
                        'color' => $backgroundColor2,
                    ],
                ],
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set the tooltip for MapCountries.
     *
     * @param $Chart
     * @return  $Chart
     */
    public function optTooltipMapCountries($Chart)
    {
        $Chart->options([
            'tooltip' => [
                'trigger' => 'item',
                'showDelay' => '0',
                'transitionDuration' => '0.2',
            ],
        ]);
        return $Chart;
    }

    /**
     * Options to set the visualMap for MapCountries.
     *
     * @param $Chart
     * @param $minValue
     * @param $maxValue
     * @param $legendColor
     * @param $color1
     * @param $color2
     * @return  $Chart
     */
    public function optVisualMapMapCountries($Chart,$minValue,$maxValue,$legendColor,$color1,$color2)
    {
        $Chart->options([
            'visualMap' => [
                'type' => 'piecewise',
                'splitNumber' => 10,
                'min' => $minValue,
                'max' => $maxValue,
                'textStyle' => [
                    'color' => $legendColor,
                ],
                'inRange' => [
                    'color' => [
                        $color1, $color2,
                    ]
                ],
                'hoverLink' => true,
            ],
        ]);
        return $Chart;
    }

}
