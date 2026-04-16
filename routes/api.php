<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactAPIController;
use App\Http\Controllers\Api\DashboardAPIController;
use App\Http\Controllers\Api\FileChargeAPIController;
use App\Http\Controllers\Api\FolderAPIController;
use App\Http\Controllers\Api\ProfileAPIController;
use App\Http\Controllers\Api\SuperAdminAPIController;
use App\Http\Controllers\ConversationsController;
use App\Http\Controllers\MessageController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the API',
    ]);
});

// Ministries route
Route::get('/ministries', function () {
    $ministries = Tenant::where('category', 'Ministry')->paginate(10);
    return response()->json([
        'ministries' => $ministries,
    ]);
});

// Agencies route
Route::get('/agencies', function () {
    $agencies = Tenant::where('category', 'Agency')->paginate(10);
    return response()->json([
        'agencies' => $agencies,
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Contact routes (public)
Route::post('/contact', [ContactAPIController::class, 'submit']);
Route::get('/contact/info', [ContactAPIController::class, 'getContactInfo']);

// Payment callback routes must remain public for gateway callbacks
Route::get('/etranzact/callback', [SuperAdminAPIController::class, 'handleETranzactCallback'])
    ->name('api.etranzact.callback');
Route::get('/dashboard/etranzact/callback', [SuperAdminAPIController::class, 'handleETranzactCallback'])
    ->name('api.dashboard.etranzact.callback');

Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
    Route::get('/dashboard', [DashboardAPIController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/session/check', [SuperAdminAPIController::class, 'checkSession']);

    Route::get('/profile', [ProfileAPIController::class, 'show']);
    Route::put('/profile', [ProfileAPIController::class, 'update']);
    Route::post('/profile/change-password', [ProfileAPIController::class, 'changePassword']);
    Route::post('/profile/upload-avatar', [ProfileAPIController::class, 'uploadAvatar']);

    Route::prefix('dashboard')->group(function () {
    /** User management */
    Route::get('/users', [SuperAdminAPIController::class, 'userIndex']);
    Route::get('/users/create', [SuperAdminAPIController::class, 'userCreate']);
    Route::post('/users/create', [SuperAdminAPIController::class, 'userStore']);
    Route::get('/users/{user}/edit', [SuperAdminAPIController::class, 'userEdit']);
    Route::get('/users/{user}/view', [SuperAdminAPIController::class, 'userShow']);
    Route::put('/users/{user}/edit', [SuperAdminAPIController::class, 'userUpdate']);
    Route::delete('/users/{user}', [SuperAdminAPIController::class, 'userDelete']);
    Route::patch('/users/{user}/deactivate', [SuperAdminAPIController::class, 'deactivateUser']);
    Route::patch('/users/{user}/activate', [SuperAdminAPIController::class, 'activateUser']);
    Route::patch('/users/{user}/verify', [SuperAdminAPIController::class, 'userVerify']);
    Route::get('/get-departments/{organisationId}', [SuperAdminAPIController::class, 'getDepartments']);

    /** Superadmin user actions aliases */
    Route::patch('/superadmin/users/{user}/deactivate', [SuperAdminAPIController::class, 'deactivateUser']);
    Route::patch('/superadmin/users/{user}/activate', [SuperAdminAPIController::class, 'activateUser']);
    Route::patch('/superadmin/users/{user}/verify', [SuperAdminAPIController::class, 'userVerify']);

    /** Organisation management */
    Route::get('/superadmin/organisations', [SuperAdminAPIController::class, 'orgIndex']);
    Route::get('/superadmin/organisations/create', [SuperAdminAPIController::class, 'orgCreate']);
    Route::post('/superadmin/organisations/create', [SuperAdminAPIController::class, 'orgStore']);
    Route::get('/superadmin/organisations/{tenant}/edit', [SuperAdminAPIController::class, 'orgEdit']);
    Route::put('/superadmin/organisations/{tenant}/edit', [SuperAdminAPIController::class, 'orgUpdate']);
    Route::delete('/superadmin/organisations/{tenant}/delete', [SuperAdminAPIController::class, 'orgDelete']);

    /** Department management */
    Route::get('/departments', [SuperAdminAPIController::class, 'departmentIndex']);
    Route::get('/departments/create', [SuperAdminAPIController::class, 'departmentCreate']);
    Route::post('/departments/create', [SuperAdminAPIController::class, 'departmentStore']);
    Route::get('/departments/{department}/edit', [SuperAdminAPIController::class, 'departmentEdit']);
    Route::put('/departments/{department}/edit', [SuperAdminAPIController::class, 'departmentUpdate']);
    Route::delete('/departments/{department}/delete', [SuperAdminAPIController::class, 'departmentDelete']);

    /** Document management */
    Route::get('/document', [SuperAdminAPIController::class, 'documentIndex']);
    Route::get('/document/create', [SuperAdminAPIController::class, 'documentCreate']);
    Route::post('/document/create', [SuperAdminAPIController::class, 'documentStore']);
    Route::get('/document/sent', [SuperAdminAPIController::class, 'sentDocuments']);
    Route::get('/document/received', [SuperAdminAPIController::class, 'receivedDocuments']);
    Route::get('/document/{document}/send', [SuperAdminAPIController::class, 'getSendform']);
    Route::get('/document/{document}/sendout', [SuperAdminAPIController::class, 'getSendExternalForm']);
    Route::get('/document/{document}/reply', [SuperAdminAPIController::class, 'getReplyform']);
    Route::post('/document/{document}/send', [SuperAdminAPIController::class, 'sendDocument']);
    Route::post('/document/send2admin', [SuperAdminAPIController::class, 'secSendToAdmin']);
    Route::get('/document/file/document', [SuperAdminAPIController::class, 'userFileDocument']);
    Route::get('/document/document/{received}/view', [SuperAdminAPIController::class, 'documentShow']);
    Route::get('/document/document/{sent}/view', [SuperAdminAPIController::class, 'documentShowSent']);

    // Upload-first filing flow
    Route::post('/document/upload', [SuperAdminAPIController::class, 'uploadDocument']);
    Route::get('/document/uploads', [SuperAdminAPIController::class, 'listUploadedDocuments']);
    Route::get('/document/uploads/{hold}/pay', [SuperAdminAPIController::class, 'initiatePayment'])
        ->name('api.dashboard.document.uploads.pay');
    Route::post('/document/uploads/{document}/send', [SuperAdminAPIController::class, 'sendUploadedDocument']);

    // Legacy upload endpoint
    Route::post('/document/file/document', [SuperAdminAPIController::class, 'userStoreFileDocument']);
    Route::get('/document/{document}/location', [SuperAdminAPIController::class, 'trackDocument']);
    Route::get('/document/{document}/attachments', [SuperAdminAPIController::class, 'getAttachments']);

    /** Memo management */
    Route::get('/document/memo', [SuperAdminAPIController::class, 'memoIndex']);
    Route::get('/document/memo/create', [SuperAdminAPIController::class, 'createMemo']);
    Route::post('/document/memo/create', [SuperAdminAPIController::class, 'storeMemo']);
    Route::get('/document/memo/{memo}/edit', [SuperAdminAPIController::class, 'editMemo']);
    Route::put('/document/memo/{memo}/edit', [SuperAdminAPIController::class, 'updateMemo']);
    Route::delete('/document/memo/{memo}/delete', [SuperAdminAPIController::class, 'deleteMemo']);
    Route::get('/document/memo/{memo}/view', [SuperAdminAPIController::class, 'getMemo']);
    Route::get('/generate-letter/{memo}/memo', [SuperAdminAPIController::class, 'generateMemoPdf']);
    Route::get('/document/memo/template', [SuperAdminAPIController::class, 'createMemoTemplateForm']);
    Route::post('/document/memo/template', [SuperAdminAPIController::class, 'storeMemoTemplate']);
    Route::get('/document/memo/template/{template}/edit', [SuperAdminAPIController::class, 'editMemoTemplateForm']);
    Route::get('/document/memo/{memo}/send', [SuperAdminAPIController::class, 'getSendMemoform']);
    Route::get('/document/memo/{memo}/sendout', [SuperAdminAPIController::class, 'getSendMemoExternalForm']);
    Route::post('/document/memo/{memo}/send', [SuperAdminAPIController::class, 'sendMemo']);
    Route::get('/document/sent/memo', [SuperAdminAPIController::class, 'sentMemos']);
    Route::get('/document/received/memo', [SuperAdminAPIController::class, 'receivedMemos']);

    /** Receipt management */
    Route::get('/document/receipts', [SuperAdminAPIController::class, 'receiptIndex']);
    Route::get('/document/{receipt}/receipt', [SuperAdminAPIController::class, 'showReceipt']);

    /** Role management */
    Route::get('/roles', [SuperAdminAPIController::class, 'roleIndex']);
    Route::post('/roles', [SuperAdminAPIController::class, 'roleStore']);
    Route::get('/roles/{role}/edit', [SuperAdminAPIController::class, 'roleEdit']);
    Route::put('/roles/{role}', [SuperAdminAPIController::class, 'roleUpdate']);
    Route::delete('/roles/{role}', [SuperAdminAPIController::class, 'roleDelete']);

    /** Designation management */
    Route::get('/designations', [SuperAdminAPIController::class, 'designationIndex']);
    Route::post('/designations', [SuperAdminAPIController::class, 'designationStore']);
    Route::get('/designations/{designation}/edit', [SuperAdminAPIController::class, 'designationEdit']);
    Route::put('/designations/{designation}', [SuperAdminAPIController::class, 'designationUpdate']);
    Route::delete('/designations/{designation}', [SuperAdminAPIController::class, 'designationDelete']);

    /** Search routes */
    Route::get('/search/users', [SuperAdminAPIController::class, 'searchUsers']);
    Route::get('/search/organisations', [SuperAdminAPIController::class, 'searchOrganisations']);
    Route::get('/search/departments', [SuperAdminAPIController::class, 'searchDepartments']);
    Route::get('/search/documents', [SuperAdminAPIController::class, 'searchDocuments']);

    /** Visitor activities */
    Route::get('/visitor-activities', [SuperAdminAPIController::class, 'visitorActivitiesIndex']);
    Route::get('/visitor-activities/{visitorActivity}', [SuperAdminAPIController::class, 'visitorActivitiesShow']);

    /** Tenant usage analytics */
    Route::get('/superadmin/tenant-usage', [SuperAdminAPIController::class, 'tenantUsage']);
    Route::get('/superadmin/tenant-usage/export', [SuperAdminAPIController::class, 'tenantUsageExport']);

    /** File charge management */
    Route::apiResource('file-charges', FileChargeAPIController::class);
    Route::get('/file-charges/active/list', [FileChargeAPIController::class, 'getActiveCharges']);
    Route::patch('/file-charges/{fileCharge}/toggle-status', [FileChargeAPIController::class, 'toggleStatus']);

    /** Organisation department view */
    Route::get('/organisations/{organisationId}/departments', [SuperAdminAPIController::class, 'getOrganisationDepartments']);

    /** Conversations and messages */
    Route::get('/conversations', [ConversationsController::class, 'index']);
    Route::post('/conversations', [ConversationsController::class, 'store']);
    Route::get('/conversations/members/search', [ConversationsController::class, 'searchMembers']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::post('/conversations/{conversation}/messages/read', [MessageController::class, 'markRead']);

    /** Folder management */
    Route::get('/folders', [FolderAPIController::class, 'index'])->name('api.folders.list');
    Route::post('/folders', [FolderAPIController::class, 'store'])->name('api.folders.create');
    Route::get('/folders/{folder}', [FolderAPIController::class, 'show'])->name('api.folders.view');
    Route::put('/folders/{folder}', [FolderAPIController::class, 'update'])->name('api.folders.edit');
    Route::delete('/folders/{folder}', [FolderAPIController::class, 'destroy'])->name('api.folders.delete');
    Route::post('/folders/{folder}/move', [FolderAPIController::class, 'move'])->name('api.folders.move');
    Route::get('/folders/{folder}/permissions', [FolderAPIController::class, 'permissions'])->name('api.folders.permissions');
    Route::put('/folders/{folder}/permissions', [FolderAPIController::class, 'updatePermissions'])->name('api.folders.update-permissions');
    Route::post('/folders/{folder}/share', [FolderAPIController::class, 'share'])->name('api.folders.share');
    Route::delete('/folders/{folder}/unshare', [FolderAPIController::class, 'unshare'])->name('api.folders.unshare');
    Route::post('/folders/{folder}/documents', [FolderAPIController::class, 'addDocument'])->name('api.folders.attach-document');
    Route::post('/folders/{folder}/add-documents', [FolderAPIController::class, 'addDocuments'])->name('api.folders.add-documents');
    Route::delete('/folders/{folder}/documents/{document}', [FolderAPIController::class, 'removeDocument'])->name('api.folders.detach-document');
    });
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
