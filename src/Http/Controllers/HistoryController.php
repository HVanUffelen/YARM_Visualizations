<?php

namespace Yarm\Visualizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class HistoryController extends Controller
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
            return Redirect::to('/dlbt/visualisations/searchForm?form=History')->with('alert-danger', trans('ERROR: No search result.'));

        //Result criteria to determine what to show on the result page
        $resultCriteria = [];

        if ($request['resultCriteria'] !== null)
            $resultCriteria = $request['resultCriteria'];

        //Check which options to use
        list ($chartCriteria, $request) = VisualisationsSearchController::checkDataOptionsValues('History', $request);

        $request4dataSetCounter = count(preg_grep('/^field.*/', array_keys($request->toArray())));

        for ($i = 0; $i < $request4dataSetCounter; $i++) {
            //buildRequest for individual dataSets
            $request4dataSet = VisualisationsSearchController::buildRequest4dataset($request, $i, '');

            if (isset($request['search0'][0]) and $request['search0'][0] !== null)
                $dataDataset[] = VisualisationsSearchController::search($request4dataSet, '');

            $searchCriteria[] = $request['search' . $i][0];
            $request = VisualisationsSearchController::fillRequest($request, 'searchKeys');
        }
        try {
            list ($dataSetSeries, $dataSetLinks, $idsArray) = HistoryController::buildDataSetArray($dataDataset, $searchCriteria, $chartCriteria);

            //Make data for Excel export
            $exportData = [
                'dataSetSeries' => $dataSetSeries['series'],
                'dataSetLinks' => $dataSetLinks
            ];

            //Put data in session for export to Excel
            $request->session()->put('exportData', $exportData);

            //Put chartData in session for onClick
            $dataIds[0]['ids'] = $idsArray;
            $request->session()->put('chartData', $dataIds);

            $Chart = VisualisationsOptionsController::showHistoryChart($dataSetSeries, $dataSetLinks);
            $dataChartInfo['Chart'] = $Chart;

            //set width an height of graph
            //$dataChartInfo['Format'] = VisualisationsOptionsController::setWidthAndHeight($request);

            $criteria = SearchController::buildCriteria($request);
            $dataChartInfo['search_info'] = SearchController::buildSearchInfo($request, $criteria);

            return view('visualizations::history.historyView', $dataChartInfo)->with(VisualisationsSearchController::buildDataOptionsForResultView('History', $chartCriteria, $resultCriteria));
        } catch (\Throwable $e) {
            return Redirect::to('/dlbt/visualisations/searchForm?form=History')->with('alert-danger', trans('No results with this search criteria!'));
        }
    }

    /**
     * Builds dataSet array for the chart
     * @param $searchData
     * @param $searchCriteria
     * @param $chartCriteria
     * @return array
     */
    private static function buildDataSetArray($searchData, $searchCriteria, $chartCriteria)
    {
        //Chart criteria
        $sortCriterion = $chartCriteria['history_sort_criterion'];
        $targetType = $chartCriteria['history_target_type'];

        $i = 0;
        $datasetCounter = 0;

        foreach ($searchData as $dataRows) {
            $dataset[$datasetCounter] = [];

            foreach ($searchData[$datasetCounter]['rows'] as $row) {
                //Set language target if it exists
                if ($sortCriterion == 'language_target') {
                    if (isset($row->language_target->name))
                        $row[$sortCriterion] = $row->language_target->name;
                    else
                        $row[$sortCriterion] = '';
                }

                //if targetType is reprints then buildDataSerieReprints
                if ($targetType == 'reprints') {
                    list($dataSerie, $i, $pos) = HistoryController::buildDataSerieReprints($dataset, $row, $targetType, $sortCriterion, $i, $datasetCounter);

                    //Use === because pos == null is equal to pos == 0
                    if ($pos === null) {
                        //Build new dataSerie
                        $dataset[$datasetCounter][] = $dataSerie;
                    } else {
                        //Adapt existing dataSerie[pos] to new dataSerie
                        $dataset[$datasetCounter][$pos] = $dataSerie;
                    }
                } else {
                    $dataset[$datasetCounter][] = HistoryController::buildDataSerieTranslationsPublicationsAndAdaptations($dataset, $row, $targetType, $sortCriterion, $i);
                    $i++;
                }
            }

            $datasetCounter++;
            $i = 0;
        }

        //Set 'x source and y reprints' to children titles
        if ($targetType == 'reprints') {
            $dataset = HistoryController::setSourceAndReprintValuesToChildren($dataset);
        }

        list($dataSetSeries, $dataSetLinks, $idsArray) = HistoryController::setDataSetSeriesAndLinks($searchCriteria, $dataset);

        return array($dataSetSeries, $dataSetLinks, $idsArray);
    }

    /**
     * Builds dataSet if options reprints is chosen
     * @param $dataset
     * @param $row
     * @param $targetType
     * @param $sortCriterion
     * @param $i
     * @param $datasetCounter
     * @return array
     */
    private static function buildDataSerieReprints($dataset, $row, $targetType, $sortCriterion, $i, $datasetCounter)
    {
        //Search posistion of title
        $pos = array_search(($row[$sortCriterion] . ' - ' . $row['publisher']), array_column($dataset[$datasetCounter], 'target'));

        if ($pos !== false && ($row[$sortCriterion] . ' - ' . $row['publisher']) == $dataset[$datasetCounter][$pos]['target']) {
            //Set target value ++ when edition is 0 or 1
            if ($row['edition'] <= 1) {
                $dataset[$datasetCounter][$pos]['value']++;
                $dataset[$datasetCounter][$pos]['ids'][] = $row['id'];
            } else {
                //Search if child name already exist
                if (isset($dataset[$datasetCounter][$pos]['children'])) {
                    $posChild = array_search(('reprints - ' . $dataset[$datasetCounter][$pos]['target']), array_column($dataset[$datasetCounter][$pos]['children'], 'name'));
                } else {
                    $posChild = false;
                }

                //Set child value ++ when child name already exist
                if ($posChild !== false && ('reprints - ' . $dataset[$datasetCounter][$pos]['target']) == $dataset[$datasetCounter][$pos]['children'][$posChild]['name']) {
                    $dataset[$datasetCounter][$pos]['children'][$posChild]['ids'][] = $row['id'];
                    $dataset[$datasetCounter][$pos]['children'][$posChild]['value']++;
                } else {
                    $dataset[$datasetCounter][$pos]['children'] = HistoryController::attachChildrenToParents($row['id'], $row, $sortCriterion, $targetType, $dataset[$datasetCounter][$pos]['target']);
                }
            }

            return array($dataset[$datasetCounter][$pos], $i, $pos);
        } else {
            //Set target when edition is 0 or 1
            if ($row['edition'] <= 1) {
                if ($row['publisher'] === "") {
                    $publisher = 'Unknown publisher';
                } else {
                    $publisher = $row['publisher'];
                }

                $dataset[$datasetCounter][$i]['target'] = $row[$sortCriterion] . ' - ' . $publisher;
                $dataset[$datasetCounter][$i]['value'] = 1;
                $dataset[$datasetCounter][$i]['ids'][] = $row['id'];
                $i++;

                return array($dataset[$datasetCounter][$i - 1], $i, null);
            } else {
                //Set child when edition is greater than 1
                $posTarget = array_search(($row[$sortCriterion] . ' - ' . $row['publisher']), array_column($dataset[$datasetCounter], 'target'));

                if ($posTarget !== false && ($row[$sortCriterion] . ' - ' . $row['publisher']) == $dataset[$datasetCounter][$posTarget]['target']) {
                    $dataset[$datasetCounter][$posTarget]['children'] = [];
                    $dataset[$datasetCounter][$posTarget]['children'][] = HistoryController::attachChildrenToParents($row['id'], $row, $sortCriterion, $targetType, $dataset[$datasetCounter][$posTarget]['target']);
                } else {
                    //Search if edition name already exist
                    $pos2ndEdition = array_search(('2nd edition - ' . $row[$sortCriterion] . ' - ' . $row['publisher']), array_column($dataset[$datasetCounter], 'target'));

                    if ($pos2ndEdition !== false && ('2nd edition - ' . $row[$sortCriterion] . ' - ' . $row['publisher']) == $dataset[$datasetCounter][$pos2ndEdition]['target']) {
                        //Set 2nd edition value++ and add current id to existing edition
                        $dataset[$datasetCounter][$pos2ndEdition]['value']++;
                        $dataset[$datasetCounter][$pos2ndEdition]['ids'][] = $row['id'];

                        return array($dataset[$datasetCounter][$pos2ndEdition], $i, $pos2ndEdition);
                    } else {
                        //Set 2nd edition in frond of to the title
                        $dataset[$datasetCounter][$i]['target'] = '2nd edition - ' . $row[$sortCriterion] . ' - ' . $row['publisher'];
                        $dataset[$datasetCounter][$i]['value'] = 1;
                        $dataset[$datasetCounter][$i]['ids'][] = $row['id'];
                        $i++;

                        return array($dataset[$datasetCounter][$i - 1], $i, null);
                    }
                }
            }
        }
    }

    /**
     * Builds dataSet if options reprints is Translations, Publications or adaptations
     * @param $dataset
     * @param $row
     * @param $targetType
     * @param $sortCriterion
     * @param $i
     * @return mixed $dataset
     */
    private static function buildDataSerieTranslationsPublicationsAndAdaptations($dataset, $row, $targetType, $sortCriterion, $i)
    {
        //Search posistion of title in all targets
        $pos = array_search($row[$sortCriterion], array_column($dataset, 'target'));

        if ($pos !== false && ($row[$sortCriterion] . ' - ' . $row['publisher']) == $dataset[$pos]['target']) {
            //Set children if they exist
            if (count($row->childrenTypesForHistory($targetType)) != 0) {
                $childrenIds = $row->childrenTypesForHistory($targetType);
                $dataset[$pos]['children'] = HistoryController::attachChildrenToParents($childrenIds, $row, $sortCriterion, $targetType, '');
            }

            //Set target value ++ when edition is 0 or 1
            $dataset[$pos]['value']++;
            $dataset[$pos]['ids'][] = $row['id'];
        } else {
            //Set title first time it appears as target
            if ($row[$sortCriterion] != '') {
                $dataset[$i]['target'] = $row[$sortCriterion];
            } else {
                $dataset[$i]['target'] = 'Unknown ' . $sortCriterion;
            }

            //Set children if they exist
            if (count($row->childrenTypesForHistory($targetType)) != 0) {
                $childrenIds = $row->childrenTypesForHistory($targetType);
                $dataset[$i]['children'] = HistoryController::attachChildrenToParents($childrenIds, $row, $sortCriterion, $targetType, '');
            }

            $dataset[$i]['value'] = 1;
            $dataset[$i]['ids'][] = $row['id'];
        }

        return $dataset[$i];
    }

    /**
     * Attaches the child items to the correct parent item
     * @param $childrenIds
     * @param $row
     * @param $sortCriterion
     * @param $targetType
     * @param $targetTitle
     * @return mixed $dataset
     */
    private static function attachChildrenToParents($childrenIds, $row, $sortCriterion, $targetType, $targetTitle)
    {
        $childCounter = 0;

        if ($targetType == 'reprints') {
            $dataset[$childCounter]['name'] = 'reprints - ' . $targetTitle;
            $dataset[$childCounter]['ids'][] = $childrenIds;
            $dataset[$childCounter]['value'] = 1;
        } else {
            foreach ($childrenIds as $childId) {
                if ($sortCriterion == 'language_target') {
                    $child = '(T) ' . $row->where('id', '=', $childId)->first()->language_target->name;
                } else {
                    $child = '(T) ' . $row->where('id', '=', $childId)->first()->title;
                }

                $dataset[$childCounter]['name'] = $child;
                $dataset[$childCounter]['ids'] = $childId;
                $dataset[$childCounter]['value'] = 1;
                $childCounter++;
            }
        }

        return $dataset;
    }

    /**
     * Attaches the number of children to the parent
     * @param $dataset
     * @return mixed $dataset
     */
    private static function setSourceAndReprintValuesToChildren($dataset)
    {
        $datasetCounter = 0;

        foreach ($dataset as $dataSeries) {
            $dataSeriesCounter = 0;

            foreach ($dataSeries as $dataSerie) {
                if (isset($dataSerie['children'])) {
                    $childCounter = 0;

                    foreach ($dataSerie['children'] as $child) {
                        $dataset[$datasetCounter][$dataSeriesCounter]['children'][$childCounter]['name'] = '1 source and ' . $child['value'] . ' ' . $child['name'];
                        $childCounter++;
                    }
                }
                $dataSeriesCounter++;
            }
            $datasetCounter++;
        }

        return $dataset;
    }

    /**
     * Make dataSet series and links, required format for the Sankey chart
     * @param $searchCriteria
     * @param $dataset
     * @return array
     */
    private static function setDataSetSeriesAndLinks($searchCriteria, $dataset)
    {
        $datasetCounter = 0;

        foreach ($dataset as $dataSeries) {
            //Sets searched field
            $dataSetSeries['series'][] = [
                'name' => 'DS' . ($datasetCounter + 1) . ' (' . $searchCriteria[$datasetCounter] . ')',
                'value' => count($dataSeries)
            ];

            //Make dataSet for Series and Links
            foreach ($dataSeries as $dataSetRow) {

                //Sets ids for onClik
                $idsArray[] = $dataSetRow['ids'];

                //Sets bullets for every found title if not already in $dataSetSeries
                if (!array_search($dataSetRow['target'], array_column($dataSetSeries['series'], 'name')))
                    $dataSetSeries['series'][] = [
                        'name' => $dataSetRow['target'],
                        'value' => $dataSetRow['value'],
                        'ids' => $dataSetRow['ids'],
                    ];

                //Makes links for every found title to searched field
                $dataSetLinks[] = [
                    'source' => 'DS' . ($datasetCounter + 1) . ' (' . $searchCriteria[$datasetCounter] . ')',
                    'target' => $dataSetRow['target'],
                    'ids' => $dataSetRow['ids'],
                    'value' => $dataSetRow['value']
                ];

                //Makes links if parent_id is not 0 to parent title
                if (isset($dataSetRow['source'])) {
                    $dataSetLinks[] = [
                        'source' => $dataSetRow['source'],
                        'target' => $dataSetRow['target'],
                        'ids' => $dataSetRow['ids'],
                        'value' => $dataSetRow['value']
                    ];
                }

                //Makes links if $dataSetRow has children
                if (isset($dataSetRow['children'])) {
                    foreach ($dataSetRow['children'] as $child) {
                        //Sets bullets for every found title if not already in $dataSetSeries
                        if (!array_search($child['name'], array_column($dataSetSeries['series'], 'name')))
                            $dataSetSeries['series'][] = [
                                'name' => $child['name'],
                                'value' => $child['value'],
                                'ids' => $child['ids']
                            ];

                        $dataSetLinks[] = [
                            'source' => $dataSetRow['target'],
                            'target' => $child['name'],
                            'ids' => $child['ids'],
                            'value' => $child['value']
                        ];
                    }
                }
            }

            $datasetCounter++;
        }

        return array($dataSetSeries, $dataSetLinks, $idsArray);
    }

    /**
     * Export chart data to excel file
     * @param Request $request
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public static function excelHistory(Request $request)
    {
        //Initialise variables
        $sessionData = $request->session()->get('exportData');

        $spreadsheet = new Spreadsheet();
        $dataArray = [];
        $relationsArray = [];
        $excelArray = [];

        //Set title and unset item in array
        $searchTerm = $sessionData['dataSetSeries'][0]['name'];
        $sheet = $spreadsheet->createSheet()->setTitle($searchTerm);


        unset($sessionData['dataSetSeries'][0]);
        //Set data parents and children in the correct column
        foreach ($sessionData['dataSetSeries'] as $dataSetSerie) {
            if (strpos($dataSetSerie['name'], '(T)') === false) {
                $dataArray['firstColumn'][] = $dataSetSerie['name'];
            }
        }

        $relationsCounter = 0;

        //Check to which parent the child belongs
        foreach ($sessionData['dataSetLinks'] as $dataSetLink) {
            if ($dataSetLink['source'] !== $searchTerm) {
                $relationsArray[$relationsCounter]['parent'] = $dataSetLink['source'];
                $relationsArray[$relationsCounter]['child'] = $dataSetLink['target'];
            }

            $relationsCounter++;
        }

        $excelArrayCounter = 0;

        //Reset array keys
        $relationsArray = array_merge($relationsArray);

        //Check which data to write according to relationsArray
        foreach ($dataArray['firstColumn'] as $key => $firstColumn) {
            foreach ($relationsArray as $relation) {
                if ($firstColumn === $relation['parent']) {
                    $excelArray[$excelArrayCounter]['firstColumn'] = $firstColumn;
                    $excelArray[$excelArrayCounter]['secondColumn'] = explode('(T) ', $relation['child'])[1];
                    $excelArrayCounter++;
                } else {
                    $excelArray[$excelArrayCounter]['firstColumn'] = $firstColumn;
                }
            }

            $excelArrayCounter++;
        }

        //If there are is no secondColumn
        if (empty($relationsArray)) {
            $excelArrayCounter = 0;

            foreach ($dataArray['firstColumn'] as $dataRow) {
                $excelArray[$excelArrayCounter]['firstColumn'] = $dataRow;
                $excelArrayCounter++;
            }
        }

        $valueCounter = 0;
        $sheet->setCellValue('A1', 'FirstColumn');
        $sheet->setCellValue('B1', 'SecondColumn');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('B1')->getFont()->setBold(true);

        $cellCounter = 2;
        $i = 0;

        foreach ($excelArray as $data) {
            if (isset($data['secondColumn'])) {
                $sheet->setCellValue('A' . $cellCounter, $data['firstColumn']);
                $sheet->setCellValue('B' . $cellCounter, $data['secondColumn']);
            } else {
                $sheet->setCellValue('A' . $cellCounter, $data['firstColumn']);
            }

            $cellCounter++;
            $i++;
        }

        $spreadsheet->getSheet(1)->getColumnDimension('A')->setAutoSize(true);
        $spreadsheet->getSheet(1)->getColumnDimension('B')->setAutoSize(true);

        //Write excel file and download
        VisualisationsSearchController::downloadExcelFile($spreadsheet, 'History');
    }
}
