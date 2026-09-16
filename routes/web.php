<?php

use Illuminate\Support\Facades\Route;
<<<<<<< HEAD

Route::get('/', function () {
    return view('welcome');
=======
use App\Http\Controllers\{DashboardController, ProjectController, TaskController, TaskMemberController};
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserManagementController;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // SRS-009: progres tugas ditampilkan di dalam projects.show &
    // projects.index (bagian dari resource route 'projects' di bawah).
    Route::resource('projects', ProjectController::class);

    Route::get('projects/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('projects/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('tasks/{task}/done', [TaskController::class, 'markAsDone'])->name('tasks.done'); // SRS-009: memicu update progres

    // SRS-008: penugasan tugas ke lebih dari satu anggota tim.
    Route::post('tasks/{task}/members', [TaskMemberController::class, 'store'])->name('tasks.members.add');
    Route::delete('tasks/{task}/members/{userId}', [TaskMemberController::class, 'destroy'])->name('tasks.members.remove');

    // SRS-007: panel admin, dibatasi middleware 'isAdmin' (role = admin).
    Route::middleware('isAdmin')->prefix('admin')->name('admin.')->group(
        fn () => Route::resource('users', UserManagementController::class)->except('show')
    );
>>>>>>> origin/feature/admin-panel
});
