<?php

namespace Yarm\Visualizations\Http\Controllers;;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PaginationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SQLRelated\AddWhereController;
use App\Http\Controllers\SQLRelated\JoinController;
use App\Http\Controllers\SQLRelated\SortController;
use App\Http\Controllers\ValidationController;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class VisualisationsSearchController extends Controller
{
    /**
     * Launches the search blade
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function searchForm(Request $request)
    {
        //check if user is verified
        if (auth()->user() && !auth()->user()->hasVerifiedEmail()) return redirect('email/verify');

        //Hide layout when user is Typo3DLBT
        ValidationController::checkIfUserIsTypo3DLBT($request);

        //Set data for formSearch
        $data = [
            'show_records_value' => 'all',
            'show_own' => 'false',
            'parent_items' => 'false',
            'child_items' => 'false',
            'search_in_value' => 'refs',
            'owner_value' => 'all',
            'library_value' => 'both',
            'showas_value' => 'bibliography',
            'pagination_value' => '5',

            'selector' => [
                'selvalues' => [
                    'field_value' => ['0' => 'title'],
                    'criterium_value' => ['0' => 'like'],
                    'search_value' => ['0' => '']
                ]
            ],
            'submit_action' =>  'showChart' . $request['form'],

            ];

        //Build dataOptions for formSearch
        $dataOptions = $this->buildDataOptions($request['form'], null, $request['chartType']);

        $data = array_merge($data, $dataOptions);

        return view('visualizations::search.search_visualisations')
            ->with(SearchController::addDropDownData($data));
    }

    /**
     * Launches the search blade after user clicks on 'Change chart'
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function searchFormRefine(Request $request)
    {
        if ($request->session()->get('searchKeys')) {
            $searchInValue = $request->session()->get('searchKeys')['search_in'];

            //build the select_values
            $select_values = $this->buildSelectValues($request);

            //Todo Owner value set variable for owner not used yet
            if (isset($request->session()->get('searchKeys')['owner'])) {
                $ownervalue = $request->session()->get('searchKeys')['owner'];
            } else {
                $ownervalue = '';
            }

            $requestFieldsArray = preg_grep('/^field.*/', array_keys($request->session()->get('searchKeys')));
            $i = 0;

            foreach ($requestFieldsArray as $field) {
                //omit not counted field!
                if ($field !== 'field') {
                    $selector[$i] = [
                        'field_value' => $request->session()->get('searchKeys')[$field],
                        'criterium_value' => $request->session()->get('searchKeys')['criterium' . $i],
                        'search_value' => $request->session()->get('searchKeys')['search' . $i],
                    ];
                    $i++;
                }
            }

            //Check if show_records is in searchKeys
            if (isset($request->session()->get('searchKeys')['show_records']))
                $showRecordsValue = $request->session()->get('searchKeys')['show_records'];
            else
                $showRecordsValue = 'all';

            //Check if show_own is in searchKeys
            if (isset($request->session()->get('searchKeys')['show_own']) && $request->session()->get('searchKeys')['show_own'] == '1') {
                $showOwnValue = 'true';
            } else {
                $showOwnValue = 'false';
            }

            //Check if parent_items is in searchKeys
            if (isset($request->session()->get('searchKeys')['parent_items']) && $request->session()->get('searchKeys')['parent_items'] == '1') {
                $parentItemsValue = 'true';
            } else {
                $parentItemsValue = 'false';
            }

            //Check if parent_items is in searchKeys
            if (isset($request->session()->get('searchKeys')['child_items']) && $request->session()->get('searchKeys')['child_items'] == '1') {
                $childItemsValue = 'true';
            } else {
                $childItemsValue = 'false';
            }

            $libraryvalue = $request->session()->get('searchKeys')['library'];
            $showasvalue = $request->session()->get('searchKeys')['showas'];
        } else {
            $showRecordsValue = 'all';
            $showOwnValue = 'false';
            $parentItemsValue = 'false';
            $childItemsValue = 'false';
            $searchInValue = 'refs';
            $ownervalue = 'all';
            $libraryvalue = 'both';
            $showasvalue = 'bibliography';

            $selector[0] = [
                'field_value' => 'title',
                'criterium_value' => 'like',
                'search_value' => '',
            ];
        }

        //build dataOptions for formSearch
        list($search_form_title, $add_chart, $optionsSelectorData) = $this->buildDataOptions($request['form'], $select_values);

        if (!isset($optionsSelectorData)) {
            $optionsSelectorData = '';
        }

        //add variables to data for formSearch
        $data = [
            'show_records_value' => $showRecordsValue,
            'show_own' => $showOwnValue,
            'parent_items' => $parentItemsValue,
            'child_items' => $childItemsValue,
            'search_in_value' => $searchInValue,
            'owner_value' => $ownervalue,
            'library_value' => $libraryvalue,
            'showas_value' => $showasvalue,
            'selector' => $selector,
            'submit_action' => 'showChart' . $request['form'],
            'search_form_title' => $search_form_title,
            'add_chart' => $add_chart,
            'optionsSelector' => $optionsSelectorData,
        ];

        return view('visualizations::search.search_visualisations')
            ->with(SearchController::addDropDownData($data));
    }

    /**
     * Builds the options for dropdown's
     * @return mixed $data
     */
    private static function buildDataOptionLists()
    {
        $data['sort_criteria'] = [
            'actor' => trans('Actor', [], Session::get('userLanguage')),
            'author' => trans('Author', [], Session::get('userLanguage')),
            'edition' => trans('Edition', [], Session::get('userLanguage')),
            'editor' => trans('Editor', [], Session::get('userLanguage')),
            'language_source' => trans('Language (source)', [], Session::get('userLanguage')),
            'language_target' => trans('Language (target)', [], Session::get('userLanguage')),
            'place' => trans('Place', [], Session::get('userLanguage')),
            'publisher' => trans('Publisher', [], Session::get('userLanguage')),
            'title' => trans('Title', [], Session::get('userLanguage')),
            'translator' => trans('Translator', [], Session::get('userLanguage')),
            'type' => trans('Type', [], Session::get('userLanguage')),
            'year' => trans('Year', [], Session::get('userLanguage'))
        ];

        $data['statistics_formats'] = [
            'all' => trans('All', [], Session::get('userLanguage')),
            '5' => 'Top 5',
            '10' => 'Top 10',
            '20' => 'Top 20',
            '30' => 'Top 30',
            '50' => 'Top 50',
            '70' => 'Top 70',
            '100' => 'Top 100'
        ];

        $data['statistics_types'] = [
            'area' => trans('Area', [], Session::get('userLanguage')),
            'bar' => trans('Bar', [], Session::get('userLanguage')),
            'doughnut' => trans('Doughnut', [], Session::get('userLanguage')),
            'funnel' => trans('Funnel', [], Session::get('userLanguage')),
            'line' => trans('Line', [], Session::get('userLanguage')),
            'line-bar' => trans('Line-Bar', [], Session::get('userLanguage')),
            'pie' => trans('Pie', [], Session::get('userLanguage'))
        ];

        $data['relations_types'] = [
            'linear' => trans('Linear', [], Session::get('userLanguage')),
            'radial' => trans('Radial', [], Session::get('userLanguage')),
            'sunburst' => trans('Sunburst', [], Session::get('userLanguage'))
        ];

        $data['relations_tree_depths'] = [
            '1' => '1',
            '2' => '2',
            '3' => '3'
        ];

        $data['history_sort_criteria'] = [
            'title' => trans('Title', [], Session::get('userLanguage')),
            'language_target' => trans('Language (target)', [], Session::get('userLanguage'))
        ];

        $data['history_target_types'] = [
            'child_adapt_id' => trans('Adaptations', [], Session::get('userLanguage')),
            'child_pub_id' => trans('New editions', [], Session::get('userLanguage')),
            'reprints' => trans('Reprints', [], Session::get('userLanguage')),
            'child_tr_id' => trans('Translations', [], Session::get('userLanguage'))
        ];

        $data['timeline_color_schemes'] = [
            'rainbow' => trans('Rainbow', [], Session::get('userLanguage')),
            'blue' => trans('Blue - Purple', [], Session::get('userLanguage')),
            'green' => trans('Green - Blue', [], Session::get('userLanguage')),
            'red' => trans('Red - Yellow', [], Session::get('userLanguage'))
        ];

        $data['timeline_line_edges'] = [
            'sharp' => trans('Sharp', [], Session::get('userLanguage')),
            'smooth' => trans('Smooth', [], Session::get('userLanguage'))
        ];
        $data['mapcities_color_schemes'] = [
            'classic' => trans('Classic', [], Session::get('userLanguage')),
            'futuristic' => trans('Futuristic', [], Session::get('userLanguage')),
            'modern' => trans('Modern', [], Session::get('userLanguage')),
            'neon' => trans('Neon', [], Session::get('userLanguage')),
            'vintage' => trans('Vintage', [], Session::get('userLanguage'))
        ];
        $data['mapcities_symbol_types'] = [
            'circle' => trans('Circle', [], Session::get('userLanguage')),
            'diamond' => trans('Diamond', [], Session::get('userLanguage')),
            'pin' => trans('Pin', [], Session::get('userLanguage')),
            'round_rectangle' => trans('Round Rectangle', [], Session::get('userLanguage')),
            'triangle' => trans('Triangle', [], Session::get('userLanguage'))
        ];
        $data['mapcities_symbol_data_types'] = [
            'both' => trans('Both', [], Session::get('userLanguage')),
            'empty_symbols' => trans('Empty Symbols', [], Session::get('userLanguage')),
            'only_values' => trans('Only Values', [], Session::get('userLanguage')),
            'only_cities' => trans('Only Cities', [], Session::get('userLanguage'))
        ];

        $data['mapcountries_color_schemes'] = [
            'classic' => trans('Classic', [], Session::get('userLanguage')),
            'modern' => trans('Modern', [], Session::get('userLanguage')),
            'neon' => trans('Neon', [], Session::get('userLanguage')),
            'vintage' => trans('Vintage', [], Session::get('userLanguage'))
        ];

        $data['mapglobe_symbol_data_types'] = [
            'both' => trans('Both', [], Session::get('userLanguage')),
            'empty_symbols' => trans('Empty Symbols', [], Session::get('userLanguage')),
            'only_cities' => trans('Only Cities', [], Session::get('userLanguage')),
            'only_values' => trans('Only Values', [], Session::get('userLanguage'))
        ];

        return $data;
    }

    //choose chartType
    private static function cType($cType)
    {
        if ($cType != null)
            $chartType = $cType;
        else
            $chartType = 'bar';
        return $chartType;
    }

    private static function buildArrayDataOptions($form, $optionLists, $chartType, $criterion = null, $top, $treeDepth)
    {

        switch (ucfirst($form)) {
            case 'Statistics':
                if ($criterion == null)
                    $criterion = 'title';
                $dataOptions = [
                    'search_form_title' => 'Search for statistics',
                    'add_chart' => true,
                    'optionsSelector' => [
                        '0' => [
                            'name_value' => 'statistics_sort_criterion',
                            'label_value' => 'Sort criterion',
                            'select_value' => $criterion,
                            'select_list' => $optionLists['sort_criteria']
                        ],
                        '1' => [
                            'name_value' => 'statistics_format',
                            'label_value' => 'Data format',
                            'select_value' => $top,
                            'select_list' => $optionLists['statistics_formats']
                        ],
                        '2' => [
                            'name_value' => 'statistics_type',
                            'label_value' => 'Chart type',
                            'select_value' => $chartType,
                            'select_list' => $optionLists['statistics_types']
                        ]
                    ]
                ];
                break;
            case 'Relations':
                if ($criterion == null)
                    $criterion = 'translator';
                $dataOptions = [
                    'search_form_title' => 'Search for relations',
                    'add_chart' => false,
                    'optionsSelector' => [
                        '0' => [
                            'name_value' => 'relations_first_criteria',
                            'label_value' => 'First criterion',
                            'select_value' => $criterion,
                            'select_list' => $optionLists['sort_criteria']
                        ],
                        '1' => [
                            'name_value' => 'relations_second_criteria',
                            'label_value' => 'Second criterion',
                            'select_value' => 'publisher',
                            'select_list' => $optionLists['sort_criteria']
                        ],
                        '2' => [
                            'name_value' => 'relations_tree_depth',
                            'label_value' => 'Initial tree depth',
                            'select_value' => $treeDepth,
                            'select_list' => $optionLists['relations_tree_depths']
                        ],
                        '3' => [
                            'name_value' => 'relations_type',
                            'label_value' => 'Chart type',
                            'select_value' => 'linear',
                            'select_list' => $optionLists['relations_types']
                        ]
                    ]
                ];
                break;
            case 'History':
                if ($criterion == null)
                    $criterion = 'title';
                $dataOptions = [
                    'search_form_title' => 'Search for translations and publications history',
                    'add_chart' => true,
                    'optionsSelector' => [
                        '0' => [
                            'name_value' => 'history_sort_criterion',
                            'label_value' => 'Source',
                            'select_value' => $criterion,
                            'select_list' => $optionLists['history_sort_criteria']
                        ],
                        '1' => [
                            'name_value' => 'history_target_type',
                            'label_value' => 'Target type',
                            'select_value' => 'child_tr_id',
                            'select_list' => $optionLists['history_target_types']
                        ]
                    ]
                ];
                break;
            case 'TimeLine':
                if ($criterion == null)
                    $criterion = 'rainbow';
                $dataOptions = [
                    'search_form_title' => 'Search for timeline',
                    'add_chart' => true,
                    'optionsSelector' => [
                        '0' => [
                            'name_value' => 'timeline_color_scheme',
                            'label_value' => 'Color scheme',
                            'select_value' => $criterion,
                            'select_list' => $optionLists['timeline_color_schemes']
                        ],
                        '1' => [
                            'name_value' => 'timeline_line_edges',
                            'label_value' => 'Line edges',
                            'select_value' => 'sharp',
                            'select_list' => $optionLists['timeline_line_edges']
                        ]
                    ]
                ];
                break;
            case 'MapCities':
                if ($criterion == null)
                    $criterion = 'classic';
                $dataOptions = [
                    'search_form_title' => 'Search for items sorted by cities',
                    'add_chart' => true,
                    'optionsSelector' => [
                        '0' => [
                            'name_value' => 'mapcities_color_scheme',
                            'label_value' => 'Map Theme',
                            'select_value' => $criterion,
                            'select_list' => $optionLists['mapcities_color_schemes']
                        ],
                        '1' => [
                            'name_value' => 'mapcities_symbol_type',
                            'label_value' => 'Symbol Type',
                            'select_value' => 'circle',
                            'select_list' => $optionLists['mapcities_symbol_types']
                        ],
                        '2' => [
                            'name_value' => 'mapcities_symbol_data',
                            'label_value' => 'Symbol Data',
                            'select_value' => 'both',
                            'select_list' => $optionLists['mapcities_symbol_data_types']
                        ],
                    ]
                ];
                break;
            case 'MapCountries':
                if ($criterion == null)
                    $criterion = 'classic';
                $dataOptions = [
                    'search_form_title' => 'Search for items sorted by countries',
                    'add_chart' => true,
                    'optionsSelector' => [
                        '0' => [
                            'name_value' => 'mapcountries_color_scheme',
                            'label_value' => 'Map Theme',
                            'select_value' => $criterion,
                            'select_list' => $optionLists['mapcountries_color_schemes']
                        ],
                    ]
                ];
                break;
            case 'MapGlobe':
                if ($criterion == null)
                    $criterion = 'both';
                $dataOptions = [
                    'search_form_title' => 'Search for items on 3D globe',
                    'add_chart' => true,
                    'optionsSelector' => [
                        '0' => [
                            'name_value' => 'mapglobe_symbol_data',
                            'label_value' => 'Symbol Data',
                            'select_value' => $criterion,
                            'select_list' => $optionLists['mapglobe_symbol_data_types']
                        ],
                    ]
                ];
                break;
        }
        return $dataOptions;
    }

    /**
     * Builds the options for each visualisation type
     * @param $form
     * @param $select_values
     * @return array
     */
    private static function buildDataOptions($form, $select_values, $cType = null, $criterion = null, $top = null, $treeDepth = null)
    {
        //choose type of visualisation
        $chartType = VisualisationsSearchController::cType($cType);

        //build selectBox lists for options charts
        $optionLists = self::buildDataOptionLists();

        $dataOptions = self::buildArrayDataOptions($form, $optionLists, $chartType, $criterion,$top, $treeDepth);

        //Check and set changed options for Refine
        if (isset($select_values)) {
            $search_form_title = $dataOptions['search_form_title'];
            $add_chart = $dataOptions['add_chart'];

            $dataOptionCounter = 0;
            foreach ($select_values as $select_value) {
                if ($select_value !== false && $select_value !== true) {
                    $dataOptions['optionsSelector'][$dataOptionCounter]['select_value'] = $select_value;
                    $dataOptionCounter++;
                }
            }

            return array($search_form_title, $add_chart, $dataOptions['optionsSelector']);
        }

        return $dataOptions;
    }

    /**
     * Builds the options for the result view
     * @param $form
     * @param $chartCriteria
     * @param $resultCriteria
     * @return array
     */
    public static function buildDataOptionsForResultView($form, $chartCriteria, $resultCriteria)
    {
        //build optionsLists to fill selectBoxes
        $optionsLists = VisualisationsSearchController::buildDataOptionLists();

        foreach ($chartCriteria as $chartCriterion) {
            $select_values[] = $chartCriterion;
        }

        //build dataOptions to build selectBoxes
        list($search_form_title, $add_chart, $optionsSelectorData) = VisualisationsSearchController::buildDataOptions($form, $select_values);
        $optionsSelector['optionsSelector'] = $optionsSelectorData;

        //Merge optionsList with optionsSelector
        $data = array_merge($optionsLists, $optionsSelector);

        //Merge data with resultCriteria
        $data = array_merge($data, $resultCriteria);

        return $data;
    }

    /**
     * Builds the select values
     * @param $request
     * @return array
     */
    private function buildSelectValues($request)
    {
        $chartCriteria = $request->session()->get('chartCriteria');

        //foreach chartCriterion set select_value
        foreach ($chartCriteria as $chartCriterion) {
            $select_values[] = $chartCriterion;
        }

        return $select_values;
    }

    /**
     * Determines which options are selected
     * @param $form
     * @param $request
     * @return array
     */
    public static function checkDataOptionsValues($form, $request)
    {
        $dataOptions = VisualisationsSearchController::buildDataOptions($form, null);
        $firstCriterionNew = $dataOptions['optionsSelector'][0]['name_value'] . '_new';

        //Options for checkboxes
        if (isset($request['dataOptionsCheckboxes'])) {
            $dataOptionsCheckboxes = $request['dataOptionsCheckboxes'];
        }

        //Check if criterionName_new exists
        if ($request->$firstCriterionNew) {
            //Foreach new value
            foreach ($dataOptions['optionsSelector'] as $chartCriterion) {
                $criterionNew = $chartCriterion['name_value'] . '_new';
                $chartCriteria[$chartCriterion['name_value']] = $request->$criterionNew;
            }

            //Foreach new value for checkboxes
            if (isset($request['dataOptionsCheckboxes'])) {
                foreach ($dataOptionsCheckboxes as $chartCriterion) {
                    $criterionNew = $chartCriterion . '_new';

                    if ($request->$criterionNew == "true") {
                        $chartCriteria[$chartCriterion] = true;
                    } else {
                        $chartCriteria[$chartCriterion] = false;
                    }
                }
            }

            $request = VisualisationsSearchController::fillRequest($request, 'searchKeys');
        } else {
            //Foreach new value
            foreach ($dataOptions['optionsSelector'] as $chartCriterion) {
                $criterion = $chartCriterion['name_value'];
                $chartCriteria[$chartCriterion['name_value']] = $request->$criterion;
            }

            //Foreach new value for checkboxes
            if (isset($request['dataOptionsCheckboxes'])) {
                foreach ($dataOptionsCheckboxes as $chartCriterion) {
                    $chartCriteria[$chartCriterion] = false;
                }
            }
        }

        //Put chartCriteria in session
        $request->session()->put('chartCriteria', $chartCriteria);

        return array($chartCriteria, $request);
    }

    /**
     * Builds the search criteria
     * @param $request
     * @return string
     */
    private function buildCriteriaCitationView($request)
    {
        $criteria = "<span class='font-weight-bold'>" . trans('Criteria:', [], Session::get('userLanguage')) . "</span> &nbsp;" . trans('you selected data for', [], Session::get('userLanguage')) . "<span class='font-weight-bold'>" . $request['listData'] . "</span>";
        return $criteria;
    }

    /**
     * Refactors the request to build the dataSet
     * @param $request
     * @param $counter
     * @param $type
     * @return mixed $request
     */
    public static function buildRequest4dataset($request, $counter, $type)
    {
        //save original request to session
        $request->session()->put('searchKeys', $request->all());
        $request4dataset = $request->session()->get('searchKeys');

        //reduce request for search data for singular dataset
        $request4dataset['field0'] = $request['field' . $counter];
        $request4dataset['criterium0'] = $request['criterium' . $counter];
        $request4dataset['search0'] = $request['search' . $counter];

        if ($type == 'map') {
            //Add place != '' for maps
            array_push($request4dataset['field0'], 'place');
            array_push($request4dataset['criterium0'], '!=');
            array_push($request4dataset['search0'], '');
        }

        $request->merge($request4dataset);
        return $request;
    }

    /**
     * Builds the data according to the search criteria
     * @param Request $request
     * @param $sortCriterion
     * @return array
     */
    public static function search(Request $request, $sortCriterion)
    {

        $roles = Role::roles();

        $queryLib = SearchController::setQueryLib($request);

        //only search in ElasticSearch if asked for!

        if ($request['search_in'] == 'text' or $request['search_in'] == 'refstext') {
            $idsEsArray = SearchController::searchInElasticSearch($request);
            if (!is_array($idsEsArray) && $idsEsArray != false ) {
                return redirect()->back()->with('alert-danger', $idsEsArray);
            }
        }
        else {
            $idsEsArray = false;
        }
        //Set restrictedSearchByParentsChildren false
        $restrictedSearchByParentsChildren = false;

        //Restrict data according to 'parent_items' or 'child_items'
        if (isset($request['parent_items']) || isset($request['child_items'])) {
            $restrictedSearchByParentsChildren = AddWhereController::restrictSearchByParentsOrChildren($request);
        }

        $query =  JoinController::buildJoints($roles, $request);
        $query = AddWhereController::addWhereClauses($request, $idsEsArray, $query, $request['search_in'], $queryLib, $roles, $restrictedSearchByParentsChildren);
        $query = SortController::addSortCriteria($query, $request, 'search', $sortCriterion);

        //Todo add here (and by show)? user(-group) restrictions!
        if (count($query->getQuery()->wheres) == 0) {
            session(['listViewDataCounter' => 0]);
            $data = [];
            $data['rows'] = $query->find(0);
        } else {
            $data = [];
            $data['rows'] = $query->get();
            session(['listViewDataCounter' => count($data['rows'])]);
        }

        return $data;
    }

    /**
     * Fills the request with the old searchKeys
     * @param $request
     * @param $key
     * @return mixed $request
     */
    public static function fillRequest($request, $key)
    {
        $oldRequest = $request->session()->get($key);

        $keyArray = array_keys($oldRequest);

        $counter = 0;

        foreach ($oldRequest as $req) {
            $request[$keyArray[$counter]] = $req;
            $counter++;
        }

        return $request;
    }

    /**
     * @param $request
     * @return mixed $request
     */
    public static function addStandardValuesToRequest($request)
    {

        //Todo use env (f.i. yarm_sortorder) for standard sortorder

        $request['search_in'] = 'dataIds';
        $request['select'] = 'all';
        $request['library'] = 'both';
        $request['showas'] = 'bibliography';
        $request['field0'] = ['0' => 'title'];
        $request['criterium0'] = ['0' => 'like'];
        $request['search0'] = ['0' => 'null'];
        $request['sort1'] = "author";
        $request['sort2'] = "title";
        $request['sort3'] = "container";
        $request['sort4'] = "year";
        $request['sort5'] = "edition";
        $request['desc1'] = "asc";
        $request['desc2'] = "asc";
        $request['desc3'] = "asc";
        $request['desc4'] = "asc";
        $request['desc5'] = "asc";
        $request['pagination'] = PaginationController::getPaginationItemCount();
        return $request;
    }

    /**
     * @param $request
     * @param $dataChart
     * @return array|bool|false|string[]
     */
    public static function makeIdsArray($request, $dataChart)
    {

        $idsArray = [];
        if (isset($dataChart)) {
            if (isset($request->dataIds)) {
                $idsArray = explode(',', $request->dataIds);
            } else {
                try {
                    $idsArray = ($dataChart[$request->seriesIndex]['ids'][$request->dataIndex]);

                    if (!is_array($idsArray)) {
                        $idsArray = array($idsArray);
                    }
                } catch (\Throwable $e) {
                    //Todo Lang
                    return false;
                }
            }
        }
        return $idsArray;
    }

    /**
     * Builds data for onClick chart
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function searchSelectedDataOnclick(Request $request)
    {
        $request->session()->push('searchKeys.showDataRequest', $request->all());

        /*error_log("1 CLICK CLICK CLICK");
        error_log(var_export(session()->all(), true));
        error_log("2 CLICK CLICK CLICK");

        if (isset($request['chartId']) && !empty($request['chartId']))
            $dataChart = $request->session()->get('chartData_' . $request['chartId']);
        else
            $dataChart = $request->session()->get('chartData');*/

        $dataChart = $request->session()->get('chartData');

        $idsArray = self::makeIdsArray($request, $dataChart);
        if ($idsArray == false || $idsArray == 0) {
            //Todo Lang
            return back()->with('alert-danger', 'No search result!');
        }

        $request = self::addStandardValuesToRequest($request);

        $roles = Role::roles();
        $queryLib = SearchController::setQueryLib($request);

        $query = JoinController::buildJoints($roles, $request);
        $query = AddWhereController::addWhereClauses($request, $idsArray, $query, $request['search_in'], $queryLib, $roles, false);
        $query = SortController::addSortCriteria($query, $request, 'search', '');


        //Todo add here (and by show)? user(-group) restrictions!
        if (count($query->getQuery()->wheres) == 0) {
            session(['listViewDataCounter' => 0]);
            $data = [];
            $data['rows'] = $query->find(0);
        } else {
            $data = [];
            $data['rows'] = $query->paginate($request['pagination']);
            session(['listViewDataCounter' => $data['rows']->total()]);
        }

        $criteria = $this->buildCriteriaCitationView($request);
        $data['search_info'] = SearchController::buildSearchInfo($request, $criteria);
        $data['searchKeys'] = $request->session()->get('searchKeys');

        return ExportController::reformatBladeExport(view('dlbt.citation.citationView', $data)->render());
    }

    private static function setFormAndType($requestAttributes)
    {

        //Valid forms and array the user can choose
        $validFormsArray = ['Statistics', 'Relations', 'History', 'TimeLine', 'MapCities', 'MapCountries', 'MapGlobe'];

        //Determine which form and type to use
        foreach ($requestAttributes as $requestAttribute) {
            $attribute = explode('=', $requestAttribute);

            //Set $form with first letter capital
            if ($attribute[0] == 'form') {
                $form = ucfirst($attribute[1]);
            }

            //Set $type with all lower caps
            if ($attribute[0] == 'type') {
                $type = strtolower($attribute[1]);
            }
        }

        //Set standard value to form
        if (!isset($form))
            $form = 'Statistics';

        switch ($form) {
            case 'Timeline':
                $form = 'TimeLine';
                break;
            case 'Mapcities':
                $form = 'MapCities';
                break;
            case 'Mapcountries':
                $form = 'MapCountries';
                break;
            case 'Mapglobe':
                $form = 'MapGlobe';
                break;
        }

        //Check if form exists in validFormsArray
        if (array_search($form, $validFormsArray) == false)
            $form = 'Statistics';

        return array($form, $type);

    }

    private static function buildChartCriteria($dataOptions, $type)
    {

        $validTypesArray = ['area', 'bar', 'doughnut', 'funnel', 'line', 'line-bar', 'pie', 'linear', 'radial', 'sunburst'];

        foreach ($dataOptions['optionsSelector'] as $chartCriterion) {
            if (isset($type)) {
                //Set type if there is one and type exists in validTypesArray
                if (strpos($chartCriterion['name_value'], 'type') !== false && array_search($type, $validTypesArray) !== false) {
                    $chartCriteria[$chartCriterion['name_value']] = $type;
                } else {
                    $chartCriteria[$chartCriterion['name_value']] = $chartCriterion['select_value'];
                }
            } else {
                $chartCriteria[$chartCriterion['name_value']] = $chartCriterion['select_value'];
            }
        }
        return $chartCriteria;
    }

    private static function buildSearchAttributes($chartCriteria)
    {
        //Standard search criteria
        $searchCriteria = [
            'search_in' => 'refs',
            'select' => 'all',
            'library' => 'both',
            'showas' => 'bibliography',
            'dataSetCounter' => '2',
            'SearchForm' => 'Search'
        ];

        $searchAttributes = array_merge($searchCriteria, $chartCriteria);

        return $searchAttributes;

    }

    /**
     * API Chart
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function buildCustomChart(Request $request)
    {
        //Request url
        $requestUri = \Request::getRequestUri();

        try {
            //Make separate attributes from url
            $requestAttributes = explode('&', urldecode(explode('?', $requestUri)[1]));
        } catch (\Throwable $e) {
            //Redirect when something went wrong
            $error_message['error_message'] = 'ERROR: Missing attributes in the url.';
            return view('errors.api_error')->with($error_message);
        }


        //set sortCriterion
        if (!isset($request['criterion']))
            $Criterion = null;
        else
            $Criterion = $request['criterion'];

        if (!isset($request['top']))
            $top = '5';
        else
            $top = $request['top'];

        if (!isset($request['treeDepth']))
            $treeDepth = '2';
        else
            $treeDepth = $request['treeDepth'];




        list($form, $type) = VisualisationsSearchController::setFormAndType($requestAttributes);

        //Build data options depending on what form
        $dataOptions = VisualisationsSearchController::buildDataOptions($form, null, null, $Criterion, $top, $treeDepth);

        //Build chart criteria
        $chartCriteria = VisualisationsSearchController::buildChartCriteria($dataOptions, $type);

        //Build searchAttributes
        $searchAttributes = self::buildSearchAttributes($chartCriteria);

        //Fill searchAttributes with requestAttributes
        foreach ($requestAttributes as $requestAttribute) {
            if (strpos($requestAttribute, '=') !== false) {
                $attribute = explode('=', $requestAttribute);

                //Check if attribute[0] is field, criterium or search
                if (strpos($attribute[0], 'field') !== false
                    || strpos($attribute[0], 'criterium') !== false
                    || strpos($attribute[0], 'search') !== false) {
                    $attributeArray[] = $attribute[1];

                    //Check if attribute[0] contains a number
                    if (preg_match('/[0-9]/', $attribute[0])) {
                        $searchAttributes[$attribute[0]][] = $attribute[1];
                    } else {
                        //Attach 0 behind attribute and remove attribute from request
                        $searchAttributes[$attribute[0] . '0'][] = $attribute[1];
                        $request->request->remove($attribute[0]);
                    }
                    unset($attributeArray);
                } else {
                    $searchAttributes[$attribute[0]] = $attribute[1];
                }
            }
        }

        //Put searchAttributes in request
        $request->request->add($searchAttributes);

        //Result criteria to determine what to show on the result page
        $request['resultCriteria'] = $this->buildResultCriteria($request);

        try {
            //buildCustomChartView returns view
            $view = $this->buildCustomChartView($form, $request);
        } catch (\Throwable $e) {
            //Redirect when something went wrong
            $error_message['error_message'] = 'ERROR: Wrong attributes in the url.';
            return view('errors.api_error')->with($error_message);
        }

        return $view;
    }

    /**
     * Builds the result criteria
     * @param $request
     * @return |null
     */
    private function buildResultCriteria($request)
    {
        $resultCriteria = null;

        //Show or hide layout
        if (isset($request['hideLayout']) && $request['hideLayout'] == 'true') {
            $request->session()->put('hideLayout', true);
        } else {
            $request->session()->remove('hideLayout');
        }

        //Show or hide search result container
        if (isset($request['hideSearchResult']) && $request['hideSearchResult'] == 'true') {
            $resultCriteria['hideSearchResult'] = true;
        }

        //Show or hide data counter
        if (isset($request['hideListViewDataCounter']) && $request['hideListViewDataCounter'] == 'true') {
            $resultCriteria['hideListViewDataCounter'] = true;
        }

        //Show or hide data counter
        if (isset($request['hideMapSlider']) && $request['hideMapSlider'] == 'true') {
            $resultCriteria['hideMapSlider'] = true;
        }

        return $resultCriteria;
    }

    /**
     * Determines which view to show for custom chart
     * @param $form
     * @param $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    private function buildCustomChartView($form, $request)
    {
        switch ($form) {
            case 'History':
                $view = HistoryController::showData($request);
                break;
            case 'Relations':
                $view = RelationsController::showData($request);
                break;
            case 'TimeLine':
                $view = TimeLineController::showData($request);
                break;
            case 'MapCities':
                $view = MapCitiesController::showData($request);
                break;
            case 'MapCountries':
                $view = MapCountriesController::showData($request);
                break;
            case 'MapGlobe':
                $view = MapGlobeController::showData($request);
                break;
            default:
                $view = StatisticsController::showData($request);
                break;
        }

        if ($view == false)
            abort();

        return $view;
    }

    /**
     * Writes the values for the Excel sheet
     * @param $dataArray
     * @param $sheet
     * @param $title
     * @param $firstCriteriun
     * @param $secondCriterion
     * @return mixed $sheet
     */
    public static function writeValuesToExcelSheet($dataArray, $sheet, $title, $firstCriteriun, $secondCriterion)
    {
        $valueCounter = 0;
        foreach ($dataArray as $data) {
            $i = 0;
            $cellCounter = 2;
            $sheet[$valueCounter]->setCellValue('A1', $title);
            $sheet[$valueCounter]->setCellValue('B1', 'Value');
            $sheet[$valueCounter]->getStyle('A1')->getFont()->setBold(true);
            $sheet[$valueCounter]->getStyle('B1')->getFont()->setBold(true);
            foreach ($data[$firstCriteriun] as $value) {
                $sheet[$valueCounter]->setCellValue('A' . $cellCounter, $data[$firstCriteriun][$i]);
                $sheet[$valueCounter]->setCellValue('B' . $cellCounter, $data[$secondCriterion][$i]);
                $cellCounter++;
                $i++;
            }
            $valueCounter++;
        }

        return $sheet;
    }

    /**
     * Prepares the download of the Excel file
     * @param $spreadsheet
     * @param $filename
     */
    public static function downloadExcelFile($spreadsheet, $filename)
    {
        $sheetIndex = $spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet'));
        $spreadsheet->removeSheetByIndex($sheetIndex);

        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');


        $response = response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $timestamp = time();

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"' . $timestamp . '".xlsx"');
        $response->send();
    }
}
