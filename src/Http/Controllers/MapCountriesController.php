<?php

namespace Yarm\Visualizations\Http\Controllers;;

use App\Charts\Echarts;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MapCountriesController extends Controller
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
        $silent = VisualisationsOptionsController::checkIfSilent($request);

        //Redirect if user search without search criteria in url
        if ($request->session()->get('searchKeys') == null && $request['field0'] == null)
            return Redirect::to('/dlbt/visualisations/searchForm?form=MapCountries')->with('alert-danger', trans('ERROR: No search result.'));

        //Result criteria to determine what to show on the result page
        $resultCriteria = [];

        if ($request['resultCriteria'] !== null)
            $resultCriteria = $request['resultCriteria'];

        //Check which options to use
        list ($chartCriteria, $request) = VisualisationsSearchController::checkDataOptionsValues('MapCountries', $request);
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
            $request->session()->forget('refineCitiesMap');
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
            $dataChartInfo['Chart'] = MapCountriesController::prepareChart($searchTerms, $dataDataset, $request, $chartCriteria);

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

            return view('visualizations::map.mapCountriesView', $dataChartInfo)->with(VisualisationsSearchController::buildDataOptionsForResultView('MapCountries', $chartCriteria, $resultCriteria));
        } catch (\Throwable $e) {
            return Redirect::to('/dlbt/visualisations/searchForm?form=MapCountries')->with('alert-danger', trans('No results with this search criteria!'));
        }
    }

    /**
     * Prepare variable $chart for showData()
     * @param $searchTerms //Typed in searchterms used to group total items for view
     * @param $dataDataset
     * @param $request
     * @param $chartCriteria //contains options chosen or filters
     * @return Echarts
     */
    private static function prepareChart($searchTerms, $dataDataset, $request, $chartCriteria)
    {
        //Checks if years are selected with sidebar
        if (isset($request['mapcountries_first_year'])) {
            $firstYear = $request['mapcountries_first_year'];
            $lastYear = $request['mapcountries_last_year'];
        } else {
            $firstYear = 0;
            $lastYear = 3000;
        }
        //Build data with given variables
        list ($dataSet, $idsArray, $filteredYears) = MapCountriesController::buildDataSetArrayCountries($searchTerms, $dataDataset, $firstYear, $lastYear, $request);

        //Check if result is null after search or filter with sidebar and fill up with empty values
        $datasetCounter = 0;
        $seriesCounter = 0;
        foreach ($dataSet as $dataSeries) {
            if ($dataSeries == null) {
                $dataSet[$datasetCounter][$seriesCounter]['name'] = "empty";
                $dataSet[$datasetCounter][$seriesCounter]['value'] = 0;
                $dataSet[$datasetCounter][$seriesCounter]['ids'] = "";
                $dataSet[$datasetCounter][$seriesCounter]['searchterm'] = $searchTerms[$datasetCounter];
                $dataSet[$datasetCounter][$seriesCounter]['years'] = [0];
            }
            $datasetCounter++;
        }

        $datasetCounter = 0;
        //Build data for view ex.-> "total items: ..."
        try {
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
        } catch (\Throwable $e) {
            dd($e);
        }

        //Put data in session for later usage in showData()
        $request->session()->put('chartData', $idsArray);
        $request->session()->put('dataCounterInfo', $dataCounterInfo);
        $request->session()->put('dataDataset', $dataDataset);
        $request->session()->put('chartDataMapData', $dataSet);
        $request->session()->put('filteredYears', $filteredYears);
        $request->session()->put('listViewDataCounter', count($filteredYears));
        $request->session()->put('refineCountriesMap', [0]);

        try {
            //Send data to chart creating function
            $chart = VisualisationsOptionsController::showMapCountriesChart($dataSet, $chartCriteria);
        } catch (\Throwable $e) {
            dd($e);
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
    public static function buildDataSetArrayCountries($searchTerms, $SearchData, $firstYear, $lastYear, Request $request)
    {
        $i = 0;
        $dataSetCounter = 0;

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
                    if ($row['place'] !== '') {

                        //check if there is a country
                        if (isset($row->places->first()->country))
                            $geoPlace = $row->places->first()->country;
                        else
                            $geoPlace = '';
                        $testPlaces[] = $row['place'];
                    }

                    //Check if place has already encountered the dataset you're creating
                    $pos = array_search($geoPlace, array_column($dataSet[$dataSetCounter], 'name'));

                    if ($pos !== false && $geoPlace == $dataSet[$dataSetCounter][$pos]['name']) {
                        $dataSet[$dataSetCounter][$pos]['value']++;
                        $dataSet[$dataSetCounter][$pos]['years'][] = $row['year'];
                        $filteredYears[] = $row['year'];
                        $idsArray[$dataSetCounter]['ids'][$pos][] = $row['id'];
                    } else {
                        $dataSet[$dataSetCounter][$i]['name'] = $geoPlace;
                        $dataSet[$dataSetCounter][$i]['value'] = 1;
                        $dataSet[$dataSetCounter][$i]['searchterm'] = $searchTerms[$dataSetCounter];
                        $dataSet[$dataSetCounter][$i]['years'][] = $row['year'];
                        $filteredYears[] = $row['year'];
                        $idsArray[$dataSetCounter]['ids'][$i][] = $row['id'];
                        $i++;
                    }
                }
            }

            $dataSetCounter++;
            $i = 0;
        }
        $idsArrayKeyReset = array_merge($idsArray);

        // When 1 or more datasets are present , make a Total Dataset
        list($dataSet, $idsArrayKeyReset) = MapCountriesController::buildTotalDataSet($dataSet, $idsArrayKeyReset);

        //Put data in session for export to Excel
        $request->session()->put('exportData', $dataSet);

        return array($dataSet, $idsArrayKeyReset, $filteredYears);
    }

    /**
     * Building the total dataset, used by buildDataSetArrayCountries()
     * @param $dataSet
     * @param $idsArrayKeyReset
     * @return array
     */
    private static function buildTotalDataSet($dataSet, $idsArrayKeyReset)
    {
        foreach ($dataSet as $dataSeries) {
            if ($dataSeries != null)
                $notEmptyDataSets[] = $dataSeries;
        }

        if (count($notEmptyDataSets) > 1) {
            $datasetTotal = [];
            $idsArrayTotal = [];
            $dataSeriesCounter = 0;
            $i = 0;
            $idsCounter = 0;

            foreach ($notEmptyDataSets as $dataSeries) {
                foreach ($dataSeries as $dataSerie) {
                    $pos = array_search($dataSerie['name'], array_column($datasetTotal, 'name'));

                    if ($pos !== false && $dataSerie['name'] == $datasetTotal[$pos]['name']) {
                        foreach ($dataSerie['years'] as $dataSerieYear) {
                            $datasetTotal[$pos]['years'][] = $dataSerieYear;
                        }
                        $datasetTotal[$pos]['value'] += $dataSerie['value'];

                        //Search position of current id in dataset array
                        $posIds = array_search($dataSerie['name'], array_column($notEmptyDataSets[$dataSeriesCounter], 'name'));

                        if ($posIds !== false && $dataSerie['name'] == $notEmptyDataSets[$dataSeriesCounter][$posIds]['name']) {
                            //Foreach idsArray set id to idsArrayTotal position
                            foreach ($idsArrayKeyReset[$dataSeriesCounter]['ids'][$posIds] as $idsRow) {
                                $idsArrayTotal['ids'][$pos][] = $idsRow;
                            }
                        }

                    } else {
                        $datasetTotal[$i]['name'] = $dataSerie['name'];
                        $datasetTotal[$i]['value'] = $dataSerie['value'];
                        $datasetTotal[$i]['searchterm'] = 'Total';
                        $datasetTotal[$i]['years'] = $dataSerie['years'];

                        $idsArrayTotal['ids'][$i] = $idsArrayKeyReset[$dataSeriesCounter]['ids'][$idsCounter];
                        $i++;
                    }

                    $idsCounter++;
                }

                $dataSeriesCounter++;
                $idsCounter = 0;
            }

            array_push($dataSet, $datasetTotal);
            array_push($idsArrayKeyReset, $idsArrayTotal);
        }
        return array($dataSet, $idsArrayKeyReset);
    }

    /**
     * Export chart data to excel file
     * @param Request $request
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public static function excelMapCountries(Request $request)
    {
        $sessionData = $request->session()->get('exportData');
        $spreadsheet = new Spreadsheet();
        $dataArray = [];

        //Build array usable to export to excel
        $i = 0;
        foreach ($sessionData as $dataset) {
            foreach ($dataset as $data) {
                $dataArray[$i]['searchterm'] = $data['searchterm'];
                $dataArray[$i]['values'][] = $data['value'];
                $dataArray[$i]['names'][] = $data['name'];
            }

            //Create sheets per dataset and setAutosize for Columns in excel file
            $sheet[$i] = $spreadsheet->createSheet($i)->setTitle($data['searchterm']);
            $spreadsheet->getSheet($i)->getColumnDimension('A')->setAutoSize(true);
            $spreadsheet->getSheet($i)->getColumnDimension('B')->setAutoSize(true);
            $i++;
        }

        //Write excel cell values
        $sheet = VisualisationsSearchController::writeValuesToExcelSheet($dataArray, $sheet, 'Country', 'names', 'values');

        //Write excel file and download
        VisualisationsSearchController::downloadExcelFile($spreadsheet, 'MapCountries');
    }
}
