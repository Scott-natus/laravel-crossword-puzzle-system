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
use App\Http\Controllers\MobilePushController;
use App\Http\Controllers\Api\PuzzleGameController;

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

// 십자낱말 퍼즐 API 라우트 (인증 불필요)
Route::prefix('crossword')->group(function () {
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

// 모바일 푸시 알림 관련 라우트
Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    Route::post('/push-token', [MobilePushController::class, 'registerToken']);
    Route::delete('/push-token', [MobilePushController::class, 'unregisterToken']);
    Route::get('/push-settings', [MobilePushController::class, 'getSettings']);
    Route::put('/push-settings', [MobilePushController::class, 'updateSettings']);
});

// 모든 OPTIONS 요청에 대해 200 OK로 응답 (CORS preflight 우회)
Route::options('/{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');

// 테스트용 라우트
Route::get('/test', function () {
    return response()->json(['message' => 'API 서버가 정상 작동 중입니다!']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user/stats', [AuthController::class, 'getUserStats']);
    Route::get('user/recent-games', [AuthController::class, 'getRecentGames']);
    Route::get('user/recent-game-sessions', [AuthController::class, 'getRecentGameSessions']);
    
    // 퍼즐게임 API 라우트 (기존 라라벨 웹용)
    Route::prefix('puzzle-game')->group(function () {
        Route::get('/template', [\App\Http\Controllers\PuzzleGameController::class, 'getTemplate']);
        Route::post('/check-answer', [\App\Http\Controllers\PuzzleGameController::class, 'checkAnswer']);
        Route::get('/hints', [\App\Http\Controllers\PuzzleGameController::class, 'getHints']);
        Route::post('/complete-level', [\App\Http\Controllers\PuzzleGameController::class, 'completeLevel']);
    });
    
    // React 앱용 퍼즐게임 API 라우트
    Route::prefix('puzzle')->group(function () {
        Route::get('/template', [PuzzleGameController::class, 'getTemplate']);
        Route::post('/submit-answer', [PuzzleGameController::class, 'submitAnswer']);
        Route::get('/hints', [PuzzleGameController::class, 'getPuzzleHints']);
        Route::post('/generate', [PuzzleGameController::class, 'generate']);
        Route::post('/check-completion', [PuzzleGameController::class, 'checkCompletion']);
        Route::post('/save-game-state', [PuzzleGameController::class, 'saveGameState']);
        Route::post('/submit-result', [PuzzleGameController::class, 'submitResult']);
        Route::post('/complete-level', [PuzzleGameController::class, 'completeLevel']);
    });
});
