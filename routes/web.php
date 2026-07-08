<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;



//use App\Models\Customer;
//use Illuminate\Support\Facades\Hash;
// ─────────────────────────────────────────
// PUBLIC
// ─────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/auth/me', [AuthController::class, 'me']);
Route::get('/menu', [ProductController::class, 'getAvailableProducts']);
Route::get('/orders/{orderId}/track', [OrderController::class, 'track']);


Route::get('/customer/lookup', [CustomerController::class, 'lookup']);
Route::post('/customer/confirm-details', [CustomerController::class, 'confirmDetails']);
Route::post('/register', [CustomerController::class, 'register']);
//routes/web.php

 //Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
// Route::post('/register', [CustomerController::class, 'register'])->name('register');

// Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
// ─────────────────────────────────────────
// CUSTOMER
// ─────────────────────────────────────────
Route::middleware('auth:customer')->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/history', [OrderController::class, 'history']);
});

// ─────────────────────────────────────────
// STAFF
// ─────────────────────────────────────────
Route::middleware('auth:web')->prefix('staff')->group(function () {

    // Categories
    Route::get('/categories', [ProductController::class, 'getCategories']);
    Route::post('/categories', [ProductController::class, 'storeCategory']);
    Route::put('/categories/{id}', [ProductController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [ProductController::class, 'destroyCategory']);

    // SubCategories
    Route::get('/sub-categories', [ProductController::class, 'getSubCategories']);
    Route::get('/sub-categories/category/{categoryId}', [ProductController::class, 'getSubCategoriesByCategory']);
    Route::post('/sub-categories', [ProductController::class, 'storeSubCategory']);
    Route::put('/sub-categories/{id}', [ProductController::class, 'updateSubCategory']);
    Route::delete('/sub-categories/{id}', [ProductController::class, 'destroySubCategory']);

    // Products
    Route::get('/products', [ProductController::class, 'getProducts']);
    Route::get('/products/{id}', [ProductController::class, 'showProduct']);
    Route::post('/products', [ProductController::class, 'storeProduct']);
    Route::put('/products/{id}', [ProductController::class, 'updateProduct']);
    Route::delete('/products/{id}', [ProductController::class, 'destroyProduct']);
    Route::patch('/products/{id}/toggle', [ProductController::class, 'toggleAvailability']);

    // Materials
    Route::get('/materials', [ProductController::class, 'getMaterials']);
    Route::get('/materials/low-stock', [ProductController::class, 'getLowStock']);
    Route::post('/materials', [ProductController::class, 'storeMaterial']);
    Route::put('/materials/{id}', [ProductController::class, 'updateMaterial']);
    Route::delete('/materials/{id}', [ProductController::class, 'destroyMaterial']);
    Route::post('/materials/{id}/restock', [ProductController::class, 'restock']);

    // Recipes
    Route::get('/products/{productId}/recipe', [ProductController::class, 'getRecipe']);
    Route::post('/products/{productId}/recipe', [ProductController::class, 'syncRecipe']);
    Route::delete('/products/{productId}/recipe/{materialId}', [ProductController::class, 'removeRecipeItem']);

    // Orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/active', [OrderController::class, 'active']);
    Route::patch('/orders/{orderId}/complete', [OrderController::class, 'complete']);
    //Route::patch('/orders/{orderId}/cancel', [OrderController::class, 'cancel']);
    //Route::post('/orders/{orderId}/payment', [OrderController::class, 'confirmPayment']);

     // Cook only
Route::patch('/order-items/{itemId}/claim', [OrderController::class, 'claimItem'])
    ->middleware('role:cook');
Route::patch('/order-items/{itemId}/done', [OrderController::class, 'itemDone'])
    ->middleware('role:cook');

// CEO, Supervisor, staff who placed (further auth in service)
Route::patch('/orders/{orderId}/ready', [OrderController::class, 'markReady'])
    ->middleware('role:ceo,supervisor,cashier,waiter');

    // CEO only
Route::delete('/orders/{orderId}/cancel', [OrderController::class, 'cancel'])
    ->middleware('role:ceo');

// CEO + Supervisor
Route::get('/orders', [OrderController::class, 'index'])
    ->middleware('role:ceo,supervisor','cashier');

    // Cashier + CEO
Route::post('/orders/{orderId}/payment', [OrderController::class, 'confirmPayment'])
    ->middleware('role:cashier,ceo,supervisor');

});

//Vue SPA catch-all — must be last
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');





// Route::get('/test-create', function () {

//     $customer = Customer::create([
//         'first_name' => 'Test',
//         'surname'    => 'User',
//         'username'   => 'tester',
//         'phone'      => '08011111111',
//         'sex'        => 'Male',
//         'password'   => Hash::make('123456'),
//         'address'    => 'Test Address',
//     ]);

//     return $customer;
// });