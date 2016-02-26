<?php

Route::group( [ 'middleware' => [ 'web' ], 'namespace' => 'Polyether\Backend\Http\Controllers' ], function () {
    // Authentication routes...
    Route::get( 'login', [ 'as' => 'login', 'uses' => 'Auth\AuthController@getLogin' ] );
    Route::post( 'login', [ 'as' => 'loginPost', 'uses' => 'Auth\AuthController@postLogin' ] );
    Route::get( 'logout', [ 'as' => 'logout', 'uses' => 'Auth\AuthController@getLogout' ] );
    // Registration routes...
    Route::get( 'register', [ 'as' => 'register', 'uses' => 'Auth\AuthController@getRegister' ] );
    Route::post( 'register', [ 'as' => 'registerPost', 'uses' => 'Auth\AuthController@postRegister' ] );
    // Password reset link request routes...
    Route::get( 'password/email', [ 'as' => 'recover_password', 'uses' => 'Auth\PasswordController@getEmail' ] );
    Route::post( 'password/email', [ 'as' => 'recover_passwordPost', 'uses' => 'Auth\PasswordController@postEmail' ] );
    // Password reset routes...
    Route::get( 'password/reset/{token}', [ 'as' => 'reset_password', 'uses' => 'Auth\PasswordController@getReset' ] );
    Route::post( 'password/reset', [ 'as' => 'reset_passwordPost', 'uses' => 'Auth\PasswordController@postReset' ] );

    Route::group( [ 'prefix' => 'dashboard', 'middleware' => 'auth', ], function () {
        Route::get( '/', [ 'as' => 'dashboardHome', 'uses' => 'Backend\HomeController@getIndex' ] );


        Route::group( [ 'prefix' => 'pt' ], function () {

            Route::get( '{post_type}', [ 'as' => 'post_type_home', 'uses' => 'Backend\PostTypeController@getIndex' ] );
            Route::get( 'edit/{post_id}', [ 'as' => 'post_edit', 'uses' => 'Backend\PostTypeController@getEdit' ] );
            Route::put( 'edit/{post_id}', [ 'as' => 'post_editPut', 'uses' => 'Backend\PostTypeController@putEdit' ] );
            Route::get( '{post_type}/new', [ 'as'   => 'post_type_new',
                                             'uses' => 'Backend\PostTypeController@getCreate' ] );
            Route::post( '{post_type}/new', [ 'as'   => 'post_type_newPost',
                                              'uses' => 'Backend\PostTypeController@postCreate' ] );
            Route::post( '{post_type}/datatables_data', [ 'as'   => 'post_type_dataTables_resultPost',
                                                          'uses' => 'Backend\PostTypeController@postGetPostTypeDataTableResult' ] );

        } );
    } );
} );
