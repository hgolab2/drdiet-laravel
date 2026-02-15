<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DietUserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\DietItemController;
use App\Http\Controllers\Api\DietMealController;
use App\Http\Controllers\Api\DietWeeklyController;
use App\Http\Controllers\Api\DietUserWeeklyController;
use App\Http\Controllers\Api\CalorieController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\DietLeadController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\ExerciseProgramController;
use App\Http\Controllers\Api\ExerciseUsersProgramController;
use App\Http\Controllers\Api\MessageController;

Route::get('/exercise-users-programs', [ExerciseUsersProgramController::class, 'index']);
Route::post('/exercise-users-programs', [ExerciseUsersProgramController::class, 'store']);
Route::get('/exercise-users-programs/{id}', [ExerciseUsersProgramController::class, 'show']);
Route::delete('/exercise-users-programs/{id}', [ExerciseUsersProgramController::class, 'destroy']);
Route::get('/exercise-program-detail', [ExerciseUsersProgramController::class, 'detail']);

Route::get('/exercise-programs', [ExerciseProgramController::class, 'index']);
Route::post('/exercise-programs', [ExerciseProgramController::class, 'store']);
Route::get('/exercise-programs/{id}', [ExerciseProgramController::class, 'show']);
Route::put('/exercise-programs/{id}', [ExerciseProgramController::class, 'update']);
Route::delete('/exercise-programs/{id}', [ExerciseProgramController::class, 'destroy']);


Route::prefix('exercises')->group(function () {
    Route::get('/', [ExerciseController::class, 'index']);
    Route::get('/{id}', [ExerciseController::class, 'show']);
    Route::post('/', [ExerciseController::class, 'store']);
    Route::post('/{id}', [ExerciseController::class, 'update']);
    Route::delete('/{id}', [ExerciseController::class, 'destroy']);
});
Route::get('/muscles', [ExerciseController::class, 'muscleLists']);


Route::get('auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);


Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::put('/{id}', [PaymentController::class, 'update']);
    Route::delete('/{id}', [PaymentController::class, 'destroy']);
});

Route::get('/transactions/handleReturn', [PaymentController::class,'handleReturn']);
Route::get('/transactions/{token}', [PaymentController::class,'storeTransaction']);
Route::get('/transactions', [PaymentController::class, 'listTransaction']);
Route::put('/transactions/{id}', [PaymentController::class, 'updateTransaction']);
Route::delete('/transactions/{id}', [PaymentController::class, 'destroyTransaction']);


Route::prefix('subscriptions')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index']);
    Route::post('/', [SubscriptionController::class, 'store']);
    Route::put('/{id}', [SubscriptionController::class, 'update']);
    Route::delete('/{id}', [SubscriptionController::class, 'destroy']);
});
Route::post('/addSubscriptions', [SubscriptionController::class, 'addSubscriptions']);


Route::prefix('calories')->group(function () {
    Route::get('/', [CalorieController::class, 'index']);
    Route::post('/', [CalorieController::class, 'store']);
    Route::put('/{id}', [CalorieController::class, 'update']);
    Route::delete('/{id}', [CalorieController::class, 'destroy']);
});

Route::get('/user-weekly', [DietUserWeeklyController::class, 'index']);
Route::post('/user-weekly', [DietUserWeeklyController::class, 'store']);
Route::get('/user-weekly/{id}', [DietUserWeeklyController::class, 'show']);
Route::put('/user-weekly/{id}', [DietUserWeeklyController::class, 'update']);
Route::post('/diet/updateWeekly', [DietUserWeeklyController::class, 'updateWeekly']);
Route::post('/diet/updateWeekly', [DietUserWeeklyController::class, 'updateWeekly']);
Route::post('/addDiet', [DietUserWeeklyController::class, 'addDiet']);

Route::post('/updateWeight', [DietUserWeeklyController::class, 'updateWeight']);

Route::delete('/user-weekly/{id}', [DietUserWeeklyController::class, 'destroy']);

Route::get('/diet-weekly', [DietWeeklyController::class, 'index']);
Route::get('/diet-weekly/{id}', [DietWeeklyController::class, 'show']);
Route::post('/diet-weekly', [DietWeeklyController::class, 'store']);
Route::put('/diet-weekly/{id}', [DietWeeklyController::class, 'update']);
Route::delete('/diet-weekly/{id}', [DietWeeklyController::class, 'destroy']);
Route::post('/diet-weekly/day-meals', [DietWeeklyController::class, 'mealsByDay']);



Route::post('/diet-weekly', [DietWeeklyController::class, 'store']);
Route::get('/diet-weekly/{id}', [DietWeeklyController::class, 'show']);


Route::get('/diet-meals', [DietMealController::class, 'index']);
Route::post('/diet-meals', [DietMealController::class, 'store']);
Route::get('/meal-categories', [DietMealController::class, 'MealCategories']);
Route::get('/diet-meals/{id}', [DietMealController::class, 'show']);
Route::delete('/diet-meals/{id}', [DietMealController::class, 'destroy']);
Route::get('/diet-meals/{mealId}/items', [DietMealController::class, 'itemsByMeal']);
Route::post('/diet-meals/{id}', [DietMealController::class, 'update']);



Route::prefix('locations')->group(function () {
    Route::get('/countries', [LocationController::class, 'countries']);
    Route::get('/states', [LocationController::class, 'states']);
    Route::get('/cities', [LocationController::class, 'cities']);
});

Route::post('/diet/register', [DietUserController::class, 'register']);
Route::post('/diet-users', [DietUserController::class, 'index']);
Route::get('/diet-users/{id}', [DietUserController::class, 'show']);
Route::delete('/diet-users/{id}', [DietUserController::class, 'destroy']);
Route::put('/diet/{id}/update', [DietUserController::class, 'update']);
Route::put('/update-user', [DietUserController::class, 'updateUser']);

Route::post('/diet-users/set-roles', [DietUserController::class, 'setRoles']);

Route::post('/diet-users/{id}/status', [DietUserController::class, 'toggleStatus']);

Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/login-token', [LoginController::class, 'loginToken'])->name('loginToken');
Route::post('/createUser', [AuthController::class, 'createUser'])->name('createUser');
Route::post('/userlogin', [AuthController::class, 'login'])->name('userlogin');
Route::get('/last-user-weekly', [DietUserWeeklyController::class, 'lastUserWeekly']);
Route::get('/last-user-weekly-items', [DietUserWeeklyController::class, 'lastUserWeeklyItems']);

Route::get('/food-cultures', [DietUserController::class, 'foodCultureList']);
Route::get('/diet/types', [DietUserController::class, 'dietTypes']);
Route::get('/diet/activity-levels', [DietUserController::class, 'activityLevels']);
Route::get('/diet/goals', [DietUserController::class, 'dietGoals']);
Route::get('/diet/history-options', [DietUserController::class, 'dietHistories']);

Route::get('/food-types', [DietUserWeeklyController::class, 'foodTypes']);

Route::prefix('diet-items')->group(function () {
    Route::get('/list', [DietItemController::class, 'index']);
    Route::get('/{id}', [DietItemController::class, 'show']);
    Route::post('/', [DietItemController::class, 'store']);
    Route::put('/{id}', [DietItemController::class, 'update']);
    Route::delete('/{id}', [DietItemController::class, 'destroy']);
});

Route::middleware('auth:api')->get('/meal-types', [DietMealController::class, 'mealTypes']);

Route::get('/diet-leads', [DietLeadController::class, 'index']);       // لیست لیدها
Route::post('/diet-leads', [DietLeadController::class, 'store']);      // ایجاد لید جدید
Route::post('/lead/register1', [DietLeadController::class, 'register1']);
Route::post('/lead/register2', [DietLeadController::class, 'register2']);
Route::put('/diet-leads-edit', [DietLeadController::class, 'edit']); // ویرایش لید
Route::get('/diet-leads/{id}', [DietLeadController::class, 'show']);   // مشاهده جزئیات لید
Route::put('/diet-leads/{id}', [DietLeadController::class, 'update']); // ویرایش لید
Route::delete('/diet-leads/{id}', [DietLeadController::class, 'destroy']); // حذف لید

Route::get('diet-leads/source-report', [DietLeadController::class, 'sourceReport']);

Route::post('/ai/generate', [DietUserController::class, 'generate']);

Route::post('/webhook/whatsapp', [MessageController::class, 'handle']);
Route::post('/whatsapp/send-message', [MessageController::class, 'sendMessage']);


