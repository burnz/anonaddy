<?php

use Illuminate\Support\Facades\Route;

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

Route::group([
  'middleware' => ['auth:api', 'verified'],
  'prefix' => 'v1'
], function () {
    Route::get('/aliases', 'Api\AliasController@index');
    Route::get('/aliases/{id}', 'Api\AliasController@show');
    Route::post('/aliases', 'Api\AliasController@store');
    Route::patch('/aliases/{id}', 'Api\AliasController@update');
    Route::patch('/aliases/{id}/restore', 'Api\AliasController@restore');
    Route::delete('/aliases/{id}', 'Api\AliasController@destroy');
    Route::delete('/aliases/{id}/forget', 'Api\AliasController@forget');

    Route::post('/active-aliases', 'Api\ActiveAliasController@store');
    Route::delete('/active-aliases/{id}', 'Api\ActiveAliasController@destroy');

    Route::post('/alias-recipients', 'Api\AliasRecipientController@store');

    Route::get('/recipients', 'Api\RecipientController@index');
    Route::get('/recipients/{id}', 'Api\RecipientController@show');
    Route::post('/recipients', 'Api\RecipientController@store');
    Route::delete('/recipients/{id}', 'Api\RecipientController@destroy');

    Route::patch('/recipient-keys/{id}', 'Api\RecipientKeyController@update');
    Route::delete('/recipient-keys/{id}', 'Api\RecipientKeyController@destroy');

    Route::post('/encrypted-recipients', 'Api\EncryptedRecipientController@store');
    Route::delete('/encrypted-recipients/{id}', 'Api\EncryptedRecipientController@destroy');

    Route::post('/allowed-recipients', 'Api\AllowedRecipientController@store');
    Route::delete('/allowed-recipients/{id}', 'Api\AllowedRecipientController@destroy');

    Route::get('/domains', 'Api\DomainController@index');
    Route::get('/domains/{id}', 'Api\DomainController@show');
    Route::post('/domains', 'Api\DomainController@store');
    Route::patch('/domains/{id}', 'Api\DomainController@update');
    Route::delete('/domains/{id}', 'Api\DomainController@destroy');
    Route::patch('/domains/{id}/default-recipient', 'Api\DomainDefaultRecipientController@update');

    Route::post('/active-domains', 'Api\ActiveDomainController@store');
    Route::delete('/active-domains/{id}', 'Api\ActiveDomainController@destroy');

    Route::post('/catch-all-domains', 'Api\CatchAllDomainController@store');
    Route::delete('/catch-all-domains/{id}', 'Api\CatchAllDomainController@destroy');

    Route::get('/usernames', 'Api\UsernameController@index');
    Route::get('/usernames/{id}', 'Api\UsernameController@show');
    Route::post('/usernames', 'Api\UsernameController@store');
    Route::patch('/usernames/{id}', 'Api\UsernameController@update');
    Route::delete('/usernames/{id}', 'Api\UsernameController@destroy');
    Route::patch('/usernames/{id}/default-recipient', 'Api\UsernameDefaultRecipientController@update');

    Route::post('/active-usernames', 'Api\ActiveUsernameController@store');
    Route::delete('/active-usernames/{id}', 'Api\ActiveUsernameController@destroy');

    Route::post('/catch-all-usernames', 'Api\CatchAllUsernameController@store');
    Route::delete('/catch-all-usernames/{id}', 'Api\CatchAllUsernameController@destroy');

    Route::get('/rules', 'Api\RuleController@index');
    Route::get('/rules/{id}', 'Api\RuleController@show');
    Route::post('/rules', 'Api\RuleController@store');
    Route::patch('/rules/{id}', 'Api\RuleController@update');
    Route::delete('/rules/{id}', 'Api\RuleController@destroy');
    Route::post('/reorder-rules', 'Api\ReorderRuleController@store');

    Route::post('/active-rules', 'Api\ActiveRuleController@store');
    Route::delete('/active-rules/{id}', 'Api\ActiveRuleController@destroy');

    Route::get('/failed-deliveries', 'Api\FailedDeliveryController@index');
    Route::get('/failed-deliveries/{id}', 'Api\FailedDeliveryController@show');
    Route::delete('/failed-deliveries/{id}', 'Api\FailedDeliveryController@destroy');

    Route::get('/domain-options', 'Api\DomainOptionController@index');

    Route::get('/account-details', 'Api\AccountDetailController@index');

    Route::get('/app-version', 'Api\AppVersionController@index');
});
