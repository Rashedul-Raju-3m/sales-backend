<?php

use App\Http\Middleware\HeaderAuthenticationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Production\App\Http\Controllers\ConfigController;
use Modules\Production\App\Http\Controllers\ProductionInhouseController;
use Modules\Production\App\Http\Controllers\ProductionBatchController;
use Modules\Production\App\Http\Controllers\ProductionRecipeController;
use Modules\Production\App\Http\Controllers\ProductionRecipeItemsController;
use Modules\Production\App\Http\Controllers\SettingController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::prefix('/production/select')->middleware([HeaderAuthenticationMiddleware::class])->group(function() {
    Route::get('/setting-type', [SettingController::class,'settingTypeDropdown'])->name('pro_setting_type_dropdown');
    Route::get('/config-dropdown', [ConfigController::class,'configDropdown'])->name('pro_config_dropdown');
    Route::get('/items-dropdown', [ProductionRecipeItemsController::class,'dropdown'])->name('pro_item_dropdown');

});

Route::prefix('/production')->middleware([HeaderAuthenticationMiddleware::class])->group(function() {

    Route::apiResource('setting', SettingController::class)
        ->middleware([HeaderAuthenticationMiddleware::class])
        ->names([
            'index' => 'production.setting.index',
            'store' => 'production.setting.store',
            'show' => 'production.setting.show',
            'update' => 'production.setting.update',
            'destroy' => 'production.setting.destroy'
        ]);
    ;

    Route::get('/config', [ConfigController::class,'show'])->name('pro_config_show');
    Route::patch('/config-update', [ConfigController::class,'update'])->name('pro_config_update');

    Route::post('/inline-update-value-added', [ProductionRecipeItemsController::class,'inlineUpdateValueAdded'])->name('pro_inline_update_value_added');
    Route::post('/inline-update-element-status', [ProductionRecipeItemsController::class,'inlineUpdateElementStatus'])->name('pro_inline_update_element_status');
    Route::post('/recipe-items-process', [ProductionRecipeItemsController::class,'updateProcess'])->name('pro_item_update_process');

    Route::apiResource('recipe-items', ProductionRecipeItemsController::class)
        ->middleware([HeaderAuthenticationMiddleware::class])
        ->names([
            'index' => 'production.items.index',
            'store' => 'production.items.store',
            'show' => 'production.items.show',
            'update' => 'production.items.update',
            'destroy' => 'production.items.destroy'
        ]);
    ;

    Route::get('generate/finish-goods/xlsx', [ProductionRecipeItemsController::class,'finishGoodsXlsxGenerate'])->name('get_finish_goods_xlsx_generate');


    Route::apiResource('recipe', ProductionRecipeController::class)
        ->middleware([HeaderAuthenticationMiddleware::class])
        ->names([
            'index'     => 'production.recipe.index',
            'store'     => 'production.recipe.store',
            'show'      => 'production.recipe.show',
            'update'    => 'production.recipe.update',
            'destroy'   => 'production.recipe.destroy'
        ]);
    ;


    Route::apiResource('batch', ProductionBatchController::class)
        ->middleware([HeaderAuthenticationMiddleware::class])
        ->names([
            'index'     => 'production.batch.index',
            'store'     => 'production.batch.store',
            'show'      => 'production.batch.show',
            'update'    => 'production.batch.update',
            'destroy'   => 'production.batch.destroy'
        ]);
    ;
    Route::post('/batch/create-batch-item', [ProductionBatchController::class,'insertBatchItem'])->name('production_insert_batch_item');
    Route::post('/batch/item/inline-quantity-update', [ProductionBatchController::class,'batchItemQuantityInlineUpdate'])->name('production_batch_item_quantity_update');
    Route::post('/batch/approve/{id}', [ProductionBatchController::class,'batchApproved'])->name('production_batch_approve');
    Route::post('/batch/confirm-receive/{id}', [ProductionBatchController::class,'batchConfirmReceive'])->name('production_batch_receive_confirm');


    Route::prefix('restore')->middleware([HeaderAuthenticationMiddleware::class])->group(function() {
        Route::get('/item', [ProductionRecipeItemsController::class,'restore'])->name('pro_item_restore');
    });

});

Route::get('finish-goods/download', [ProductionRecipeItemsController::class,'finishGoodsDownload'])->name('finish_goods_download');
