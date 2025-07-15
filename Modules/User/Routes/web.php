<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\RolesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'auth'], function () {

    //User Profile
    Route::get('/user/profile', 'ProfileController@edit')->name('profile.edit');
    Route::patch('/user/profile', 'ProfileController@update')->name('profile.update');
    Route::patch('/user/password', 'ProfileController@updatePassword')->name('profile.update.password');

    //Users
    Route::resource('users', 'UsersController')->except('show');

    //Roles
    Route::resource('roles', 'RolesController')->except('show');

});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('roles/create-owner', [RolesController::class, 'createOwnerRole'])->name('roles.create-owner');
});
