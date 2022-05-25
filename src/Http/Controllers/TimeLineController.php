<?php

namespace Yarm\Visualizations\Http\Controllers;;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class TimeLineController extends Controller
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
            return Redirect::to('/dlbt/visualisations/searchForm?form=TimeLine')->with('alert-danger', trans('ERROR: No search result.'));

        //Result criteria to determine what to show on the result page
        $resultCriteria = [];

        if ($request['resultCriteria'] !== null)
            $resultCriteria = $request['resultCriteria'];

        //Set for checkboxes
        $dataOptionsCheckboxes = [
            'dataOptionsCheckboxes' => [
                'timeline_axis_pointer',
                'timeline_clickable'
            ]
        ];

        $request->merge($dataOptionsCheckboxes);

        //Check which options to use
        list ($chartCriteria, $request) = VisualisationsSearchController::checkDataOptionsValues('TimeLine', $request);

        $request4dataSetCounter = count(preg_grep('/^field.*/', array_keys($request->toArray())));

        for ($i = 0; $i < $request4dataSetCounter; $i++) {
            //buildRequest for individual dataSets
            $request4dataSet = VisualisationsSearchController::buildRequest4dataset($request, $i, '');

            if (isset($request['search0'][0]) and $request['search0'][0] !== null)
                $dataDataset[] = VisualisationsSearchController::search($request4dataSet, 'year');

            $request = VisualisationsSearchController::fillRequest($request, 'searchKeys');

            if (isset($request['search' . $i][0]) and $request['search' . $i][0] !== null)
                $titlesChart[] = $request['search' . $i][0];
        }
        try {
            list ($labelsChart, $dataChart) = TimeLineController::buildDataSetArray($dataDataset, 'year');

            //Put chartData in session for onClick
            $request->session()->put('chartData', $dataChart);

            $Chart = VisualisationsOptionsController::showTimeLineChart($labelsChart, $dataChart, $titlesChart, $chartCriteria);
            $dataChartInfo['Chart'] = $Chart;

            $criteria = SearchController::buildCriteria($request);
            $dataChartInfo['search_info'] = SearchController::buildSearchInfo($request, $criteria);

            //set width an height of graph
            $dataChartInfo['Format'] = VisualisationsOptionsController::setWidthAndHeight($request);

            //Build datOptions with checkbox values
            $dataOptions = TimeLineController::buildDataOptionsForResultViewWithCheckboxes($chartCriteria, $resultCriteria);

            //Put data in session for export to Excel
            $request->session()->put('exportData', $Chart);

            return view('visualizations::timeline.timeLineView', $dataChartInfo)->with($dataOptions);
        } catch (\Throwable $e) {
            return Redirect::to('/dlbt/visualisations/searchForm?form=TimeLine')->with('alert-danger', trans('No results with this search criteria!'));
        }
    }

    /**
     * Build array with usable data
     * @param $searchData
     * @param $listOn
     * @return array
     */
    private static function buildDataSetArray($searchData, $listOn)
    {
        $i = 0;
        $datasetCounter = 0;

        foreach ($searchData as $dataRows) {
            $dataset[$datasetCounter] = [];
            foreach ($searchData[$datasetCounter]['rows'] as $row) {

                //Check if list_on is already in dataset
                $pos = array_search($row[$listOn], array_column($dataset[$datasetCounter], 'label'));

                //if label is in dataset
                if ($pos !== false && $row[$listOn] == $dataset[$datasetCounter][$pos]['label']) {
                    //value data ++ ( = quantity) - add id to array
                    $dataset[$datasetCounter][$pos]['items']++;
                    $dataset[$datasetCounter][$pos]['ids'][] = $row['id'];
                } else {
                    //create new array with label, ids and data (quantity)
                    $dataset[$datasetCounter][$i]['label'] = $row[$listOn];
                    $dataset[$datasetCounter][$i]['ids'][] = $row['id'];
                    $dataset[$datasetCounter][$i]['items'] = 1;
                    $i++;
                }
            }

            //Check if user searched on empty label (selectOn)
            if (count($dataset[$datasetCounter]) == 1 and $dataset[$datasetCounter][0]['label'] == '') {
                $dataset[$datasetCounter][0]['label'] = '(No - ' . $listOn . ' - found!)';
                $dataset[$datasetCounter][0]['items'] = 0;
                $dataset[$datasetCounter][0]['ids'] = [];
            } else if ($dataset[$datasetCounter][0]['label'] == '') {
                $dataset[$datasetCounter][0]['label'] = 'Empty';
            }

            //Make array of ...
            foreach ($dataset[$datasetCounter] as $dataArray) {
                $dataList[$datasetCounter]['labels'][] = $dataArray['label'];
                $dataList[$datasetCounter]['items'][] = $dataArray['items'];
                $dataList[$datasetCounter]['ids'][] = $dataArray['ids'];
            }

            $datasetCounter++;
            $i = 0;
        }

        list($labelsChart, $dataChart) = TimeLineController::makeDatasets4AllLabels($dataList);

        return array($labelsChart, $dataChart);
    }

    /**
     * Split labels (years) from data as a separate array
     * @param $dataList
     * @return array
     */
    private static function makeDatasets4AllLabels($dataList)
    {
        $labels = $dataList[0]['labels'];

        foreach ($dataList as $list) {
            $labels = array_merge($labels, $list['labels']);
        }

        asort($labels);
        $labels = array_unique($labels);
        $labelsChart = array_splice($labels, 0, count($labels));

        $dataChart = TimeLineController::rearrangeDatasetsChart($dataList, $labelsChart);

        return array($labelsChart, $dataChart);
    }

    /**
     * Order dataset for chart
     * @param $dataList
     * @param $labelsChart
     * @return mixed
     */
    private static function rearrangeDataSetsChart($dataList, $labelsChart)
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
     * Building the options for the Timeline view
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

        $dataOptions = VisualisationsSearchController::buildDataOptionsForResultView('TimeLine', $select_values, $resultCriteria);

        $dataOptions['optionsCheckBoxes'] = [
            '0' => [
                'timeline_axis_pointer_value' => $chartCriteria['timeline_axis_pointer'],
                'timeline_clickable_value' => $chartCriteria['timeline_clickable'],
            ]
        ];

        return $dataOptions;
    }


    /**
     * Export chart data to excel file
     * @param Request $request
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public static function excelTimeLine(Request $request)
    {
        $sessionData = $request->session()->get('exportData');
        $spreadsheet = new Spreadsheet();
        $dataArray = [];

        //check on multiple datasets
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
        $sheet = VisualisationsSearchController::writeValuesToExcelSheet($dataArray, $sheet, 'Year', 'labels', 'values');

        //Write excel file and download
        VisualisationsSearchController::downloadExcelFile($spreadsheet, 'TimeLine');

    }


}
