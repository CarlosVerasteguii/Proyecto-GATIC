<?php

use App\Livewire\Admin\ErrorReports\ErrorReportsLookup;
use App\Livewire\Admin\Users\UserForm;
use App\Livewire\Admin\Users\UsersIndex;
use App\Livewire\Catalogs\Brands\BrandsIndex;
use App\Livewire\Catalogs\Categories\CategoriesIndex;
use App\Livewire\Catalogs\Categories\CategoryForm;
use App\Livewire\Catalogs\Locations\LocationsIndex;
use App\Livewire\Catalogs\Trash\CatalogsTrash;
use App\Livewire\Dev\LivewireSmokeTest;
use App\Livewire\Employees\EmployeeShow;
use App\Livewire\Employees\EmployeesIndex;
use App\Livewire\Inventory\Adjustments\AdjustmentsIndex as InventoryAdjustmentsIndex;
use App\Livewire\Inventory\Adjustments\AssetAdjustmentForm as InventoryAssetAdjustmentForm;
use App\Livewire\Inventory\Adjustments\ProductAdjustmentForm as InventoryProductAdjustmentForm;
use App\Livewire\Inventory\Assets\AssetForm as InventoryAssetForm;
use App\Livewire\Inventory\Assets\AssetShow as InventoryAssetShow;
use App\Livewire\Inventory\Assets\AssetsIndex as InventoryAssetsIndex;
use App\Livewire\Inventory\Products\ProductForm as InventoryProductForm;
use App\Livewire\Inventory\Products\ProductShow as InventoryProductShow;
use App\Livewire\Inventory\Products\ProductsIndex as InventoryProductsIndex;
use App\Livewire\Movements\Assets\AssignAssetForm as MovementsAssignAssetForm;
use App\Livewire\Movements\Assets\LoanAssetForm as MovementsLoanAssetForm;
use App\Livewire\Movements\Assets\ReturnAssetForm as MovementsReturnAssetForm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'active'])->name('dashboard');

Route::middleware(['auth', 'active', 'can:users.manage'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', UsersIndex::class)->name('users.index');
        Route::get('/users/create', UserForm::class)->name('users.create');
        Route::get('/users/{user}/edit', UserForm::class)->name('users.edit');
    });

Route::middleware(['auth', 'active', 'can:admin-only'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/error-reports', ErrorReportsLookup::class)->name('error-reports.lookup');
    });

Route::middleware(['auth', 'active', 'can:catalogs.manage'])
    ->prefix('catalogs')
    ->name('catalogs.')
    ->group(function () {
        Route::get('/categories', CategoriesIndex::class)->name('categories.index');
        Route::get('/categories/create', CategoryForm::class)->name('categories.create');
        Route::get('/categories/{category}/edit', CategoryForm::class)->name('categories.edit');
        Route::get('/brands', BrandsIndex::class)->name('brands.index');
        Route::get('/locations', LocationsIndex::class)->name('locations.index');
    });

Route::middleware(['auth', 'active', 'can:admin-only'])
    ->prefix('catalogs')
    ->name('catalogs.')
    ->group(function () {
        Route::get('/trash', CatalogsTrash::class)->name('trash.index');
    });

Route::middleware(['auth', 'active', 'can:inventory.view'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {
        Route::get('/products', InventoryProductsIndex::class)->name('products.index');
        Route::get('/products/{product}', InventoryProductShow::class)
            ->whereNumber('product')
            ->name('products.show');
        Route::get('/products/{product}/assets', InventoryAssetsIndex::class)
            ->whereNumber('product')
            ->name('products.assets.index');
        Route::get('/products/{product}/assets/{asset}', InventoryAssetShow::class)
            ->whereNumber('product')
            ->whereNumber('asset')
            ->name('products.assets.show');
    });

Route::middleware(['auth', 'active', 'can:inventory.manage'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {
        Route::get('/products/create', InventoryProductForm::class)->name('products.create');
        Route::get('/products/{product}/edit', InventoryProductForm::class)
            ->whereNumber('product')
            ->name('products.edit');
        Route::get('/products/{product}/assets/create', InventoryAssetForm::class)
            ->whereNumber('product')
            ->name('products.assets.create');
        Route::get('/products/{product}/assets/{asset}/edit', InventoryAssetForm::class)
            ->whereNumber('product')
            ->whereNumber('asset')
            ->name('products.assets.edit');
        Route::get('/products/{product}/assets/{asset}/assign', MovementsAssignAssetForm::class)
            ->whereNumber('product')
            ->whereNumber('asset')
            ->name('products.assets.assign');
        Route::get('/products/{product}/assets/{asset}/loan', MovementsLoanAssetForm::class)
            ->whereNumber('product')
            ->whereNumber('asset')
            ->name('products.assets.loan');
        Route::get('/products/{product}/assets/{asset}/return', MovementsReturnAssetForm::class)
            ->whereNumber('product')
            ->whereNumber('asset')
            ->name('products.assets.return');
    });

Route::middleware(['auth', 'active', 'can:admin-only'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {
        Route::get('/adjustments', InventoryAdjustmentsIndex::class)->name('adjustments.index');
        Route::get('/products/{product}/adjust', InventoryProductAdjustmentForm::class)
            ->whereNumber('product')
            ->name('products.adjust');
        Route::get('/products/{product}/assets/{asset}/adjust', InventoryAssetAdjustmentForm::class)
            ->whereNumber('product')
            ->whereNumber('asset')
            ->name('products.assets.adjust');
    });

Route::middleware(['auth', 'active', 'can:inventory.manage'])
    ->prefix('employees')
    ->name('employees.')
    ->group(function () {
        Route::get('/', EmployeesIndex::class)->name('index');
        Route::get('/{employee}', EmployeeShow::class)
            ->whereNumber('employee')
            ->name('show');
    });

if (app()->environment(['local', 'testing'])) {
    Route::get('/dev/livewire-smoke', LivewireSmokeTest::class)
        ->middleware(['auth', 'active'])
        ->name('dev.livewire-smoke');
}

// MVP: Profile management deshabilitado - Story 1.3 scope = "solo login/logout"
// Habilitar en story futura cuando se requiera gestion de perfil
// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';
