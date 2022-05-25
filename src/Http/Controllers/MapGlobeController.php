<?php

namespace Yarm\Visualizations\Http\Controllers;;

use App\Charts\Echarts;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ValidationController;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MapGlobeController extends Controller
{
    /**
     * Generates all data by using other functions and returns it to view
     * Same method as MapCities
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
            return Redirect::to('/dlbt/visualisations/searchForm?form=Statistics')->with('alert-danger', trans('ERROR: No search result.'));

        //Result criteria to determine what to show on the result page
        $resultCriteria = [];

        if ($request['resultCriteria'] !== null)
            $resultCriteria = $request['resultCriteria'];

        //Check which options to use
        list ($chartCriteria, $request) = VisualisationsSearchController::checkDataOptionsValues('MapGlobe', $request);
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
            $dataChartInfo['Chart'] = MapGlobeController::prepareChart($searchTerms, $dataDataset, $request, $chartCriteria);

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
            return view('visualizations::map.mapGlobeView', $dataChartInfo)->with(VisualisationsSearchController::buildDataOptionsForResultView('MapGlobe', $chartCriteria, $resultCriteria));
        } catch (\Throwable $e) {
            return Redirect::to('/dlbt/visualisations/searchForm?form=MapGlobe')->with('alert-danger', trans('No results with this search criteria!'));
        }
    }

    /**
     * Prepare variable $chart for showData()
     * Works exactly the same as prepareChart() in MapCitiesController
     * @param $searchTerms //Typed in searchterms used to group total items for view
     * @param $dataDataset
     * @param $request
     * @param $chartCriteria //contains options chosen or filters
     * @return Echarts
     */
    public static function prepareChart($searchTerms, $dataDataset, $request, $chartCriteria)
    {
        if (isset($request['mapglobe_first_year'])) {
            $firstYear = $request['mapglobe_first_year'];
            $lastYear = $request['mapglobe_last_year'];
        } else {
            $firstYear = 0;
            $lastYear = 3000;
        }
        list ($dataSet, $idsArray, $filteredYears) = MapCitiesController::buildDataSetArrayPlaces($searchTerms, $dataDataset, $firstYear, $lastYear, $request);

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

        $colorAndSizesArray = MapCitiesController::getColorsAndSizesArray($mapDataNew);

        try {
            $chart = VisualisationsOptionsController::showMapGlobeChart($mapDataNew, $colorAndSizesArray, $chartCriteria);
        } catch (\Throwable $e) {
            dd($e);
        }
        return $chart;
    }


    /**
     * Export chart data to excel file
     * @param Request $request
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public static function excelMapGlobe(Request $request)
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
        VisualisationsSearchController::downloadExcelFile($spreadsheet, 'MapGlobe');
    }
}
