<?php

Route::group(['middleware' => ['web'], 'namespace' => 'Polyether\Backend\Http\Controllers'], function () {
    // Authentication routes...
    Route::get('login', ['as' => 'login', 'uses' => 'Auth\AuthController@getLogin']);
    Route::post('login', ['as' => 'loginPost', 'uses' => 'Auth\AuthController@postLogin']);
    Route::get('logout', ['as' => 'logout', 'uses' => 'Auth\AuthController@getLogout']);
    // Registration routes...
    Route::get('register', ['as' => 'register', 'uses' => 'Auth\AuthController@getRegister']);
    Route::post('register', ['as' => 'registerPost', 'uses' => 'Auth\AuthController@postRegister']);
    // Password reset link request routes...
    Route::get('password/email', ['as' => 'recover_password', 'uses' => 'Auth\PasswordController@getEmail']);
    Route::post('password/email', ['as' => 'recover_passwordPost', 'uses' => 'Auth\PasswordController@postEmail']);
    // Password reset routes...
    Route::get('password/reset/{token}', ['as' => 'reset_password', 'uses' => 'Auth\PasswordController@getReset']);
    Route::post('password/reset', ['as' => 'reset_passwordPost', 'uses' => 'Auth\PasswordController@postReset']);

    Route::group(['prefix' => 'dashboard', 'middleware' => 'auth',], function () {
        Route::get('/', ['as' => 'dashboardHome', 'uses' => 'Backend\HomeController@getIndex']);

        Route::get('users', ['as' => 'user_manage', 'uses' => 'Backend\UserController@getIndex']);
        Route::post('user/datatables_data', [
            'as'   => 'user_dataTables_resultPost',
            'uses' => 'Backend\UserController@postGetUserDataTableResult',
        ]);
        Route::post('user/ajax/delete', [
            'as'   => 'user_ajaxDeletePost',
            'uses' => 'Backend\UserController@postAjaxDeleteUser',
        ]);
        Route::get('user/edit/{user_id}', ['as' => 'user_edit', 'uses' => 'Backend\UserController@getEdit']);
        Route::put('user/edit/{user_id}', ['as' => 'user_editPut', 'uses' => 'Backend\UserController@putEdit']);
        Route::get('user/new', [
            'as'   => 'user_create',
            'uses' => 'Backend\UserController@getCreate',
        ]);
        Route::post('user/new', [
            'as'   => 'user_createPost',
            'uses' => 'Backend\UserController@postCreate',
        ]);

        Route::post('taxonomy/ajax/{taxonomy_name}/datatables_data', [
            'as'   => 'taxonomy_datatables_resultPost',
            'uses' => 'Backend\TaxonomyController@postGetTaxonomyDataTableResult',
        ]);
        Route::post('taxonomy/ajax/term/delete', [
            'as'   => 'taxonomy_term_deletePost',
            'uses' => 'Backend\TaxonomyController@postAjaxDeleteTerm',
        ]);
        Route::put('taxonomy/edit/{taxonomy}/{term_id}', [
            'as'   => 'term_taxonomy_editPut',
            'uses' => 'Backend\TaxonomyController@putEditTerm',
        ]);
        Route::get('taxonomy/edit/{taxonomy}/{term_id}', [
            'as'   => 'term_taxonomy_edit',
            'uses' => 'Backend\TaxonomyController@getEditTerm',
        ]);
        Route::get('taxonomy/{taxonomy_name}', [
            'as'   => 'taxonomy_home',
            'uses' => 'Backend\TaxonomyController@getIndex',
        ]);
        Route::post('taxonomy/{taxonomy_name}/new', [
            'as'   => 'taxonomy_term_new',
            'uses' => 'Backend\TaxonomyController@postAddTerm',
        ]);


        Route::post('ajax/{action_name}', [
            'as'   => 'ajax_backend',
            'uses' => function ($actionName) {
                Backend::processAjax($actionName);
            },
        ]);

        Route::group(['prefix' => 'pt'], function () {

            Route::get('{post_type}', ['as' => 'post_type_home', 'uses' => 'Backend\PostTypeController@getIndex']);
            Route::get('edit/{post_id}', ['as' => 'post_edit', 'uses' => 'Backend\PostTypeController@getEdit']);
            Route::put('edit/{post_id}', ['as' => 'post_editPut', 'uses' => 'Backend\PostTypeController@putEdit']);
            Route::get('{post_type}/new', [
                'as'   => 'post_create',
                'uses' => 'Backend\PostTypeController@getCreate',
            ]);
            Route::post('{post_type}/new', [
                'as'   => 'post_createPost',
                'uses' => 'Backend\PostTypeController@postCreate',
            ]);
            Route::post('{post_type}/datatables_data', [
                'as'   => 'post_type_dataTables_resultPost',
                'uses' => 'Backend\PostTypeController@postGetPostTypeDataTableResult',
            ]);
            Route::post('post/ajax/delete', [
                'as'   => 'post_ajaxDeletePost',
                'uses' => 'Backend\PostTypeController@postAjaxDeletePost',
            ]);

        });
    });
});
