<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\CrosswordController;
use App\Http\Controllers\PzHintGeneratorController;
use App\Http\Controllers\Api\CrosswordGeneratorController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 인증 라우트
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});

// 게시판 API 라우트
Route::prefix('board')->group(function () {
    Route::get('{boardType}', [BoardController::class, 'index']);
    Route::get('{boardType}/{id}', [BoardController::class, 'show']);
    Route::get('{boardType}/threads/{id}', [BoardController::class, 'getRelatedThreads']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('{boardType}', [BoardController::class, 'store']);
        Route::put('{boardType}/{id}', [BoardController::class, 'update']);
        Route::delete('{boardType}/{id}', [BoardController::class, 'destroy']);
    });
});

// 댓글 관련 라우트
Route::prefix('board/{boardType}/{boardId}/comments')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [CommentController::class, 'index']);
    Route::post('/', [CommentController::class, 'store']);
    Route::put('/{comment}', [CommentController::class, 'update']);
    Route::delete('/{comment}', [CommentController::class, 'destroy']);
});

Route::get('/dictionary', [DictionaryController::class, 'getDefinition']);

// 십자낱말 퍼즐 API 라우트
Route::prefix('crossword')->middleware('puzzle-api')->group(function () {
    Route::get('/puzzle/{levelId}', [CrosswordController::class, 'getPuzzle']);
    Route::post('/submit-answer', [CrosswordController::class, 'submitAnswer']);
    Route::post('/puzzle', [CrosswordController::class, 'storePuzzle']);
});

// 새로운 크로스워드 생성 API 라우트 (제미나이 알고리즘)
Route::prefix('crossword-generator')->group(function () {
    Route::post('/generate', [CrosswordGeneratorController::class, 'generate']);
    Route::get('/preview', [CrosswordGeneratorController::class, 'preview']);
    Route::get('/stats', [CrosswordGeneratorController::class, 'stats']);
});

Route::post('/puzzle/generate-hints', [PzHintGeneratorController::class, 'generateForWords']);
Route::get('/puzzle/words', [PzHintGeneratorController::class, 'getWords']);
Route::get('/puzzle/crossword-words', [PzHintGeneratorController::class, 'getCrosswordWords']);

// 테스트용 라우트
Route::get('/test', function () {
    return response()->json(['message' => 'API 서버가 정상 작동 중입니다!']);
});
