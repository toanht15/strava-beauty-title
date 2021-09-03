<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityController;
use Illuminate\Http\Request;
use App\Services\StravaWebhookService;
use Illuminate\Http\Response;
use App\Http\Middleware\VerifyCsrfToken;
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

Route::get('/', function () {
    return view('welcome');
});

Route::resource('activities', ActivityController::class);

Route::get('/callback', [ActivityController::class, 'callback']);
Route::get('/info', [ActivityController::class, 'showInfo']);

Route::post('/webhook', [ActivityController::class, 'updateActivity'])->withoutMiddleware(VerifyCsrfToken::class);
Route::get('/webhook', [ActivityController::class, 'validateCallback']);

Route::get('/subscribe', [ActivityController::class, 'subscribe']);

Route::get('/check', [ActivityController::class, 'check']);
Route::get('/refresh', [ActivityController::class, 'refresh']);

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');


//Route::get('/webhook', function (Request $request) {
//    $mode = $request->query('hub_mode'); // hub.mode
//    $token = $request->query('hub_verify_token'); // hub.verify_token
//    $challenge = $request->query('hub_challenge'); // hub.challenge
//
//    return app(StravaWebhookService::class)->validate($mode, $token, $challenge);
//});
//

//Route::post('/webhook', function (Request $request) {
//    $aspect_type = $request['aspect_type']; // "create" | "update" | "delete"
//    $event_time = $request['event_time']; // time the event occurred
//    $object_id = $request['object_id']; // activity ID | athlete ID
//    $object_type = $request['object_type']; // "activity" | "athlete"
//    $owner_id = $request['owner_id']; // athlete ID
//    $subscription_id = $request['subscription_id']; // push subscription ID receiving the event
//    $updates = $request['updates']; // activity update: {"title" | "type" | "private": true/false} ; app deauthorization: {"authorized": false}
//
//    Log::channel('strava')->info(json_encode($request->all()));
//
//    return response('EVENT_RECEIVED', Response::HTTP_OK);
//})->withoutMiddleware(VerifyCsrfToken::class);
