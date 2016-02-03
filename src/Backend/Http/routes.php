<?php

Route::group(['middleware' => ['web']], function(){
    Route::any('admin', function(){
       return' fff'; 
    });
});
