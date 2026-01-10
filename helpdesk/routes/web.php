<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    $myTickets = $user->createdTickets()->latest()->limit(5)->get();
    $assignedTickets = $user->assignedTickets()->latest()->limit(5)->get();
    return view('dashboard', compact('myTickets', 'assignedTickets'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tickets', [\App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [\App\Http\Controllers\TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [\App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/suggestions', [\App\Http\Controllers\SuggestionController::class, 'index'])->name('tickets.suggestions');

    Route::get('/tickets/{ticket}', [\App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', [\App\Http\Controllers\TicketController::class, 'edit'])->name('tickets.edit');
    Route::patch('/tickets/{ticket}', [\App\Http\Controllers\TicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket}/assign', [\App\Http\Controllers\TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket}/claim', [\App\Http\Controllers\TicketController::class, 'claim'])->name('tickets.claim');
    Route::post('/tickets/{ticket}/resolve', [\App\Http\Controllers\TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{ticket}/close', [\App\Http\Controllers\TicketController::class, 'close'])->name('tickets.close');
    Route::get('/tickets/{ticket}/attachments/view/{attachment}', [\App\Http\Controllers\TicketController::class, 'viewAttachment'])->name('tickets.attachments.view');
    Route::get('/tickets/{ticket}/attachments/{attachment}/diag', [\App\Http\Controllers\TicketController::class, 'diagAttachment'])->name('tickets.attachments.diag');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [\App\Http\Controllers\TicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    Route::delete('/tickets/{ticket}/attachments/{attachment}', [\App\Http\Controllers\TicketController::class, 'destroyAttachment'])->name('tickets.attachments.destroy');
});

require __DIR__.'/auth.php';
