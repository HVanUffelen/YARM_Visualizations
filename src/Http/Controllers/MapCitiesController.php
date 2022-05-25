<?php

namespace Yarm\Visualizations\Http\Controllers;;

use App\Http\Controllers\SearchController;
use App\Http\Controllers\ValidationController;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MapCitiesController extends Controller
{
    /**
     * Generates all data by using other functions and returns it to view
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public static function showData(Request $request)
    {
        //Check valid request to prevent XSS!
        list($isValid, $error_messages) = ValidationController::validateRequest($request);

        //Return back with error message
        if ($isValid == false) {
            return redirect()->back()->withInput()
                ->with('alert-danger', $error_messages);
        }

        //check if user can click
        //$silent = VisualisationsOptionsController::checkIfSilent($request);

        //Redirect if user search without search criteria in url
        if ($request->session()->get('searchKeys') == null && $request['field0'] == null)
            return Redirect::to('/dlbt/visualisations/searchForm?form=MapCities')->with('alert-danger', trans('ERROR: No search result.'));

        //Result criteria to determine what to show on the result page
        $resultCriteria = [];

        if ($request['resultCriteria'] !== null)
            $resultCriteria = $request['resultCriteria'];

        $dataOptionsCheckboxes = [
            'dataOptionsCheckboxes' => [
                'mapcities_toggle_3d',
            ]
        ];

        $request->merge($dataOptionsCheckboxes);

        //Check which options to use
        list ($chartCriteria, $request) = VisualisationsSearchController::checkDataOptionsValues('MapCities', $request);
        //Checks if this is the first search request to clear previous saved session data
        if (isset($request->SearchForm) and $request->SearchForm == 'Search') {
            unset($request['SearchForm']);
            $request->session()->forget('chartDataMapData');
            $request->session()->forget('chartData');
            $request->session()->forget('dataDataset');
            $request->session()->forget('filteredYears');
            $request->session()->forget('StartYearArray');
            $request->session()->forget('allYears');
            $request->session()->forget('listViewDataCounter');
            $request->session()->forget('refineCountriesMap');
        }

        //Build chart datamapdata
        if (!session()->get('chartDataMapData')) {
            $request4dataSetCounter = count(preg_grep('/^field.*/', array_keys($request->toArray())));
            for ($i = 0; $i < $request4dataSetCounter; $i++) {
                $request4dataSet = VisualisationsSearchController::buildRequest4dataset($request, $i, 'map');
                if (isset($request['search0'][0]) and $request['search0'][0] !== null)
                    $dataDataset[] = VisualisationsSearchController::search($request4dataSet, '');
                $request = VisualisationsSearchController::fillRequest($request, 'searchKeys');
            }
        }

        //Make all data ready for chart and view
        try {
            if (session()->get('chartDataMapData'))
                $dataDataset = session()->get('dataDataset');
            $request4dataSetCounter = count(preg_grep('/^field.*/', array_keys($request->toArray())));
            for ($i = 0; $i < $request4dataSetCounter; $i++) {
                $searchTerms[] = $request['search' . $i][0];
            }
            $dataChartInfo['Chart'] = MapCitiesController::prepareChart($searchTerms, $dataDataset, $request, $chartCriteria);
            if ($dataChartInfo['Chart'] == false)
                return false;

            //set width an height of graph
            //$dataChartInfo['Format'] = VisualisationsOptionsController::setWidthAndHeight($request);

            $filteredYears = session()->get('filteredYears');
            $allyears = session()->get('allYears');
            $dataChartInfo['AllYearsLatest'] = max($allyears);
            $dataChartInfo['AllYearsEarliest'] = min($allyears);
            $dataChartInfo['ButtonLatestYear'] = max($filteredYears);
            $dataChartInfo['ButtonEarliestYear'] = min($filteredYears);
            $criteria = SearchController::buildCriteria($request);
            $dataChartInfo['search_info'] = SearchController::buildSearchInfo($request, $criteria);
            $dataOptions = MapCitiesController::buildDataOptionsForResultViewWithCheckboxes($chartCriteria, $resultCriteria);

            return view('visualizations::map.mapCitiesView', $dataChartInfo)->with($dataOptions);
        } catch (\Throwable $e) {
            return Redirect::to('/dlbt/visualisations/searchForm?form=MapCities')->with('alert-danger', trans('No results with this search criteria!'));
        }
    }

    /**
     * Prepare variable $chart for showData()
     * @param $searchTerms //Typed in searchterms used to group total items for view
     * @param $dataDataset
     * @param $request
     * @param $chartCriteria //contains options chosen or filters
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function prepareChart($searchTerms, $dataDataset, $request, $chartCriteria)
    {
        //Checks if years are selected with sidebar
        if (isset($request['mapcities_first_year'])) {
            $firstYear = $request['mapcities_first_year'];
            $lastYear = $request['mapcities_last_year'];
        } else {
            $firstYear = 0;
            $lastYear = 3000;
        }
        //Build data with given variables
        list ($dataSet, $idsArray, $filteredYears) = MapCitiesController::buildDataSetArrayPlaces($searchTerms, $dataDataset, $firstYear, $lastYear, $request);

        //Check if result is null after search or filter with sidebar and fill up with empty values
        $datasetCounter = 0;
        $seriesCounter = 0;
        foreach ($dataSet as $dataSeries) {
            if ($dataSeries == null) {
                $dataSet[$datasetCounter][$seriesCounter]['name'] = "";
                $dataSet[$datasetCounter][$seriesCounter]['value'][0] = "";
                $dataSet[$datasetCounter][$seriesCounter]['value'][1] = "";
                $dataSet[$datasetCounter][$seriesCounter]['value'][2] = 0;
                $dataSet[$datasetCounter][$seriesCounter]['ids'] = "";
                $dataSet[$datasetCounter][$seriesCounter]['searchterm'] = $searchTerms[$datasetCounter];
                $dataSet[$datasetCounter][$seriesCounter]['years'] = [0];
            }
            $datasetCounter++;
        }
        $datasetCounter = 0;

        //Build data for view ex.-> "total items: ..."
        foreach ($dataSet as $dataSeries) {
            foreach ($dataSeries as $dataserie) {
                if ($dataserie['searchterm'] !== "Total") {
                    foreach ($dataserie['years'] as $year) {
                        $dataCounterInfo[$datasetCounter]['years'][] = $year;
                    }
                    $dataCounterInfo[$datasetCounter]['searchterm'] = $dataserie['searchterm'];
                    if ($dataCounterInfo[$datasetCounter]['years'][0] == 0) {
                        $dataCounterInfo[$datasetCounter]['totalItems'] = 0;
                    } else {
                        $dataCounterInfo[$datasetCounter]['totalItems'] = count($dataCounterInfo[$datasetCounter]['years']);
                    }
                    $dataCounterInfo[$datasetCounter]['firstYear'] = min($dataCounterInfo[$datasetCounter]['years']);
                    $dataCounterInfo[$datasetCounter]['lastYear'] = max($dataCounterInfo[$datasetCounter]['years']);
                }
            }
            $datasetCounter++;
        }

        //Put data in session for later usage in showData()
        $request->session()->put('chartData', $idsArray);
        $request->session()->put('dataCounterInfo', $dataCounterInfo);
        $request->session()->put('dataDataset', $dataDataset);
        $request->session()->put('chartDataMapData', $dataSet);
        $request->session()->put('filteredYears', $filteredYears);
        $request->session()->put('listViewDataCounter', count($filteredYears));

        $mapDataCounter = 0;

        foreach ($dataSet as $dataSeries) {
            if ($dataSeries[0]['value'][2] != 0) {
                foreach ($dataSeries as $dataSerie) {
                    $mapDataNew[$mapDataCounter][] = $dataSerie;
                }
                $mapDataCounter++;
            }
        }

        //Assign color and sizes to symbols on the map chart
        $colorAndSizesArray = MapCitiesController::getColorsAndSizesArray($mapDataNew);

        //Build chart variable to return it to showData()
        try {
            $chart = VisualisationsOptionsController::showMapCitiesChart($mapDataNew, $colorAndSizesArray, $chartCriteria);
        } catch (\Throwable $e) {
            return false;
        }

        return $chart;
    }

    /**
     * Build data, used by prepareChart()
     * @param $searchTerms
     * @param $SearchData
     * @param $firstYear
     * @param $lastYear
     * @param Request $request
     * @return array
     */
    public static function buildDataSetArrayPlaces($searchTerms, $SearchData, $firstYear, $lastYear, Request $request)
    {
        //Get all places
        $dataPlacesTable = Place::all()->toArray();
        $i = 0;
        $dataSetCounter = 0;
        $dataSet[$dataSetCounter] = [];
        foreach ($SearchData as $dataRows) {
            $dataSet[$dataSetCounter] = [];
            foreach ($SearchData[$dataSetCounter]['rows'] as $row) {
                //Make first allYears Array to keep total years before filtering
                if ($row['year'] != 0) {
                    $allYears[] = $row['year'];
                    session()->put('allYears', $allYears);
                }
                //Filter items by years incoming from the view's slider
                if ($row['year'] >= $firstYear and $row['year'] <= $lastYear and $row['year'] != 0) {
                    //check if there is a place
                    if ($row['place'] !== '') {
                        if (isset($row->places->first()->name))
                            $geoPlace = $row->places->first()->name;
                        else {
                            $geoPlace = '';
                        }
                    }
                    //Check if place has already encountered the dataset you're creating
                    $pos = array_search($geoPlace, array_column($dataSet[$dataSetCounter], 'name'));
                    if ($pos !== false && $geoPlace == $dataSet[$dataSetCounter][$pos]['name']) {
                        $dataSet[$dataSetCounter][$pos]['value'][2]++;
                        $dataSet[$dataSetCounter][$pos]['years'][] = $row['year'];

                        $filteredYears[] = $row['year'];
                        $idsArray[$dataSetCounter][$pos]['ids'][0][] = $row['id'];
                    } else {
                        $dataSet[$dataSetCounter][$i]['name'] = $geoPlace;
                        $posCoordinates = array_search($geoPlace, array_column($dataPlacesTable, 'name'));
                        if ($posCoordinates !== false && $geoPlace == $dataPlacesTable[$posCoordinates]['name']) {
                            $dataSet[$dataSetCounter][$i]['value'][] = $dataPlacesTable[$posCoordinates]['longitude'];
                            $dataSet[$dataSetCounter][$i]['value'][] = $dataPlacesTable[$posCoordinates]['latitude'];
                        }
                        $dataSet[$dataSetCounter][$i]['value'][2] = 1;
                        $dataSet[$dataSetCounter][$i]['searchterm'] = $searchTerms[$dataSetCounter];
                        $dataSet[$dataSetCounter][$i]['years'][] = $row['year'];

                        $filteredYears[] = $row['year'];
                        $idsArray[$dataSetCounter][$i]['ids'][0][] = $row['id'];

                        $i++;
                    }
                }
            }
            $dataSetCounter++;

            $i = 0;
        }

        //Merge $idsArray's from all datasets for onCLick
        $idsArrayAllDatasets = [];

        foreach ($idsArray as $idsSeries) {
            $idsArrayAllDatasets = array_merge($idsArrayAllDatasets, $idsSeries);
        }

        //Put data in session for export to Excel
        $request->session()->put('exportData', $dataSet);

        return array($dataSet, $idsArrayAllDatasets, $filteredYears);
    }

    /**
     * Creates symbol color and sizes depending on value for map cities chart
     * @param $dataSet
     * @return array
     */
    public static function getColorsAndSizesArray($dataSet)
    {

        $colorAndSizesArray = [];
        //ColorScheme Red
        $color[0] = ['#FD0013', '#EF0014', '#D80015', '#BA0018', '#a80019', '#80001C', '#75001C'];
        $mainColor[0] = '#FF5157';

        //ColorScheme Green
        $color[1] = ['#00FD51', '#00E04C', '#01BF46', '#01903E', '#018D3D', '#017338', '#017338'];
        $mainColor[1] = 'green';

        //ColorScheme Blue
        $color[2] = ['#233CCC', '#0C61E8', '#4B5E80', '#4577CC', '#5794FF', '#233CCC', '#233CCC'];
        $mainColor[2] = 'blue';

        //ColorScheme Purple
        $color[3] = ['#793780', '#9A3780', '#BF25CC', '#EE2EFF', '#FF2EC7', '#793780', '#793780'];
        $mainColor[3] = 'purple';

        //ColorScheme Yellow
        $color[4] = ['#E6D693', '#E6DF93', '#FCF2AE', '#FEE7A2', '#FFFEA2', '#E6D693', '#E6D693'];
        $mainColor[4] = 'yellow';

        $dataSetCounter = 0;
        $colorIndex = 0;
        foreach ($dataSet as $dataSeries) {
            foreach ($dataSeries as $dataSeriesItem) {
                $placeValues[] = $dataSeriesItem['value'][2];
            }

            //Check if value is empty
            foreach ($placeValues as $key) {
                if ($key == "") {
                    $colorAndSizesArray[$dataSetCounter]['color'][] = "transparent";
                    $colorAndSizesArray[$dataSetCounter]['size'][] = 0;
                } else {
                    //Create array for color and sizes depending on size of the key
                    if (max($placeValues) <= 10) {
                        if ($key == 1) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $mainColor[$colorIndex];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 12;
                        } elseif ($key == 2) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][0];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 16;
                        } elseif ($key >= 3 && $key <= 4) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][1];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 19;
                        } elseif ($key >= 5 && $key <= 6) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][2];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 24;
                        } elseif ($key >= 7 && $key <= 8) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][3];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 29;
                        } elseif ($key >= 9) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][4];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 34;
                        }
                    } else {

                        if ($key >= 1 && $key <= 5) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $mainColor[$colorIndex];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 12;
                        } elseif ($key >= 6 && $key <= 10) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][0];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 16;
                        } elseif ($key >= 11 && $key <= 15) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][1];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 19;
                        } elseif ($key >= 16 && $key <= 20) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][2];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 24;
                        } elseif ($key >= 21 && $key <= 25) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][3];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 29;
                        } elseif ($key >= 26 && $key <= 99) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][4];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 34;
                        } elseif ($key >= 100 && $key <= 499) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][5];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 44;
                        } elseif ($key >= 500 && $key <= 10000) {
                            $colorAndSizesArray[$dataSetCounter]['color'][] = $color[$colorIndex][6];
                            $colorAndSizesArray[$dataSetCounter]['size'][] = 50;
                        }
                    }

                }

            }
            //Reset colorIndex when there are more than 5 search requests
            if ($colorIndex >= 4) {
                $colorIndex = 0;
            } else {
                $colorIndex++;
            }
            $dataSetCounter++;
        }
        return $colorAndSizesArray;
    }

    /**
     * Get selected values from options for chart
     * @param $chartCriteria
     * @param $resultCriteria
     * @return array
     */
    private static function buildDataOptionsForResultViewWithCheckboxes($chartCriteria, $resultCriteria)
    {
        foreach ($chartCriteria as $chartCriterion) {
            if ($chartCriterion !== false && $chartCriterion !== true) {
                $select_values[] = $chartCriterion;
            }
        }

        $dataOptions = VisualisationsSearchController::buildDataOptionsForResultView('MapCities', $select_values, $resultCriteria);

        $dataOptions['optionsCheckBoxes'] = [
            '0' => [
                'mapcities_toggle_3d_value' => $chartCriteria['mapcities_toggle_3d'],
            ]
        ];

        return $dataOptions;
    }


    /**
     * Export chart data to excel file
     * @param Request $request
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public static function excelMapCities(Request $request)
    {

        $sessionData = $request->session()->get('exportData');
        $spreadsheet = new Spreadsheet();
        $dataArray = [];

        //Build array usable to export to excel
        $i = 0;
        foreach ($sessionData as $dataset) {
            foreach ($dataset as $data) {
                $dataArray[$i]['searchterm'] = $data['searchterm'];
                $dataArray[$i]['values'][] = $data['value'][2];
                $dataArray[$i]['names'][] = $data['name'];
            }
            //Create sheets per dataset and setAutosize for Columns in excel file
            $sheet[$i] = $spreadsheet->createSheet($i)->setTitle($data['searchterm']);
            $spreadsheet->getSheet($i)->getColumnDimension('A')->setAutoSize(true);
            $spreadsheet->getSheet($i)->getColumnDimension('B')->setAutoSize(true);

            $i++;
        }

        //Write excel cell values
        $sheet = VisualisationsSearchController::writeValuesToExcelSheet($dataArray, $sheet, 'City', 'names', 'values');

        //Write excel file and download
        VisualisationsSearchController::downloadExcelFile($spreadsheet, 'MapCities');
    }
}
