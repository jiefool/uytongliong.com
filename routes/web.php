<?php

use App\Http\Controllers\FamilyMemberController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GenerationalMessageController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\PinAccessController;
use App\Http\Controllers\TreeAccessPinController;
use App\Http\Controllers\ClanEventController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::get('/view', [PinAccessController::class, 'show'])->name('pin.entry');
Route::post('/view', [PinAccessController::class, 'verify'])->middleware('throttle:10,1')->name('pin.verify');
Route::post('/view/leave', [PinAccessController::class, 'leave'])->name('pin.leave');
Route::middleware('tree.pin')->get('/view/family-tree', function () { return view('family-tree', ['pinViewer' => true]); })->name('pin.tree');
Route::view('/view/tree', 'family-tree-tree', ['pinViewer' => true])->middleware('tree.pin')->name('pin.tree-view');
Route::middleware('tree.pin')->get('/view/messages', [GenerationalMessageController::class, 'viewerIndex'])->name('pin.messages');
Route::middleware('tree.access')->get('/message-to-next-generations/{message}/video', [GenerationalMessageController::class, 'video'])->name('generational-messages.video');
Route::get('/members', [FamilyMemberController::class, 'index'])->middleware('tree.access')->name('members.index');
Route::get('/events', [ClanEventController::class, 'publicIndex'])->name('events.public');
Route::get('/newsletters', [NewsletterController::class, 'publicIndex'])->name('newsletters.public');

Route::middleware('auth')->group(function () {
    Route::view('/family-tree', 'family-tree')->name('family-tree');
    Route::view('/family-tree/tree', 'family-tree-tree')->name('family-tree.tree-view');
    Route::post('/members', [FamilyMemberController::class, 'store'])->name('members.store');
    Route::put('/members/{member}', [FamilyMemberController::class, 'update'])->name('members.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('site.content')->group(function () {
        Route::get('/message-to-next-generations', [GenerationalMessageController::class, 'index'])->name('message-to-next-generations');
        Route::post('/message-to-next-generations', [GenerationalMessageController::class, 'store'])->name('generational-messages.store');
        Route::get('/message-to-next-generations/{message}/edit', [GenerationalMessageController::class, 'edit'])->name('generational-messages.edit');
        Route::put('/message-to-next-generations/{message}', [GenerationalMessageController::class, 'update'])->name('generational-messages.update');
        Route::delete('/message-to-next-generations/{message}', [GenerationalMessageController::class, 'destroy'])->name('generational-messages.destroy');
        Route::delete('/members/{member}', [FamilyMemberController::class, 'destroy'])->name('members.destroy');
        Route::get('/access-pins', [TreeAccessPinController::class, 'index'])->name('access-pins.index');
        Route::post('/access-pins', [TreeAccessPinController::class, 'store'])->name('access-pins.store');
        Route::delete('/access-pins/{accessPin}', [TreeAccessPinController::class, 'destroy'])->name('access-pins.destroy');
        Route::get('/events/manage', [ClanEventController::class, 'index'])->name('events.index');
        Route::post('/events', [ClanEventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [ClanEventController::class, 'edit'])->name('events.edit');
        Route::put('/events/{event}', [ClanEventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [ClanEventController::class, 'destroy'])->name('events.destroy');
        Route::post('/events/{event}/rsvp', [ClanEventController::class, 'rsvp'])->name('events.rsvp');

        Route::get('/newsletters/manage', [NewsletterController::class, 'index'])->name('newsletters.index');
        Route::get('/newsletters/create', [NewsletterController::class, 'create'])->name('newsletters.create');
        Route::post('/newsletters', [NewsletterController::class, 'store'])->name('newsletters.store');
        Route::get('/newsletters/{newsletter}/edit', [NewsletterController::class, 'edit'])->name('newsletters.edit');
        Route::put('/newsletters/{newsletter}', [NewsletterController::class, 'update'])->name('newsletters.update');
        Route::delete('/newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');
    });

    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });
});

Route::get('/events/{event}', [ClanEventController::class, 'show'])->name('events.show');
Route::get('/newsletters/{newsletter}/cover', [NewsletterController::class, 'cover'])->name('newsletters.cover');
Route::get('/newsletters/{newsletter}', [NewsletterController::class, 'show'])->name('newsletters.show');
