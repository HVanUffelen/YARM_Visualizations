<?php
Route::group(['namespace'=>'Yarm\Visualizations\Http\Controllers','prefix'=>'dlbt','middleware'=>['web']], function (){


    //Routes for API
    Route::get('/visualisations/buildCustomChart', 'VisualisationsSearchController@buildCustomChart')
        ->name('buildCustomChart');//TODO:: Fix!

    //Routes for all Visualisations
    Route::get('/visualisations/searchForm', 'VisualisationsSearchController@searchForm')
        ->name('VisualisationsSearchForm');
    Route::get('/visualisations/searchFormRefine', 'VisualisationsSearchController@searchFormRefine')
        ->name('searchFormRefine');
    Route::get('/visualisations/selectedDataOnclick', 'VisualisationsSearchController@searchSelectedDataOnclick')
        ->name('selectedDataOnclick');

    //Statistics
    Route::get('/visualisations/showChartStatistics', 'StatisticsController@showData')
        ->name('showChartStatistics');
    Route::get('/visualisations/excelStatistics', 'StatisticsController@excelStatistics')
        ->name('excelStatistics');

    //Relations
    Route::get('/visualisations/showChartRelations', 'RelationsController@showData')
        ->name('showChartRelations');

    //History
    Route::get('/visualisations/showChartHistory', 'HistoryController@showData')
        ->name('showChartHistory');
    Route::get('/visualisations/excelHistory', 'HistoryController@excelHistory')
        ->name('excelHistory');

    //TimeLine
    Route::get('/visualisations/showChartTimeLine', 'TimeLineController@showData')
        ->name('showChartTimeLine');
    Route::get('/visualisations/excelTimeLine', 'TimeLineController@excelTimeLine')
        ->name('excelTimeLine');

    //Map cities
    Route::get('/visualisations/showChartMapCities', 'MapCitiesController@showData')
        ->name('showChartMapCities');
    Route::get('/visualisations/excelMapCities', 'MapCitiesController@excelMapCities')
        ->name('excelMapCities');

    //Map countries
    Route::get('/visualisations/showChartMapCountries', 'MapCountriesController@showData')
        ->name('showChartMapCountries');
    Route::get('/visualisations/excelMapCountries', 'MapCountriesController@excelMapCountries')
        ->name('excelMapCountries');

    //Map using Globe
    Route::get('/visualisations/showChartMapGlobe', 'MapGlobeController@showData')
        ->name('showChartMapGlobe');
    Route::get('/visualisations/excelMapGlobe', 'MapGlobeController@excelMapGlobe')
        ->name('excelMapGlobe');


});


