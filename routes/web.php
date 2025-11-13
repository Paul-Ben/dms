<?php

use App\Http\Controllers\BackupLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SuperAdminActions;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\VisitorActivityController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\DepartmentController as AdminDepartmentController;
use App\Http\Controllers\Admin\UserManagerController as AdminUserManagerController;
use App\Http\Controllers\User\DocumentController as UserDocumentController;
use App\Http\Controllers\User\ReceiptController as UserReceiptController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');
Route::get('/ministries', function () {
    $ministries = Tenant::where('category', 'Ministry')->paginate(10);
    return view('mdalistings', compact('ministries'));
})->name('mdas');
Route::get('/agencies', function () {
    $agencies =  Tenant::where('category', 'Agency')->paginate(10);
    return view('agencylisting', compact('agencies'));
})->name('agency');

Route::get('/contact', [PagesController::class, 'contactPage'])->name('contact');
Route::post('/contact', [PagesController::class, 'send'])->name('contact.send');

Route::get('/test-verify', function () {
    return view('test-verify');
});
Route::get('/session/check', function () {
    return response()->json(['authenticated' => auth()->check()]);
});

// Local-only dev routes to test payment without auth
if (app()->environment(['local', 'testing'])) {
    Route::get('/_dev/test-payment-init', [SuperAdminActions::class, 'devTestPaymentInit'])->name('dev.testPaymentInit');
    // Submit the paid filing form without auth/CSRF (local/testing only)
    Route::post('/_dev/submit-file-document', [SuperAdminActions::class, 'devSubmitFileDocument'])
        ->withoutMiddleware([App\Http\Middleware\VerifyCsrfToken::class])
        ->name('dev.submitFileDocument');
    // Simulate a successful payment callback using an existing reference
    Route::get('/_dev/test-payment-callback', [SuperAdminActions::class, 'devCompletePayment'])->name('dev.testPaymentCallback');
    // Fetch the most recent DocumentHold reference for dev testing
    Route::get('/_dev/last-reference', [SuperAdminActions::class, 'devLastReference'])->name('dev.lastReference');
}

Route::middleware(['auth', 'verified', 'user.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'user.active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('dashboard')->middleware(['auth', 'user.active'])->group(function () {
    /**User managem related links */
    Route::get('/users', [SuperAdminActions::class, 'user_index'])->name('users.index');
    Route::get('/users/create', [SuperAdminActions::class, 'user_create'])->name('user.create');
    Route::post('/users/create', [SuperAdminActions::class, 'user_store'])->name('user.save');
    Route::get('/users/{user}/edit', [SuperAdminActions::class, 'user_edit'])->name('user.edit');
    Route::get('/users/{user}/view', [SuperAdminActions::class, 'user_show'])->name('user.view');
    Route::put('/users/{user}/edit', [SuperAdminActions::class, 'user_update'])->name('user.update');
    Route::delete('/users/{user}', [SuperAdminActions::class, 'user_destroy'])->name('user.delete');
Route::patch('/users/{user}/deactivate', [SuperAdminActions::class, 'user_deactivate'])->name('user.deactivate');
Route::patch('/users/{user}/activate', [SuperAdminActions::class, 'user_activate'])->name('user.activate');
Route::patch('/users/{user}/verify', [SuperAdminActions::class, 'user_verify'])->name('user.verify');
Route::get('/users/search', [SearchController::class, 'searchUser'])->name('search.user');
    Route::get('/get-departments/{organisationId}', [SuperAdminActions::class, 'getDepartments']);
    Route::get('/upload', [SuperAdminActions::class, 'showUserUploadForm'])->name('userUpload.form');
    Route::post('/upload', [SuperAdminActions::class, 'userUploadCsv'])->name('userUpload.csv');
    Route::get('/document/charge', [SuperAdminActions::class, 'setCharge'])->name('set.charge');
    Route::post('/document/charge', [SuperAdminActions::class, 'storeFileCharge'])->name('store.fileCharge');
    Route::get('/document/charge/{fileCharge}', [SuperAdminActions::class, 'editFileCharge'])->name('edit.fileChargeForm');
    Route::put('/document/charge/{fileCharge}', [SuperAdminActions::class, 'updateFileCharge'])->name('update.fileCharge');
    Route::delete('/document/charge/{fileCharge}', [SuperAdminActions::class, 'deleteFileCharge'])->name('delete.fileCharge');


    Route::get('/superadmin/visitor-activities', [VisitorActivityController::class, 'index'])->name('superadmin.visitor.activities');

    // Superadmin: Tenant Cleanup
    Route::get('/superadmin/tenant-cleanup', [SuperAdminActions::class, 'tenantCleanupForm'])->name('superadmin.tenant.cleanup');
    Route::post('/superadmin/tenant-cleanup', [SuperAdminActions::class, 'tenantCleanupRun'])->name('superadmin.tenant.cleanup.run');

    // Superadmin: Tenant Usage Tracking
    Route::get('/superadmin/tenant-usage', [SuperAdminActions::class, 'tenantUsage'])->name('superadmin.tenant.usage');
    // Superadmin: Tenant Usage Export (CSV)
    Route::get('/superadmin/tenant-usage/export', [SuperAdminActions::class, 'tenantUsageExport'])->name('superadmin.tenant.usage.export');

    
    /**Role management related links */
    Route::get('/roles/index', [SuperAdminActions::class, 'roleIndex'])->name('role.index');
    Route::get('/roles/create', [SuperAdminActions::class, 'roleCreate'])->name('role.create');
    Route::post('/roles/create', [SuperAdminActions::class, 'roleStore'])->name('role.store');

    /**Designation mangement */
    Route::get('/superadmin/designations', [SuperAdminActions::class, 'designationIndex'])->name('designation.index');
    Route::get('/superadmin/designations/create', [SuperAdminActions::class, 'designationCreate'])->name('designation.create');
    Route::post('/superadmin/designations/create', [SuperAdminActions::class, 'designationStore'])->name('designation.store');
    Route::get('/superadmin/designations/data', [SuperAdminActions::class, 'designationsData'])->name('superadmin.designations.data');
    Route::get('/superadmin/designations/{designation}/edit', [SuperAdminActions::class, 'designationEdit'])->name('designation.edit');
    Route::put('/superadmin/designations/{designation}/edit', [SuperAdminActions::class, 'designationUpdate'])->name('designation.update');
    Route::delete('/superadmin/{designation}/delete', [SuperAdminActions::class, 'designationDestroy'])->name('designation.delete');

    /**Organisation Management realated links */
    Route::get('/superadmin/organisations', [SuperAdminActions::class, 'org_index'])->name('organisation.index');
    Route::get('/superadmin/organisations/create', [SuperAdminActions::class, 'org_create'])->name('organisation.create');
    Route::post('/superadmin/organisations/create', [SuperAdminActions::class, 'org_store'])->name('organisation.store');
    Route::get('/superadmin/organisations/{tenant}/edit', [SuperAdminActions::class, 'org_edit'])->name('organisation.edit');
    Route::put('/superadmin/organisations/{tenant}/edit', [SuperAdminActions::class, 'org_update'])->name('organisation.update');
    Route::delete('/superadmin/organisations/{tenant}/delete', [SuperAdminActions::class, 'org_delete'])->name('organisation.delete');
Route::get('/organisations/search', [SearchController::class, 'searchOrg'])->name('search.org');
    Route::get('/superadmin/organisations/{tenant}/departments', [SuperAdminActions::class, 'org_departments'])->name('organisation.departments');


    /**Department Management related links */
    Route::get('/departments', [SuperAdminActions::class, 'department_index'])->name('department.index');
    Route::get('/departments/create', [SuperAdminActions::class, 'department_create'])->name('department.create');
    Route::post('/departments/create', [SuperAdminActions::class, 'department_store'])->name('department.store');
    Route::get('/departments/{department}/edit', [SuperAdminActions::class, 'department_edit'])->name('department.edit');
    Route::put('/departments/{department}/edit', [SuperAdminActions::class, 'department_update'])->name('department.update');
    Route::delete('/departments/{department}/delete', [SuperAdminActions::class, 'department_delete'])->name('department.delete');
    Route::get('/departments/search', [SearchController::class, 'searchDept'])->name('search.dept');


    /**Document management related links */
    Route::get('/document', [SuperAdminActions::class, 'document_index'])->name('document.index');

    // Admin documents DataTables server-side endpoint
    Route::get('/admin/documents/data', [AdminDocumentController::class, 'data'])->name('admin.documents.data');
    Route::get('/admin/documents/received/data', [AdminDocumentController::class, 'receivedData'])->name('admin.documents.received.data');
    Route::get('/admin/documents/sent/data', [AdminDocumentController::class, 'sentData'])->name('admin.documents.sent.data');
    // User documents DataTables server-side endpoints
    Route::get('/user/documents/index/data', [UserDocumentController::class, 'indexData'])->name('user.documents.index.data');
    Route::get('/user/documents/received/data', [UserDocumentController::class, 'receivedData'])->name('user.documents.received.data');
    Route::get('/user/documents/sent/data', [UserDocumentController::class, 'sentData'])->name('user.documents.sent.data');
    // Staff documents DataTables server-side endpoints
    Route::get('/staff/documents/index/data', [\App\Http\Controllers\Staff\DocumentController::class, 'indexData'])->name('staff.documents.index.data');
    Route::get('/staff/documents/received/data', [\App\Http\Controllers\Staff\DocumentController::class, 'receivedData'])->name('staff.documents.received.data');

    // Superadmin DataTables server-side endpoints
    Route::get('/superadmin/departments/data', [SuperAdminActions::class, 'departmentsData'])->name('superadmin.departments.data');
    Route::get('/superadmin/organisations/data', [SuperAdminActions::class, 'organisationsData'])->name('superadmin.organisations.data');
    Route::get('/superadmin/usermanager/data', [SuperAdminActions::class, 'usermanagerData'])->name('superadmin.usermanager.data');
    Route::get('/superadmin/visitor-activity/data', [SuperAdminActions::class, 'visitorActivityData'])->name('superadmin.visitor.activity.data');
    Route::get('/staff/documents/sent/data', [\App\Http\Controllers\Staff\DocumentController::class, 'sentData'])->name('staff.documents.sent.data');
    // Staff memos DataTables server-side endpoints
    Route::get('/staff/memo/index/data', [\App\Http\Controllers\Staff\MemoController::class, 'indexData'])->name('staff.memo.index.data');
    Route::get('/staff/memo/received/data', [\App\Http\Controllers\Staff\MemoController::class, 'receivedData'])->name('staff.memo.received.data');
    Route::get('/staff/memo/sent/data', [\App\Http\Controllers\Staff\MemoController::class, 'sentData'])->name('staff.memo.sent.data');
    // Admin departments DataTables server-side endpoint
    Route::get('/admin/departments/data', [AdminDepartmentController::class, 'data'])->name('admin.departments.data');
    // Admin users DataTables server-side endpoint
    Route::get('/admin/users/data', [AdminUserManagerController::class, 'data'])->name('admin.users.data');
    Route::get('/document/create', [SuperAdminActions::class, 'document_create'])->name('document.create');
    Route::post('/document/create', [SuperAdminActions::class, 'document_store'])->name('document.store');
    Route::get('/document/sent', [SuperAdminActions::class, 'sent_documents'])->name('document.sent');
    Route::get('/document/search', [SearchController::class, 'searchDoc'])->name('search.doc');

    Route::get('/document/search', [SearchController::class, 'searchRecived'])->name('search.received');


    Route::get('/document/received', [SuperAdminActions::class, 'received_documents'])->name('document.received');
    // Route::get('/document/{document}/view', [SuperAdminActions::class, 'viewDocument'])->name('document.view');
    Route::get('/document/{document}/send', [SuperAdminActions::class, 'getSendform'])->name('document.send');
    Route::get('/document/{document}/sendout', [SuperAdminActions::class, 'getSendExternalForm'])->name('document.sendout');
    Route::get('/document/{document}/reply', [SuperAdminActions::class, 'getReplyform'])->name('document.reply');
    Route::post('/document/{document}/send', [SuperAdminActions::class, 'sendDocument'])->name('document.senddoc');
    Route::post('/document/send2admin', [SuperAdminActions::class, 'secSendToAdmin'])->name('document.senddoc2admin');
    Route::get('/document/file/document', [SuperAdminActions::class, 'user_file_document'])->name('document.file');
    Route::get('/document/document/{received}/view', [SuperAdminActions::class, 'document_show'])->name('document.view');
    Route::get('/document/document/{document}/myview', [SuperAdminActions::class, 'myDocument_show'])->name('document.myview');
    Route::get('/document/document/{sent}/view', [SuperAdminActions::class, 'document_show_sent'])->name('document.view_sent');
    Route::post('/document/file/document', [SuperAdminActions::class, 'user_store_file_document'])->name('document.storefile');
    Route::get('/document/{document}/location', [SuperAdminActions::class, 'track_document'])->name('track');
    Route::get('/document/{document}/attachments', [SuperAdminActions::class, 'get_attachments'])->name('getAttachments');
    Route::get('/etranzact/callback', [SuperAdminActions::class, 'handleETranzactCallback'])->name("etranzact.callBack");

    /**Memo management related links */
    Route::get('/document/memo', [SuperAdminActions::class, 'memo_index'])->name('memo.index');
    Route::get('/document/memo/create', [SuperAdminActions::class, 'create_memo'])->name('memo.create');
    Route::post('/document/memo/create', [SuperAdminActions::class, 'store_memo'])->name('memo.store');
    Route::get('/document/memo/{memo}/edit', [SuperAdminActions::class, 'edit_memo'])->name('memo.edit');
    Route::put('/document/memo/{memo}/edit', [SuperAdminActions::class, 'update_memo'])->name('memo.update');
    Route::delete('/document/memo/{memo}/delete', [SuperAdminActions::class, 'delete_memo'])->name('memo.delete');
    Route::get('/document/memo/{memo}/view', [SuperAdminActions::class, 'get_memo'])->name('memo.view');
    Route::get('/generate-letter/{memo}/memo', [SuperAdminActions::class, 'generateMemoPdf'])->name('memo.generate');
    Route::get('/document/memo/template', [SuperAdminActions::class, 'createMemoTemplateForm'])->name('memo.template');
    Route::post('/document/memo/template', action: [SuperAdminActions::class, 'storeMemoTemplate'])->name('memo.template.store');
    Route::get('/document/memo/template/{template}/edit', [SuperAdminActions::class, 'editMemoTemplateForm'])->name('memo.template.edit');
    Route::get('/document/memo/{memo}/send', [SuperAdminActions::class, 'getSendMemoform'])->name('memo.send');
    Route::get('/document/memo/{memo}/sendout', [SuperAdminActions::class, 'getSendMemoExternalForm'])->name('memo.sendout');
    Route::post('/document/memo/{memo}/send', [SuperAdminActions::class, 'sendMemo'])->name('memo.senddoc');
    Route::get('/document/sent/memo', [SuperAdminActions::class, 'sent_memos'])->name('memo.sent');
    Route::get('/document/received/memo', [SuperAdminActions::class, 'received_memos'])->name('memo.received');
    
    Route::get('/document/receipts', [SuperAdminActions::class, 'receipt_index'])->name('receipt.index');
    Route::get('/document/{receipt}/receipt', [SuperadminActions::class, 'show_receipt'])->name('receipt.show');
    Route::get('/download-receipt', [SuperadminActions::class, 'downloadReceipt'])->name('download.receipt');
    // User receipts DataTables server-side endpoint
    Route::get('/document/receipts/data', [UserReceiptController::class, 'indexData'])->name('user.receipts.data');
});

// Folder Management Routes
Route::prefix('dashboard')->middleware(['auth', 'user.active'])->group(function () {
    // Additional folder management routes
    Route::post('/folders/{folder}/move', [FolderController::class, 'move'])->name('folders.move');
    Route::post('/folders/{folder}/share', [FolderController::class, 'share'])->name('folders.share');
    Route::delete('/folders/{folder}/unshare/{user}', [FolderController::class, 'unshare'])->name('folders.unshare');
    Route::get('/folders/{folder}/permissions', [FolderController::class, 'permissions'])->name('folders.permissions');
    Route::put('/folders/{folder}/permissions', [FolderController::class, 'updatePermissions'])->name('folders.update-permissions');
    Route::post('/folders/{folder}/documents', [FolderController::class, 'addDocument'])->name('folders.add-document');
    Route::post('/folders/{folder}/documents/{document}/remove', [FolderController::class, 'removeDocument'])->name('folders.remove-document');
    
    // Main folder resource routes
    Route::resource('folders', FolderController::class);

    // Folder document management routes
    Route::get('/folders/select/{document}', [FolderController::class, 'selectFolder'])->name('folders.select');
    Route::get('/folders/{folder}/add-documents', [FolderController::class, 'showAddDocuments'])->name('folders.show-add-documents');
    Route::post('/folders/{folder}/add-documents', [FolderController::class, 'addDocuments'])->name('folders.add-documents');
    Route::get('/folders/{folder}/documents/{document}', [FolderController::class, 'removeDocument'])->name('folders.remove-document');

    // Backup routes
    Route::get('/superadmin/backup', [BackupLogController::class, 'index'])->name('backup.index');
    Route::get('/superadmin/{backup}/download', [BackupLogController::class, 'download'])->name('backup.download');
    Route::delete('/superadmin/{backup}/delete-backup', [BackupLogController::class, 'destroy'])->name('backup.delete');

    // Spatie database backup management
    Route::post('/superadmin/backups/run', [BackupLogController::class, 'runDatabaseBackup'])->name('superadmin.backups.run');
    Route::post('/superadmin/backups/clean', [BackupLogController::class, 'cleanDatabaseBackups'])->name('superadmin.backups.clean');
    
    // Async triggers for custom backups (time-restricted)
    Route::post('/superadmin/backups/visitor-run', [BackupLogController::class, 'runVisitorActivityBackup'])->name('superadmin.backups.visitor');
    Route::post('/superadmin/backups/weekly-incremental', [BackupLogController::class, 'runWeeklyIncrementalBackup'])->name('superadmin.backups.weekly_incremental');
    Route::post('/superadmin/backups/monitor', [BackupLogController::class, 'monitorBackups'])->name('superadmin.backups.monitor');
    Route::get('/superadmin/backups/{disk}/download/{path}', [BackupLogController::class, 'downloadDatabaseBackup'])
        ->where('path', '.*')
        ->name('superadmin.backups.download');
    Route::delete('/superadmin/backups/{disk}/delete/{path}', [BackupLogController::class, 'deleteDatabaseBackup'])
        ->where('path', '.*')
        ->name('superadmin.backups.delete');
});

require __DIR__ . '/auth.php';

// Local-only email preview routes for development/testing
// Local-only preview of Superadmin Usage view
if (env('APP_ENV') !== 'production') {
    Route::get('/_preview/superadmin/usage', function () {
        // Minimal stub data to render the view structure
        $authUser = (object) ['name' => 'Superadmin'];
        $userTenant = null;
        $tenants = collect([
            (object) ['id' => 1, 'name' => 'Demo Tenant A'],
            (object) ['id' => 2, 'name' => 'Demo Tenant B'],
        ]);
        $selectedTenantId = 1;
        $from = now()->subDays(14)->toDateString();
        $to = now()->toDateString();
        $userCount = 42;
        $totals = ['sent' => 120, 'received' => 98];
        $dailySeries = ['labels' => [], 'sent' => [], 'received' => []];
        $monthlySeries = ['labels' => ['2025-09','2025-10'], 'sent' => [300, 280], 'received' => [250, 260]];
        $recent = collect();

        return view('superadmin.usage.index', compact(
            'authUser','userTenant','tenants','selectedTenantId','from','to','userCount','totals','dailySeries','monthlySeries','recent'
        ));
    });
}
if (app()->environment('local')) {
    Route::get('/_preview/email/receive', function () {
        return new \App\Mail\ReceiveNotificationMail(
            'Sender Example',
            'Receiver Example',
            'Test Document',
            'DOC-123',
            config('app.name')
        );
    });
    Route::get('/_preview/email/send', function () {
        return new \App\Mail\SendNotificationMail(
            'Sender Example',
            'Receiver Example',
            'Project Plan',
            config('app.name'),
            '12345',
            'Planning Department',
            'Benue State ICT'
        );
    });
    Route::get('/_preview/superadmin/backups', function () {
        $authUser = (object) ['name' => 'Superadmin', 'default_role' => 'superadmin'];
        $userTenant = null;
        $backups = collect();
        $spatieBackups = [
            [
                'disk' => 'local',
                'path' => 'dummy/dms-2025-10-01-020000.zip',
                'filename' => 'dms-2025-10-01-020000.zip',
                'size' => 1024 * 1024 * 10,
                'last_modified' => now()->subDays(5)->getTimestamp(),
            ],
        ];
        return view('superadmin.backups', compact('authUser','userTenant','backups','spatieBackups'));
    });
    Route::get('/_preview/user/sent', function () {
        $sent_documents = collect();
        $mda = null;
        return view('user.documents.sent', compact('sent_documents','mda'));
    });
}
