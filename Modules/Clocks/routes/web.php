<?php

use Illuminate\Support\Facades\Route;
use Modules\Clocks\Http\Controllers\DeductionRuleTemplateController;
use Modules\Clocks\Http\Controllers\RulesPageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([], function () {
});

Route::prefix('clocks')
    ->name('clocks.')
    ->group(function () {
        Route::get('rules', [RulesPageController::class, 'index'])->name('rules.index');
        Route::get('rules/plan/{scope}/{id}', [RulesPageController::class, 'showPlan'])->name('rules.plan.show');
        Route::post('rules/plan/{scope}/{id}', [RulesPageController::class, 'updatePlan'])->name('rules.plan.update');

        Route::get('rules/templates', [DeductionRuleTemplateController::class, 'index'])->name('rules.templates.index');
        Route::post('rules/templates', [DeductionRuleTemplateController::class, 'store'])->name('rules.templates.store');
        Route::get('rules/templates/{template}', [DeductionRuleTemplateController::class, 'show'])->name('rules.templates.show');
        Route::put('rules/templates/{template}', [DeductionRuleTemplateController::class, 'update'])->name('rules.templates.update');
        Route::delete('rules/templates/{template}', [DeductionRuleTemplateController::class, 'destroy'])->name('rules.templates.destroy');
    });
