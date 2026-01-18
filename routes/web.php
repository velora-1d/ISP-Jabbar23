<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\SchedulingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\RecurringBillingController;

Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================
// All Authenticated Routes
// ============================================
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // User & Role Management
    Route::resource('users', UserController::class);
    
    // Package Management
    Route::resource('packages', PackageController::class);
    
    // Lead/CRM Management
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::resource('leads', LeadController::class);
    Route::resource('contracts', ContractController::class);
    Route::resource('partners', PartnerController::class);
    
    // Payment Management
    Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::resource('payments', PaymentController::class);

    // Inventory Management
    Route::post('inventory/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');
    Route::resource('inventory', InventoryController::class);

    // Work Order / Field Ops
    Route::post('work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])->name('work-orders.update-status');
    Route::post('work-orders/{workOrder}/add-item', [WorkOrderController::class, 'addItem'])->name('work-orders.add-item');
    Route::delete('work-order-items/{item}', [WorkOrderController::class, 'removeItem'])->name('work-order-items.destroy');
    Route::resource('work-orders', WorkOrderController::class);
    
    // Technician Management
    Route::resource('technicians', TechnicianController::class);
    Route::patch('technicians/{technician}/toggle-active', [TechnicianController::class, 'toggleActive'])
        ->name('technicians.toggleActive');
    
    // Customer Management
    Route::patch('customers/{customer}/status', [CustomerController::class, 'updateStatus'])
        ->name('customers.updateStatus');
    Route::resource('customers', CustomerController::class);
    Route::get('customers/{customer}/payment-history', [CustomerController::class, 'paymentHistory'])
        ->name('customers.paymentHistory');
    
    // Invoice Routes (specific routes before resource)
    Route::patch('invoices/{invoice}/pay', [InvoiceController::class, 'markAsPaid'])
        ->name('invoices.markAsPaid');
    Route::get('invoices/scanner', [InvoiceController::class, 'scanner'])
        ->name('invoices.scanner');
    Route::post('invoices/lookup-token', [InvoiceController::class, 'lookupByToken'])
        ->name('invoices.lookupByToken');
    Route::post('invoices/pay-token', [InvoiceController::class, 'payByToken'])
        ->name('invoices.payByToken');
    Route::post('invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
    Route::resource('invoices', InvoiceController::class);
    
    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    
    // Tickets / Helpdesk
    Route::resource('tickets', TicketController::class);
    
    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::patch('settings', [SettingController::class, 'update'])->name('settings.update');
    
    // Payment Gateways
    Route::get('settings/payment-gateways', [\App\Http\Controllers\PaymentGatewayController::class, 'index'])->name('settings.payment-gateways');
    Route::post('settings/payment-gateways', [\App\Http\Controllers\PaymentGatewayController::class, 'update'])->name('settings.payment-gateways.update');
    Route::post('settings/payment-gateways/test/{gateway}', [\App\Http\Controllers\PaymentGatewayController::class, 'testConnection'])->name('settings.payment-gateways.test');

    // Scheduling
    Route::get('/scheduling', [SchedulingController::class, 'index'])->name('scheduling.index');
    Route::get('/scheduling/events', [SchedulingController::class, 'events'])->name('scheduling.events');

    // Billing Module
    Route::prefix('billing')->name('billing.')->group(function () {
        // Recurring Billing
        Route::get('recurring', [RecurringBillingController::class, 'index'])->name('recurring');
        Route::get('recurring/{customer}', [RecurringBillingController::class, 'show'])->name('recurring.show');
        Route::patch('recurring/{customer}/billing-date', [RecurringBillingController::class, 'updateBillingDate'])->name('recurring.update-billing-date');
        
        // Proforma Invoices
        Route::get('proforma', [\App\Http\Controllers\ProformaInvoiceController::class, 'index'])->name('proforma');
        Route::get('proforma/create', [\App\Http\Controllers\ProformaInvoiceController::class, 'create'])->name('proforma.create');
        Route::post('proforma', [\App\Http\Controllers\ProformaInvoiceController::class, 'store'])->name('proforma.store');
        Route::get('proforma/{proforma}', [\App\Http\Controllers\ProformaInvoiceController::class, 'show'])->name('proforma.show');
        Route::post('proforma/{proforma}/convert', [\App\Http\Controllers\ProformaInvoiceController::class, 'convert'])->name('proforma.convert');
        Route::post('proforma/{proforma}/cancel', [\App\Http\Controllers\ProformaInvoiceController::class, 'cancel'])->name('proforma.cancel');
        
        // Credit Notes
        Route::get('credit-notes', [\App\Http\Controllers\CreditNoteController::class, 'index'])->name('credit-notes');
        Route::get('credit-notes/create', [\App\Http\Controllers\CreditNoteController::class, 'create'])->name('credit-notes.create');
        Route::post('credit-notes', [\App\Http\Controllers\CreditNoteController::class, 'store'])->name('credit-notes.store');
        Route::get('credit-notes/{creditNote}', [\App\Http\Controllers\CreditNoteController::class, 'show'])->name('credit-notes.show');
        Route::post('credit-notes/{creditNote}/apply', [\App\Http\Controllers\CreditNoteController::class, 'apply'])->name('credit-notes.apply');
        Route::post('credit-notes/{creditNote}/cancel', [\App\Http\Controllers\CreditNoteController::class, 'cancel'])->name('credit-notes.cancel');
    });

    // Network Module
    Route::group(['prefix' => 'network', 'as' => 'network.'], function () {
        Route::resource('odps', \App\Http\Controllers\Network\OdpController::class);
        Route::resource('olts', \App\Http\Controllers\Network\OltController::class);
        Route::get('monitoring', [\App\Http\Controllers\Network\NetworkMonitoringController::class, 'index'])->name('monitoring.index');
        Route::post('monitoring/ping', [\App\Http\Controllers\Network\NetworkMonitoringController::class, 'ping'])->name('monitoring.ping');
    });
    
    // Payment Manual & Gateway
    Route::resource('payments', PaymentController::class);
    Route::post('payment/{invoice}/create', [PaymentController::class, 'createTransaction'])
        ->name('payment.create');
});

// ============================================
// Profile Routes
// ============================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ============================================
// Public Webhook (No Auth)
// ============================================
Route::post('payment/webhook', [PaymentController::class, 'handleWebhook'])
    ->name('payment.webhook');

require __DIR__.'/auth.php';
