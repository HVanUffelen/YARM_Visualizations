<?php

namespace Yarm\Visualizations\Http\Controllers;;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StatisticsController extends Controller
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
        if ($isValid === false) {
            return redirect()->back()->withInput()
                ->with('alert-danger', $error_messages);
        }

        //check if user can click
        $silent = VisualisationsOptionsController::checkIfSilent($request);

        //Redirect if user search without search criteria in url
        if ($request->session()->get('searchKeys') == null && $request['field0'] == null)
            return Redirect::to('/dlbt/visualisations/searchForm?form=Statistics')->with('alert-danger', trans('ERROR: No search result.'));

        //Result criteria to determine what to show on the result page
        $resultCriteria = [];

        if ($request['resultCriteria'] !== null)
            $resultCriteria = $request['resultCriteria'];

        //Check which options to use
        list ($chartCriteria, $request) = VisualisationsSearchController::checkDataOptionsValues('Statistics', $request);

        $request4dataSetCounter = count(preg_grep('/^field.*/', array_keys($request->toArray())));

        for ($i = 0; $i < $request4dataSetCounter; $i++) {
            //buildRequest for individual dataSets
            $request4dataSet = VisualisationsSearchController::buildRequest4dataset($request, $i, '');

            if (isset($request['search0'][0]) and $request['search0'][0] !== null)
                $dataDataset[] = VisualisationsSearchController::search($request4dataSet, $chartCriteria['statistics_sort_criterion']);

            //Refill request
            $request = VisualisationsSearchController::fillRequest($request, 'searchKeys');

            //Fill titles for chart
            if (isset($request['title']) && $request['title'] !== null) {
                $titlesChart[] = $request['title'];
            } else {
                if (isset($request['search' . $i][0]) and $request['search' . $i][0] !== null)
                    $titlesChart[] = $request['search' . $i][0];
                else
                    $titlesChart[] = '';
            }
        }
        try {
            list ($labelsChart, $dataChart) = StatisticsController::buildDataSetArray($dataDataset, $chartCriteria);

            $Chart = VisualisationsOptionsController::showStatisticsChart($labelsChart, $dataChart, $titlesChart, $chartCriteria, $silent);
            $dataChartInfo['Chart'] = (!is_array($Chart)) ? array($Chart) : $Chart; // todo xxx make better

            //Put chartData in session for onClick

            $request->session()->put('chartData', $dataChart);

            /*error_log($request->session()->getId());
            error_log(var_export(session()->all(), true));*/

            $criteria = SearchController::buildCriteria($request);
            $dataChartInfo['search_info'] = SearchController::buildSearchInfo($request, $criteria);

            //set width an height of graph
            $dataChartInfo['Format'] = VisualisationsOptionsController::setWidthAndHeight($request);

            //Put data in session for export to Excel
            $request->session()->put('exportData', $Chart);

            return view('visualizations::stats.statsView', $dataChartInfo)->with(VisualisationsSearchController::buildDataOptionsForResultView('Statistics', $chartCriteria, $resultCriteria));
        } catch (\Throwable $e) {
            //Redirect when something went wrong
            return Redirect::to('/dlbt/visualisations/searchForm?form=Statistics')->withInput()->with('alert-danger', trans('No results with this search criteria!'));
        }
    }

    /**
     * Builds dataSet array for the chart
     * @param $SearchData
     * @param $chartCriteria
     * @return array
     */
    private static function buildDataSetArray($SearchData, $chartCriteria)
    {
        //Chart criteria
        $sortCriterion = $chartCriteria['statistics_sort_criterion'];
        $format = $chartCriteria['statistics_format'];
        $type = $chartCriteria['statistics_type'];

        $i = 0;
        $datasetCounter = 0;
        $dataset[$datasetCounter] = [];

        foreach ($SearchData as $dataRows) {
            $dataset[$datasetCounter] = [];
            foreach ($SearchData[$datasetCounter]['rows'] as $row) {

                //Put all names and roles in row, change Ids with names
                $row = $row->prepareDataset();

                //Look if list_on is already in dataset
                $pos = array_search($row[$sortCriterion], array_column($dataset[$datasetCounter], 'label'));

                //If label is in dataset
                if ($pos !== false && $row[$sortCriterion] == $dataset[$datasetCounter][$pos]['label']) {
                    //value data ++ ( = quantity) - add id to array
                    $dataset[$datasetCounter][$pos]['items']++;
                    $dataset[$datasetCounter][$pos]['ids'][] = $row['id'];
                } else {
                    //Create new array with label, data (quantity) and ids
                    $dataset[$datasetCounter][$i]['label'] = $row[$sortCriterion];
                    $dataset[$datasetCounter][$i]['items'] = 1;
                    $dataset[$datasetCounter][$i]['ids'][] = $row['id'];
                    $i++;
                }
            }

            //Check if user searched on empty label (selectOn)
            if (count($dataset[$datasetCounter]) == 1 and $dataset[$datasetCounter][0]['label'] == '') {
                $dataset[$datasetCounter][0]['label'] = '(No - ' . $sortCriterion . ' - found!)';
                $dataset[$datasetCounter][0]['items'] = 0;
                $dataset[$datasetCounter][0]['ids'] = [];
            } else if ($dataset[$datasetCounter][0]['label'] == '') {
                $dataset[$datasetCounter][0]['label'] = 'Empty';
            }

            //Make array of ...
            foreach ($dataset[$datasetCounter] as $dataArray) {
                if (strlen($dataArray['label']) > 50) {
                    $dataList[$datasetCounter]['labels'][] = substr($dataArray['label'], 0, 50) . '...';
                } else {
                    $dataList[$datasetCounter]['labels'][] = $dataArray['label'];
                }

                $dataList[$datasetCounter]['items'][] = $dataArray['items'];
                $dataList[$datasetCounter]['ids'][] = $dataArray['ids'];
            }

            $datasetCounter++;
            $i = 0;
        }

        //Make dataSets for Charts
        if ($format == 'all')
            list($labelsChart, $dataChart) = StatisticsController::makeDatasets4AllLabels($dataList, $type);
        else
            list($labelsChart, $dataChart) = StatisticsController::makeSlicedDatasets($dataList, $format, $type);

        return array($labelsChart, $dataChart);
    }

    /**
     * Generates labels for every dataSet
     * @param $dataList
     * @param $type
     * @return array
     */
    private static function makeDatasets4AllLabels($dataList, $type)
    {
        //Check if type is pie, doughnut or funnel than add multiple dataSets
        if ($type !== 'line' && $type !== 'bar' && $type !== 'area' && $type !== 'line-bar') {
            $listCounter = 0;

            foreach ($dataList as $list) {
                $labelsChart[$listCounter] = $list['labels'];
                $dataChart[$listCounter]['ids'] = $list['ids'];
                $dataChart[$listCounter]['items'] = $list['items'];
                $listCounter++;
            }
        } else {
            $labels = $dataList[0]['labels'];

            foreach ($dataList as $list) {
                $labels = array_merge($labels, $list['labels']);
            }

            asort($labels);
            $labels = array_unique($labels);
            $labelsChart = array_splice($labels, 0, count($labels));

            //reorder items and ids on new label list (all)
            $dataChart = StatisticsController::rearrangeDatasetsChart($dataList, $labelsChart);
        }

        return array($labelsChart, $dataChart);
    }

    /**
     * Splits the chart data and labels in 2 variables
     * @param $dataList
     * @param $format
     * @param $type
     * @return array
     */
    private static function makeSlicedDatasets($dataList, $format, $type)
    {
        $datasetCounter = 0;
        $i = 0;

        foreach ($dataList as $List) {
            //Sort items (desc)
            arsort($dataList[$datasetCounter]['items']);
            //Make slicedDataList for items, labels and ids
            foreach ($dataList[$datasetCounter]['items'] as $key => $value) {
                if ($i < $format) {
                    $slicedDataList[$datasetCounter]['items'][] = $value;
                    $slicedDataList[$datasetCounter]['labels'][] = $dataList[$datasetCounter]['labels'][$key];
                    $labels[] = $dataList[$datasetCounter]['labels'][$key];
                    $slicedDataList[$datasetCounter]['ids'][] = $dataList[$datasetCounter]['ids'][$key];
                    $i++;
                } else {
                    break 1;
                }
            }
            $i = 0;
            $datasetCounter++;
        }

        //Check if type is pie, doughnut or funnel
        if ($type !== 'line' && $type !== 'bar' && $type !== 'area' && $type !== 'line-bar') {
            $listCounter = 0;

            foreach ($slicedDataList as $list) {
                asort($list['labels']);
                $labelsChart[$listCounter] = $list['labels'];

                unset($selectedSlicedDataList);
                $selectedSlicedDataList[] = $slicedDataList[$listCounter];
                $data = StatisticsController::rearrangeDatasetsChart($selectedSlicedDataList, $labelsChart[$listCounter]);

                $dataChart[$listCounter]['items'] = $data[0]['items'];
                $dataChart[$listCounter]['ids'] = $data[0]['ids'];

                $listCounter++;
            }
        } else {
            asort($labels);
            $labels = array_unique($labels);

            $labelsChart = array_splice($labels, 0, count($labels));
            $dataChart = StatisticsController::rearrangeDatasetsChart($slicedDataList, $labelsChart);
        }

        return array($labelsChart, $dataChart);
    }

    /**
     * Puts the data on the right position in the array
     * @param $dataList
     * @param $labelsChart
     * @return mixed $dataChart
     */
    private static function rearrangeDatasetsChart($dataList, $labelsChart)
    {
        $datasetCounter = 0;

        foreach ($dataList as $list) {
            for ($i = 0; $i < count($labelsChart); $i++) {
                $pos = array_search($labelsChart[$i], $dataList[$datasetCounter]['labels']);

                if ($pos !== false) {
                    $dataChart[$datasetCounter]['items'][] = $list['items'][$pos];
                    $dataChart[$datasetCounter]['ids'][] = $list['ids'][$pos];
                } else {
                    $dataChart[$datasetCounter]['items'][] = 0;
                    $dataChart[$datasetCounter]['ids'][] = null;
                }
            }
            $datasetCounter++;
        }

        return $dataChart;
    }

    /**
     * Export chart data to excel file
     * @param Request $request
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public static function excelStatistics(Request $request)
    {
        $searchKeys = $request->session()->get('searchKeys');

        if (isset($searchKeys['statistics_sort_criterion_new'])) {
            $title = $searchKeys['statistics_sort_criterion_new'];
        } else {
            $title = $searchKeys['statistics_sort_criterion'];
        }

        $sessionData = $request->session()->get('exportData');
        $spreadsheet = new Spreadsheet();
        $dataArray = [];

        //Check type (pie,bar,line,...) and create array with exportable data
        if (isset($sessionData->datasets)) {
            $i = 0;
            foreach ($sessionData->datasets as $dataset) {
                $dataArray[$i]['searchterm'] = $dataset->name;
                $dataArray[$i]['values'] = $dataset->values;
                $dataArray[$i]['labels'] = $sessionData->labels;
                $sheet[$i] = $spreadsheet->createSheet($i)->setTitle($dataset->name);
                $spreadsheet->getSheet($i)->getColumnDimension('A')->setAutoSize(true);
                $spreadsheet->getSheet($i)->getColumnDimension('B')->setAutoSize(true);

                $i++;
            }
        } else {
            $i = 0;
            foreach ($sessionData as $dataset) {
                $dataArray[$i]['searchterm'] = $dataset->datasets[0]->name;
                $dataArray[$i]['values'] = $dataset->datasets[0]->values;
                $dataArray[$i]['labels'] = $dataset->labels;
                $sheet[$i] = $spreadsheet->createSheet($i)->setTitle($dataset->datasets[0]->name);
                $spreadsheet->getSheet($i)->getColumnDimension('A')->setAutoSize(true);
                $spreadsheet->getSheet($i)->getColumnDimension('B')->setAutoSize(true);

                $i++;
            }
        }

        //Write excel cell values
        $sheet = VisualisationsSearchController::writeValuesToExcelSheet($dataArray, $sheet, ucfirst($title), 'labels', 'values');

        //Write excel file and download
        VisualisationsSearchController::downloadExcelFile($spreadsheet, 'Statistics');
    }
}
