<?php

namespace Yarm\Visualizations\Http\Controllers;;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RelationsController extends Controller
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
            return Redirect::to('/dlbt/visualisations/searchForm?form=Relations')->with('alert-danger', trans('ERROR: No search result.'));

        //Result criteria to determine what to show on the result page
        $resultCriteria = [];

        if ($request['resultCriteria'] !== null)
            $resultCriteria = $request['resultCriteria'];

        //Check which options to use
        list ($chartCriteria, $request) = VisualisationsSearchController::checkDataOptionsValues('Relations', $request);

        $request4dataSetCounter = count(preg_grep('/^field.*/', array_keys($request->toArray())));

        for ($i = 0; $i < $request4dataSetCounter; $i++) {
            //buildRequest for individual dataSets
            $request4dataSet = VisualisationsSearchController::buildRequest4dataset($request, $i, '');

            if (isset($request['search0'][0]) and $request['search0'][0] !== null)
                $searchResult = VisualisationsSearchController::search($request4dataSet, '');
            if (!is_array($searchResult)) return $searchResult;
            $dataDataset[] = $searchResult;
        }
        try {
            $searchCriteria = $request['search0'][0];

            list ($dataChart, $idsArray) = RelationsController::buildDataSetArray($dataDataset, $searchCriteria, $chartCriteria);

            //Put chartData in session for onClick
            $dataIds[0]['ids'] = $idsArray;
            $request->session()->put('chartData', $dataIds);

            $Chart = VisualisationsOptionsController::showRelationsChart($dataChart, $chartCriteria);
            $dataChartInfo['Chart'] = $Chart;

            //set width an height of graph
            //$dataChartInfo['Format'] = VisualisationsOptionsController::setWidthAndHeight($request);

            $criteria = SearchController::buildCriteria($request);
            $dataChartInfo['search_info'] = SearchController::buildSearchInfo($request, $criteria);

            return view('visualizations::relations.relationsView', $dataChartInfo)->with(VisualisationsSearchController::buildDataOptionsForResultView('Relations', $chartCriteria, $resultCriteria));
        } catch (\Throwable $e) {
            return Redirect::to('/dlbt/visualisations/searchForm?form=Relations')->with('alert-danger', trans('No results with this search criteria!'));
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
        $firstCriteria = $chartCriteria['relations_first_criteria'];
        $secondCriteria = $chartCriteria['relations_second_criteria'];

        $i = 0;
        $data = [];

        //Build data array
        foreach ($searchData[0]['rows'] as $dataRow) {
            $dataRow = $dataRow->prepareDataset();

            $pos = array_search($dataRow[$firstCriteria], array_column($data, ('FC_' . $firstCriteria)));

            if ($pos !== false && $dataRow[$firstCriteria] == $data[$pos]['FC_' . $firstCriteria]) {
                $data[$pos]['SC_' . $secondCriteria][] = $dataRow[$secondCriteria];
                $data[$pos]['titles'][] = $dataRow['title'];
                $data[$pos]['ids'][] = $dataRow['id'];
            } else {
                $data[$i]['FC_' . $firstCriteria] = $dataRow[$firstCriteria];
                $data[$i]['SC_' . $secondCriteria][] = $dataRow[$secondCriteria];
                $data[$i]['titles'][] = $dataRow['title'];
                $data[$i]['ids'][] = $dataRow['id'];

                $i++;
            }
        }

        $data = RelationsController::setEmptyElementsToUnknown($data, ('FC_' . $firstCriteria), ('SC_' . $secondCriteria));
        $data = RelationsController::sortDataOnTitle($data, ('SC_' . $secondCriteria));

        //Put data array in separate arrays
        foreach ($data as $dataSetRow) {
            $firstCriteriaArray[] = $dataSetRow['FC_' . $firstCriteria];
            $secondCriteriaArray[] = $dataSetRow['SC_' . $secondCriteria];
            $thirdCriteriaArray[] = $dataSetRow['titles'];
            $idsArray[] = $dataSetRow['ids'];
        }

        $dataSet = [];
        $dataSet['name'] = $searchCriteria;
        $firstCriteriaCounter = 0;

        $firstCriteriaArray = RelationsController::sortArrayOnSpecialCharts($firstCriteriaArray);

        //foreach translator
        foreach ($firstCriteriaArray as $keyFirstCriteriaItem => $firstCriteriaItem) {
            $secondCriteriaCounter = 0;

            $dataSet['children'][] =
                [
                    'name' => $firstCriteriaItem,
                ];

            //foreach array of publishers
            foreach ($secondCriteriaArray as $keySecondCriteriaItem => $secondCriteriaItem) {
                if ($keySecondCriteriaItem == $keyFirstCriteriaItem) {

                    $secondCriteriaItem = RelationsController::sortArrayOnSpecialCharts($secondCriteriaItem);

                    //foreach publisher
                    foreach ($secondCriteriaItem as $keySecondCriteriaItemField => $secondCriteriaItemField) {

                        //if first publisher is written
                        if (isset($dataSet['children'][$firstCriteriaCounter]['children'])) {
                            $searchArraySecondCriteria = $dataSet['children'][$firstCriteriaCounter]['children'];
                            $pos = array_search($secondCriteriaItemField, array_column($searchArraySecondCriteria, 'name'));

                            //if position is found
                            if ($pos !== false && $secondCriteriaItemField == $searchArraySecondCriteria[$pos]['name']) {

                                $dataSet = RelationsController::setTitlesToDataSet(
                                    $dataSet, $pos,
                                    $firstCriteriaCounter, $keyFirstCriteriaItem,
                                    $secondCriteriaItemField, $secondCriteriaCounter,
                                    $keySecondCriteriaItem, $keySecondCriteriaItemField,
                                    $thirdCriteriaArray, $idsArray);
                            } else {
                                $dataSet = RelationsController::setTitlesToDataSet(
                                    $dataSet, null,
                                    $firstCriteriaCounter, $keyFirstCriteriaItem,
                                    $secondCriteriaItemField, $secondCriteriaCounter,
                                    $keySecondCriteriaItem, $keySecondCriteriaItemField,
                                    $thirdCriteriaArray, $idsArray);

                                $secondCriteriaCounter++;
                            }
                        } else {
                            $dataSet = RelationsController::setTitlesToDataSet(
                                $dataSet, null,
                                $firstCriteriaCounter, $keyFirstCriteriaItem,
                                $secondCriteriaItemField, $secondCriteriaCounter,
                                $keySecondCriteriaItem, $keySecondCriteriaItemField,
                                $thirdCriteriaArray, $idsArray);

                            $secondCriteriaCounter++;
                        }
                    }
                }
            }

            $firstCriteriaCounter++;
        }

        return array($dataSet, $idsArray);
    }

    /**
     * Attaches the titles (third criterion) to the dataSet array
     * @param $dataSet
     * @param $pos
     * @param $firstCriteriaCounter
     * @param $keyFirstCriteriaItem
     * @param $secondCriteriaItemField
     * @param $secondCriteriaCounter
     * @param $keySecondCriteriaItem
     * @param $keySecondCriteriaItemField
     * @param $thirdCriteriaArray
     * @param $idsArray
     * @return mixed $dataSet
     */
    private static function setTitlesToDataSet($dataSet, $pos,
                                               $firstCriteriaCounter, $keyFirstCriteriaItem,
                                               $secondCriteriaItemField, $secondCriteriaCounter,
                                               $keySecondCriteriaItem, $keySecondCriteriaItemField,
                                               $thirdCriteriaArray, $idsArray)
    {
        //if $pos = null -> set secondCriteria
        if ($pos !== null) {
            $secondCriteriaCounter = $pos;
        } else {
            $dataSet['children'][$firstCriteriaCounter]['children'][] = [
                'name' => $secondCriteriaItemField
            ];
        }

        //foreach array of titles
        foreach ($thirdCriteriaArray as $keyThirdCriteriaItem => $thirdCriteriaItem) {
            if ($keyThirdCriteriaItem == $keySecondCriteriaItem) {
                $thirdCriteriaItemCounter = 0;

                //foreach title
                foreach ($thirdCriteriaItem as $keyThirdCriteriaItemField => $thirdCriteriaItemField) {

                    //if key title = key publisher
                    if ($keyThirdCriteriaItemField == $keySecondCriteriaItemField) {
                        $dataSet['children'][$firstCriteriaCounter]['children'][$secondCriteriaCounter]['children'][] = [
                            'name' => $thirdCriteriaItemField,
                            'value' => 1,
                            'ids' => $idsArray[$keyFirstCriteriaItem][$thirdCriteriaItemCounter]
                        ];
                    }

                    $thirdCriteriaItemCounter++;
                }
            }
        }

        return $dataSet;
    }

    /**
     * Sets every item that is empty to 'Unknown ...'
     * @param $data
     * @param $firstCriteria
     * @param $secondCriteria
     * @return mixed $data
     */
    private static function setEmptyElementsToUnknown($data, $firstCriteria, $secondCriteria)
    {
        $firstDataCounter = 0;

        //Check if fistCriterion item is empty than set 'Unknown ...'
        foreach ($data as $dataRow) {
            if ($dataRow[$firstCriteria] == "") {
                $data[$firstDataCounter][$firstCriteria] = 'Unknown ' . substr($firstCriteria, 3);
            }

            $SecondDataCounter = 0;

            //Check if secondCriterion item is empty than set 'Unknown ...'
            foreach ($dataRow[$secondCriteria] as $secondCriteriaItem) {
                if ($secondCriteriaItem == "") {
                    $data[$firstDataCounter][$secondCriteria][$SecondDataCounter] = 'Unknown ' . substr($secondCriteria, 3);
                }

                $SecondDataCounter++;
            }

            $firstDataCounter++;
        }

        return $data;
    }

    /**
     * Sorts the titles for every secondCriterion
     * @param $data
     * @param $secondCriteria
     * @return mixed $data
     */
    private static function sortDataOnTitle($data, $secondCriteria)
    {
        $dataSetRowCounter = 0;

        foreach ($data as $dataSetRow) {

            $arrayToSortOn = RelationsController::sortArrayOnSpecialCharts($dataSetRow['titles']);

            foreach (array_keys($arrayToSortOn) as $arrayToSortOnKey) {
                $sortedArraySecondCriteria[$dataSetRowCounter][] = $dataSetRow[$secondCriteria][$arrayToSortOnKey];
                $sortedArrayTitles[$dataSetRowCounter][] = $dataSetRow['titles'][$arrayToSortOnKey];
                $sortedArrayIds[$dataSetRowCounter][] = $dataSetRow['ids'][$arrayToSortOnKey];
            }

            $data[$dataSetRowCounter][$secondCriteria] = $sortedArraySecondCriteria[$dataSetRowCounter];
            $data[$dataSetRowCounter]['titles'] = $sortedArrayTitles[$dataSetRowCounter];
            $data[$dataSetRowCounter]['ids'] = $sortedArrayIds[$dataSetRowCounter];

            $dataSetRowCounter++;
        }

        return $data;
    }

    /**
     * Sorts the titles according to special characters
     * @param $arrayToSort
     * @return mixed $arrayToSort
     */
    private static function sortArrayOnSpecialCharts($arrayToSort)
    {
        $collator = new \Collator('en_US');
        $collator->asort($arrayToSort);

        return $arrayToSort;
    }
}
