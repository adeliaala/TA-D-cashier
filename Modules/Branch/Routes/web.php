<?php

use Illuminate\Support\Facades\Route;
use Modules\Branch\Http\Controllers\BranchController;

Route::middleware(['auth'])->group(function () {
    Route::resource('branch', BranchController::class);
    Route::get('/branch/switch/{branch}', [BranchController::class, 'switchBranch'])->name('branch.switch');
}); 