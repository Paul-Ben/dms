<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\DocumentStorage;
use App\Helpers\FileService;
use App\Helpers\SendMailHelper;
use App\Helpers\StampHelper;
use App\Helpers\UserAction;
use App\Models\Designation;
use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\FileMovement;
use App\Models\Tenant;
use App\Models\TenantDepartment;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendNotificationMail;
use App\Mail\ReceiveNotificationMail;
use App\Models\Activity;
use App\Models\Attachments;
use App\Models\DocumentHold;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\Payment;
use Exception;
use setasign\Fpdi\Fpdi;

class SuperAdminAPIController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // User Management Endpoints

    public function userIndex()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $users = User::orderBy('id', 'desc')->paginate(5);
            return response()->json(['users' => $users], 200);
        }

        if ($authUser->default_role === 'Admin') {
            $id = $authUser->userDetail->tenant_id;
            $users = UserDetails::with('user')->where('tenant_id', $id)->get();
            return response()->json(['users' => $users], 200);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function userCreate()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();
            return response()->json([
                'organisations' => $organisations,
                'roles' => $roles,
                'departments' => $departments,
                'designations' => $designations
            ], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $id = $authUser->userDetail->tenant_id;
            $departments = TenantDepartment::where('tenant_id', $id)->get();
            $designations = Designation::all();
            $roles = Role::whereNotIn('name', [$authUser->default_role, 'superadmin', 'User'])->get();
            return response()->json([
                'departments' => $departments,
                'designations' => $designations,
                'roles' => $roles
            ], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function getDepartments($organisationId)
    {
        $departments = TenantDepartment::where('tenant_id', $organisationId)->get();
        return response()->json(['departments' => $departments], 200);
    }

    public function userStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->default_role = $request->input('default_role');
        $user->save();

        if ($request->input('default_role') === 'Admin') {
            $user->assignRole('Admin');
        }
        if ($request->input('default_role') === 'Staff') {
            $user->assignRole('Staff');
        }
        if ($request->input('default_role') === 'User') {
            $user->assignRole('User');
        }

        $user->userDetail()->create([
            'user_id' => $user->id,
            'department_id' => $request->input('department_id'),
            'tenant_id' => $request->input('tenant_id'),
            'phone_number' => $request->input('phone_number'),
            'designation' => $request->input('designation'),
            'avatar' => $request->input('avatar'),
            'signature' => $request->input('signature'),
            'nin_number' => $request->input('nin_number'),
        ]);

        return response()->json(['message' => 'User created successfully'], 201);
    }

    public function userEdit(User $user)
    {
        try {
            $authUser = Auth::user();
            if ($authUser->default_role === 'superadmin') {
                $user_details = User::with('userDetail')->where('id', $user->id)->first();
                list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();
                $organisationName = optional($user->userDetail)->tenant->name;
                $tenantDepartments = TenantDepartment::all();
                return response()->json([
                    'user' => $user,
                    'roles' => $roles,
                    'organisations' => $organisations,
                    'organisationName' => $organisationName,
                    'tenantDepartments' => $tenantDepartments,
                    'departments' => $departments,
                    'designations' => $designations,
                    'user_details' => $user_details
                ], 200);
            }
            if ($authUser->default_role === 'Admin') {
                $user_details = User::with('userDetail')->where('id', $user->id)->first();
                list($organisations, $roles, $departments, $designations) = UserAction::getOrganisationDetails();
                $organisationName = optional($authUser->userDetail)->tenant->name;
                $tenantDepartments = TenantDepartment::where('tenant_id', optional($authUser->userDetail)->tenant_id)->get();
                return response()->json([
                    'user' => $user,
                    'roles' => $roles,
                    'organisations' => $organisations,
                    'organisationName' => $organisationName,
                    'tenantDepartments' => $tenantDepartments,
                    'designations' => $designations,
                    'user_details' => $user_details
                ], 200);
            }
            return response()->json(['error' => 'Unauthorized'], 403);
        } catch (\Exception $e) {
            Log::error('Error while fetching user details: ' . $e->getMessage());
            return response()->json(['error' => 'Error while fetching user details'], 500);
        }
    }

    public function userUpdate(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'nin_number' => 'sometimes|string',
            'phone_number' => 'sometimes|string',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'default_role' => 'required|string',
            'designation' => 'required|string',
            'tenant_id' => 'required|integer',
            'department_id' => 'required|integer',
            'gender' => 'required|string',
            'signature' => 'sometimes|string',
        ]);

        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'default_role' => $request->input('default_role'),
        ]);

        if ($request->input('password') != $user->password) {
            $user->update([
                'password' => Hash::make($request->input('password')),
            ]);
        }

        $userDetail = $user->userDetail;

        if (!$userDetail) {
            $userDetail = new UserDetails();
            $userDetail->user_id = $user->id;
        }

        $userDetail->update([
            'nin_number' => $request->input('nin_number'),
            'phone_number' => $request->input('phone_number'),
            'designation' => $request->input('designation'),
            'tenant_id' => $request->input('tenant_id'),
            'department_id' => $request->input('department_id'),
            'gender' => $request->input('gender'),
            'signature' => $request->input('signature'),
        ]);

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
            $avatar->storeAs('public/avatars', $avatarName);
            $userDetail->update([
                'avatar' => $avatarName,
            ]);
        }

        return response()->json(['message' => 'User updated successfully'], 200);
    }

    public function userDelete(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function deactivateUser(User $user)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->id === $authUser->id) {
            return response()->json(['error' => 'You cannot deactivate your own account'], 400);
        }

        $user->update(['is_active' => false]);
        
        // Log the activity
        Activity::create([
            'action' => 'User Deactivated',
            'user_id' => $authUser->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully'
        ], 200);
    }

    public function activateUser(User $user)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user->update(['is_active' => true]);
        
        // Log the activity
        Activity::create([
            'action' => 'User Activated',
            'user_id' => $authUser->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully'
        ], 200);
    }

    // Organisation Management Endpoints

    public function orgIndex()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $organisations = Tenant::orderBy('id', 'desc')->paginate(10);
            return response()->json(['organisations' => $organisations], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function orgCreate()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            return response()->json([], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function orgStore(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255|unique:tenants',
                'email' => 'required|string|email|max:255|unique:tenants',
                'phone' => 'nullable|string|max:255',
                'category' => 'required|string',
                'address' => 'nullable|string',
                'status' => 'required|string',
            ]);

            $tenant = new Tenant();
            $tenant->name = $request->input('name');
            $tenant->code = $request->input('code');
            $tenant->email = $request->input('email');
            $tenant->phone = $request->input('phone');
            $tenant->category = $request->input('category');
            $tenant->address = $request->input('address');
            $tenant->status = $request->input('status');
            $tenant->save();

            return response()->json(['message' => 'Organisation created successfully'], 201);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function orgEdit(Tenant $tenant)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            return response()->json(['tenant' => $tenant], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function orgUpdate(Request $request, Tenant $tenant)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:255|unique:tenants,code,' . $tenant->id,
                'email' => 'required|string|email|max:255|unique:tenants,email,' . $tenant->id,
                'phone' => 'nullable|string|max:255',
                'category' => 'required|string',
                'address' => 'nullable|string',
                'status' => 'required|string',
            ]);
            $tenant->name = $request->input('name');
            $tenant->code = $request->input('code');
            $tenant->email = $request->input('email');
            $tenant->phone = $request->input('phone');
            $tenant->category = $request->input('category');
            $tenant->address = $request->input('address');
            $tenant->status = $request->input('status');
            $tenant->save();
            return response()->json(['message' => 'Organisation updated successfully'], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function orgDelete(Tenant $tenant)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $tenant->delete();
            return response()->json(['message' => 'Organisation deleted successfully'], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Document Management Endpoints

    public function documentIndex()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $documents = DocumentStorage::myDocuments();
            return response()->json(['documents' => $documents], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $documents = DocumentStorage::myDocuments();
            $tenantName = optional($authUser->userDetail)->tenant->name;
            return response()->json(['documents' => $documents, 'tenantName' => $tenantName], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            $documents = DocumentStorage::myDocuments();
            return response()->json(['documents' => $documents], 200);
        }
        if ($authUser->default_role === 'User') {
            $documents = DocumentStorage::myDocuments();
            return response()->json(['documents' => $documents], 200);
        }
        if ($authUser->default_role === 'Staff') {
            $documents = DocumentStorage::myDocuments();
            return response()->json(['documents' => $documents], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function documentCreate()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            return response()->json([], 200);
        }
        if ($authUser->default_role === 'Admin') {
            return response()->json([], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            return response()->json([], 200);
        }
        if ($authUser->default_role === 'User') {
            return response()->json([], 200);
        }
        if ($authUser->default_role === 'Staff') {
            return response()->json([], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function userFileDocument()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'User') {
            $recipients = DocumentStorage::getUserRecipients();
            return response()->json(['recipients' => $recipients], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function userStoreFileDocument(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'file_path' => 'required|mimes:pdf|max:2048', // PDF file, max 2MB
            'uploaded_by' => 'required|exists:users,id',
            'status' => 'nullable|in:pending,processing,approved,rejected,kiv,completed',
            'description' => 'nullable|string',
            'recipient_id' => 'required|exists:users,id',
            'metadata' => 'nullable|json',
        ]);

        if ($request->hasFile('file_path')) {
            $filePath = $request->file('file_path');
            $filename = time() . '_' . $filePath->getClientOriginalName();
            $file_path = $filePath->move(public_path('documents/'), $filename);
            $file = $request->merge(['file_path' => $filename]);
        }

        $reference = Str::random(12);
        $amount = 3000;
        $documentHold = DocumentHold::create([
            'title' => $request->title,
            'docuent_number' => $request->document_number,
            'file_path' => $filename,
            'uploaded_by' => Auth::user()->id,
            'status' => $request->status ?? 'pending',
            'description' => $request->description,
            'reference' => $reference,
            'amount' => $amount,
            'recipient_id' => $request->recipient_id,
            'metadata' => json_encode($request->metadata),
        ]);

        $authUser = Auth::user();

        try {
            $response = Http::accept('application/json')->withHeaders([
                'authorization' => env('CREDO_PUBLIC_KEY'),
                'content_type' => "Content-Type: application/json",
            ])->post(env("CREDO_URL") . "/transaction/initialize", [
                "email" => $authUser->email,
                "amount" => ($amount * 100),
                "reference" => $reference,
                "callbackUrl" => route("etranzact.callBack"),
                "bearer" => 0,
            ]);

            $responseData = $response->collect("data");

            if (isset($responseData['authorizationUrl'])) {
                return response()->json(['authorizationUrl' => $responseData['authorizationUrl']], 200);
            }

            return response()->json(['error' => 'Credo E-Tranzact gateway service took too long to respond.'], 500);
        } catch (\Exception $e) {
            report($e);
            Log::error('Error initializing payment gateway: ' . $e->getMessage());
            return response()->json(['error' => 'Error initializing payment gateway. Please try again.'], 500);
        }
    }

    public function handleETranzactCallback(Request $request)
    {
        $response = Http::accept('application/json')->withHeaders([
            'authorization' => env('CREDO_SECRET_KEY'),
            'content-type' => 'application/json',
        ])->get(env('CREDO_URL') . "/transaction/{$request->reference}/verify");

        if (!$response->successful()) {
            return response()->json(['error' => 'Payment verification failed. Please try again.'], 500);
        }

        $payment = $response->json('data');
        $status = $payment['status'];
        $message = $payment['statusMessage'] == 'Successfully processed' ? 'Successful' : 'Failed';

        if ($message == 'Successful') {
            $recipient_id = DocumentHold::where('reference', $request->reference)->first()->recipient_id;
            $tenant_id = User::with('userDetail')->where('id', $recipient_id)->first()->userDetail->tenant_id;

            Payment::create([
                'businessName' => $payment['businessName'],
                'reference' => $payment['businessRef'],
                'transAmount' => $payment['transAmount'],
                'transFee' => $payment['transFeeAmount'],
                'transTotal' => $payment['debitedAmount'],
                'transDate' => $payment['transactionDate'],
                'settlementAmount' => $payment['settlementAmount'],
                'status' => $payment['status'],
                'statusMessage' => $payment['statusMessage'],
                'customerEmail' => $payment['customerId'],
                'customerId' => Auth::id(),
                'channelId' => $payment['channelId'],
                'currencyCode' => $payment['currencyCode'],
                'recipient_id' => $recipient_id,
                'tenant_id' => $tenant_id,
            ]);

            return $this->handleSuccessfulPayment($request->reference);
        }

        return response()->json(['error' => 'Payment failed. Please try again.'], 500);
    }

    protected function handleSuccessfulPayment($reference)
    {
        $document = DocumentHold::where('reference', $reference)->first();

        if (!$document) {
            return response()->json(['error' => 'Document not found.'], 404);
        }

        $document->status = 'Successful';
        $document->save();

        $newDocument = Document::create([
            'title' => $document->title,
            'docuent_number' => $document->docuent_number,
            'file_path' => $document->file_path,
            'uploaded_by' => $document->uploaded_by,
            'status' => 'pending',
            'description' => $document->description,
            'metadata' => json_encode($document->metadata),
        ]);

        Activity::create([
            'action' => 'You uploaded a document',
            'user_id' => Auth::id(),
        ]);

        $fileMovement = FileMovement::create([
            'recipient_id' => $document->recipient_id,
            'sender_id' => Auth::id(),
            'message' => $document->description,
            'document_id' => $newDocument->id,
        ]);

        DocumentRecipient::create([
            'file_movement_id' => $fileMovement->id,
            'recipient_id' => $document->recipient_id,
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]);

        Activity::insert([
            [
                'action' => 'Sent Document',
                'user_id' => Auth::id(),
                'created_at' => now(),
            ],
            [
                'action' => 'Document Received',
                'user_id' => $document->recipient_id,
                'created_at' => now(),
            ],
        ]);

        $senderName = Auth::user()->name;
        $receiverName = User::find($document->recipient_id)->name;
        $documentName = $document->title;
        $documentId = $document->docuent_number;
        $appName = config('app.name');

        try {
            Mail::to(Auth::user()->email)->send(new SendNotificationMail(
                $senderName,
                $receiverName,
                $documentName,
                $appName,
                $documentId
            ));
            Mail::to(User::find($document->recipient_id)?->email)->send(new ReceiveNotificationMail($senderName, $receiverName, $documentName, $documentId, $appName));
        } catch (\Exception $e) {
            Log::error('Failed to send Document notification');
        }

        return response()->json(['message' => 'Document uploaded and sent successfully.'], 200);
    }

    public function documentShow($received, Document $document)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $received)->first();
            return response()->json(['document_received' => $document_received], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->get();
            return response()->json(['document_received' => $document_received, 'document_locations' => $document_locations], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->get();
            return response()->json(['document_received' => $document_received, 'document_locations' => $document_locations], 200);
        }
        if ($authUser->default_role === 'User') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();
            return response()->json(['document_received' => $document_received], 200);
        }
        if ($authUser->default_role === 'Staff') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document', 'attachments'])->where('id', $received)->first();
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document_received->document_id)->get();
            return response()->json(['document_received' => $document_received, 'document_locations' => $document_locations], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function documentShowSent($sent)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();
            return response()->json(['document_received' => $document_received], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();
            return response()->json(['document_received' => $document_received], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();
            return response()->json(['document_received' => $document_received], 200);
        }
        if ($authUser->default_role === 'User') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();
            return response()->json(['document_received' => $document_received], 200);
        }
        if ($authUser->default_role === 'Staff') {
            $document_received =  FileMovement::with(['sender', 'recipient', 'document'])->where('id', $sent)->first();
            return response()->json(['document_received' => $document_received], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function documentStore(Request $request)
    {
        $data = $request;

        $result = DocumentStorage::storeDocument($data);

        if ($result['status'] === 'error') {
            return response()->json(['errors' => $result['errors']], 400);
        }

        return response()->json(['message' => 'Document uploaded successfully'], 200);
    }

    public function sentDocuments()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                $mda = collect();
            }
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient, 'mda' => $mda], 200);
        }
        if ($authUser->default_role === 'Admin') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient], 200);
        }
        if ($authUser->default_role === 'User') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                $mda = collect();
            }
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient, 'mda' => $mda], 200);
        }
        if ($authUser->default_role === 'Staff') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with(['tenant', 'tenant_department'])->where('id', $recipient[0]->id)->get();
            } else {
                $mda = collect();
            }
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient, 'mda' => $mda], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function receivedDocuments()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        if ($authUser->default_role === 'Admin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        if ($authUser->default_role === 'User') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        if ($authUser->default_role === 'Staff') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // public function viewDocument(Document $document)
    // {
    //     $tenantId = $document->tenant_id;
    //     $departmentId = $document->department_id;
    //     $filePath = $document->file_path;

    //     $file = public_path('documents/' . $tenantId . '/' . $departmentId . '/' . $filePath);

    //     if (file_exists($file)) {
    //         return response()->file($file, [
    //             'Content-Disposition' => 'inline; filename="' . basename($file) . '"',
    //             'Content-Type' => 'application/pdf', // Change this based on your file type
    //         ]);
    //     }

    //     return response()->json(['error' => 'File not found'], 404);
    // }

    public function getReplyform(Request $request, Document $document)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'Admin') {
            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();
            return response()->json(['recipients' => $recipients, 'document' => $document], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();
            return response()->json(['recipients' => $recipients, 'document' => $document], 200);
        }
        if ($authUser->default_role === 'Staff') {
            $getter = FileMovement::where('document_id', $document->id)->where('recipient_id', $authUser->id)->get();
            $recipients = User::where('id', $getter[0]->sender_id)->get();
            return response()->json(['recipients' => $recipients, 'document' => $document], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function getSendExternalForm(Request $request, Document $document)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'Admin') {
            $recipients = User::with(['userDetail.tenant' => function ($query) {
                $query->select('id', 'name');
            }])
                ->where('default_role', 'Admin')
                ->get();

            if ($recipients->isEmpty()) {
                return response()->json(['error' => 'No recipients found.'], 404);
            }
            return response()->json(['recipients' => $recipients, 'document' => $document], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function getSendform(Request $request, Document $document)
    {
        $authUser = Auth::user();
        $role = $authUser->default_role;

        switch ($role) {
            case 'superadmin':
                $recipients = User::all();
                return response()->json(['recipients' => $recipients, 'document' => $document], 200);

            case 'Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    return response()->json(['error' => 'Tenant information is missing.'], 400);
                }

                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name');
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return response()->json(['error' => 'No recipients found.'], 404);
                }

                return response()->json(['recipients' => $recipients, 'document' => $document, 'document_locations' => $document_locations], 200);

            case 'User':
                $recipients = User::where('default_role', 'Admin')->get();
                return response()->json(['recipients' => $recipients, 'document' => $document], 200);

            case 'Staff':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
                if (!$tenantId) {
                    return response()->json(['error' => 'Tenant information is missing.'], 400);
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name');
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return response()->json(['error' => 'No recipients found.'], 404);
                }

                return response()->json(['recipients' => $recipients, 'document' => $document, 'document_locations' => $document_locations], 200);

            case 'Secretary':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();

                if (!$tenantId) {
                    return response()->json(['error' => 'Tenant information is missing.'], 400);
                }

                $recipients = User::with(['userDetail' => function ($query) {
                    $query->select('id', 'user_id', 'designation', 'tenant_id');
                }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return response()->json(['error' => 'No recipients found.'], 404);
                }

                return response()->json(['recipients' => $recipients, 'document' => $document, 'document_locations' => $document_locations], 200);

            default:
                return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function sendDocument(Request $request)
    {
        $data = $request;
        $result = DocumentStorage::sendDocument($data);
        if ($result['status'] === 'error') {
            return response()->json(['errors' => $result['errors']], 400);
        }
        try {
            SendMailHelper::sendNotificationMail($data, $request);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return response()->json(['message' => 'Document was processed, but notification email failed.'], 500);
        }

        return response()->json(['message' => 'Document sent successfully'], 200);
    }

    public function secSendToAdmin(Request $request, Document $document)
    {
        $validated = $request->validate([
            'document_data' => 'required|json',
        ]);

        $documentData = json_decode($validated['document_data'], true);

        $documentID = $documentData['document_id'];
        if (!isset($documentData['document']['id'], $documentData['sender']['name'], $documentData['recipient']['name'])) {
            return response()->json(['error' => 'Invalid document data provided.'], 400);
        }

        $authUser = Auth::user();
        $tenantId = $authUser->userDetail->tenant_id ?? null;

        if (!$tenantId) {
            return response()->json(['error' => 'You are not assigned to any tenant.'], 400);
        }

        $recipient = User::with('userDetail')
            ->where('default_role', 'Admin')
            ->whereHas('userDetail', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->get();

        if ($recipient->isEmpty()) {
            return response()->json(['error' => 'No admin users found for the tenant.'], 404);
        }

        $stamp = StampHelper::stampIncomingMail($documentID);
        $result = DocumentStorage::reviewedDocument($documentData, $recipient);

        try {
            SendMailHelper::sendReviewNotificationMail($documentData, $recipient);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return response()->json(['message' => 'Document was processed, but notification email failed.'], 500);
        }

        return response()->json(['message' => 'Document sent successfully.'], 200);
    }

    public function trackDocument(Request $request, Document $document)
    {
        $authUser = Auth::user();
        if (in_array($authUser->default_role, ['superadmin', 'Admin', 'Staff', 'Secretary'])) {
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
            return response()->json(['document_locations' => $document_locations, 'document' => $document], 200);
        }

        if ($authUser->default_role === 'User') {
            $document_locations = FileMovement::with(['document', 'sender.userDetail', 'recipient.userDetail.tenant_department'])->where('document_id', $document->id)->get();
            return response()->json(['document_locations' => $document_locations, 'document' => $document], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function getAttachments(Request $request, Document $document)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'Admin') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return response()->json(['attachments' => $attachments, 'document' => $document], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return response()->json(['attachments' => $attachments, 'document' => $document], 200);
        }
        if ($authUser->default_role === 'Staff') {
            $attachments = Attachments::where('document_id', $document->id)->paginate(5);
            return response()->json(['attachments' => $attachments, 'document' => $document], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Memo Management Endpoints

    public function memoIndex()
    {
        $authUser = Auth::user();
        $memos = Memo::where('user_id', $authUser->id)->orderBy('id', 'desc')->paginate(10);
        return response()->json(['memos' => $memos], 200);
    }

    public function createMemo()
    {
        $authUser = Auth::user();
        return response()->json([], 200);
    }

    public function storeMemo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255|unique:memos,docuent_number',
            'content' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $memo = new Memo();
        $memo->title = $request->input('title');
        $memo->docuent_number = $request->input('document_number');
        $memo->content = $request->input('content');
        $memo->user_id = $request->input('user_id');
        $memo->save();

        return response()->json(['message' => 'Memo created successfully'], 201);
    }

    public function editMemo(Memo $memo)
    {
        $authUser = Auth::user();
        return response()->json(['memo' => $memo], 200);
    }

    public function updateMemo(Request $request, Memo $memo)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $memo->title = $request->input('title');
        $memo->docuent_number = $request->input('document_number');
        $memo->content = $request->input('content');
        $memo->save();

        return response()->json(['message' => 'Memo updated successfully'], 200);
    }

    public function deleteMemo(Memo $memo)
    {
        $authUser = Auth::user();
        $memo->delete();
        return response()->json(['message' => 'Memo deleted successfully'], 200);
    }

    public function generateMemoPdf(Memo $memo)
    {
        $authUser = Auth::user();
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile(public_path('templates/letterhead.pdf'));
        $tplId = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($tplId);
        $pdf->SetFont('Arial', '', 14);
        $pdf->SetXY(25, 100);
        $pdf->Write(0, "Subject: " . $memo['title']);
        $pdf->SetXY(25, 120);
        $pdf->MultiCell(0, 10, $memo['content']);

        return response()->stream(function () use ($pdf) {
            $pdf->Output('I', 'letter.pdf');
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="letter.pdf"',
        ]);
    }

    public function getMemo(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        return response()->json(['memo' => $memo], 200);
    }

    public function createMemoTemplateForm()
    {
        $authUser = Auth::user();
        return response()->json([], 200);
    }

    public function storeMemoTemplate(Request $request)
    {
        $authUser = Auth::user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:memo_templates,name',
            'template' => 'required|file|mimes:pdf|max:2048',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'File upload failed'], 400);
        }

        if ($request->hasFile('template')) {
            $file = $request->file('template');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->move(public_path('templates/'), $fileName);

            $memoTemplate = MemoTemplate::create([
                'name' => $request->name,
                'template' => $fileName,
                'user_id' => $request->user_id,
            ]);

            return response()->json(['message' => 'Memo template created successfully'], 201);
        }

        return response()->json(['error' => 'File upload failed'], 400);
    }

    public function getSendMemoExternalForm(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'Admin') {
            $recipients = User::with(['userDetail.tenant' => function ($query) {
                $query->select('id', 'name');
            }])
                ->where('default_role', 'Admin')
                ->get();

            if ($recipients->isEmpty()) {
                return response()->json(['error' => 'No recipients found.'], 404);
            }
            return response()->json(['recipients' => $recipients, 'memo' => $memo], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function getSendMemoform(Request $request, Memo $memo)
    {
        $authUser = Auth::user();
        $role = $authUser->default_role;

        switch ($role) {
            case 'superadmin':
                $recipients = User::all();
                return response()->json(['recipients' => $recipients, 'memo' => $memo], 200);

            case 'Admin':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                if (!$tenantId) {
                    return response()->json(['error' => 'Tenant information is missing.'], 400);
                }

                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name');
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return response()->json(['error' => 'No recipients found.'], 404);
                }

                return response()->json(['recipients' => $recipients, 'memo' => $memo], 200);

            case 'User':
                $recipients = User::where('default_role', 'Admin')->get();
                return response()->json(['recipients' => $recipients, 'memo' => $memo], 200);

            case 'Staff':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                if (!$tenantId) {
                    return response()->json(['error' => 'Tenant information is missing.'], 400);
                }
                $recipients = User::select('id', 'name')
                    ->with(['userDetail' => function ($query) {
                        $query->select('id', 'user_id', 'designation', 'tenant_id', 'department_id')
                            ->with('tenant_department:id,name');
                    }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return response()->json(['error' => 'No recipients found.'], 404);
                }

                return response()->json(['recipients' => $recipients, 'memo' => $memo], 200);

            case 'Secretary':
                $tenantId = $authUser->userDetail->tenant_id ?? null;
                if (!$tenantId) {
                    return response()->json(['error' => 'Tenant information is missing.'], 400);
                }

                $recipients = User::with(['userDetail' => function ($query) {
                    $query->select('id', 'user_id', 'designation', 'tenant_id');
                }])
                    ->whereHas('userDetail', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    })
                    ->where('id', '!=', $authUser->id)
                    ->get();

                if ($recipients->isEmpty()) {
                    return response()->json(['error' => 'No recipients found.'], 404);
                }

                return response()->json(['recipients' => $recipients, 'memo' => $memo], 200);

            default:
                return response()->json(['error' => 'Unauthorized'], 403);
        }
    }

    public function sendMemo(Request $request)
    {
        $data = $request;
        $result = DocumentStorage::sendMemo($data);
        if ($result['status'] === 'error') {
            return response()->json(['errors' => $result['errors']], 400);
        }
        try {
            SendMailHelper::sendNotificationMail($data, $request);
        } catch (\Exception $e) {
            Log::error('Failed to send review notification email: ' . $e->getMessage());
            return response()->json(['message' => 'Document was processed, but notification email failed.'], 500);
        }

        return response()->json(['message' => 'Memo sent successfully'], 200);
    }

    public function sentMemos()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                $mda = collect();
            }
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient, 'mda' => $mda], 200);
        }
        if ($authUser->default_role === 'Admin') {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient], 200);
        }
        if ($authUser->default_role === 'User') {
            list($sent_documents, $recipient) = DocumentStorage::getSentDocuments();
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with('tenant')->where('id', $recipient[0]->id)->get();
            } else {
                $mda = collect();
            }
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient, 'mda' => $mda], 200);
        }
        if ($authUser->default_role === 'Staff') {
            list($sent_documents, $recipient) = DocumentStorage::getSentMemos();
            if (!empty($recipient) && isset($recipient[0])) {
                $mda = UserDetails::with(['tenant', 'tenant_department'])->where('id', $recipient[0]->id)->get();
            } else {
                $mda = collect();
            }
            return response()->json(['sent_documents' => $sent_documents, 'recipient' => $recipient, 'mda' => $mda], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function receivedMemos()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        if ($authUser->default_role === 'Admin') {
            list($received_documents) = DocumentStorage::getReceivedMemos();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        if ($authUser->default_role === 'Secretary') {
            list($received_documents) = DocumentStorage::getReceivedMemos();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        if ($authUser->default_role === 'User') {
            list($received_documents) = DocumentStorage::getReceivedDocuments();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        if ($authUser->default_role === 'Staff') {
            list($received_documents) = DocumentStorage::getReceivedMemos();
            return response()->json(['received_documents' => $received_documents], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Department Management Endpoints

    public function departmentIndex()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $departments = TenantDepartment::orderBy('id', 'desc')->paginate(10);
            return response()->json(['departments' => $departments], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $tenantId = $authUser->userdetail->tenant_id;
            $departments = TenantDepartment::where('tenant_id', $tenantId)
                ->orderBy('id', 'desc')
                ->paginate(10);
            return response()->json(['departments' => $departments], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function departmentCreate()
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $organisations = Tenant::all();
            return response()->json(['organisations' => $organisations], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $tenantId = $authUser->userdetail->tenant_id;
            $organisations = Tenant::where('id', $tenantId)->first();
            return response()->json(['organisations' => $organisations, 'tenantId' => $tenantId], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function departmentStore(Request $request)
    {
        if (Auth::user()->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department = TenantDepartment::create($request->all());
            return response()->json(['message' => 'Department created successfully'], 201);
        }
        if (Auth::user()->default_role === 'Admin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department = TenantDepartment::create($request->all());
            return response()->json(['message' => 'Department created successfully'], 201);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function departmentEdit(TenantDepartment $department)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $organisations = Tenant::all();
            return response()->json(['department' => $department, 'organisations' => $organisations], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $organisations = Tenant::all();
            return response()->json(['department' => $department, 'organisations' => $organisations], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function departmentUpdate(Request $request, TenantDepartment $department)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department->update($request->all());
            return response()->json(['message' => 'Department updated successfully'], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'tenant_id' => 'required|exists:tenants,id',
            ]);
            $department->update($request->all());
            return response()->json(['message' => 'Department updated successfully'], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    public function departmentDelete(TenantDepartment $department)
    {
        $authUser = Auth::user();
        if ($authUser->default_role === 'superadmin') {
            $department->delete();
            return response()->json(['message' => 'Department deleted successfully'], 200);
        }
        if ($authUser->default_role === 'Admin') {
            $department->delete();
            return response()->json(['message' => 'Department deleted successfully'], 200);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Role Management Endpoints

    public function roleIndex()
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $roles = Role::orderBy('name')->paginate(10);
        return response()->json(['roles' => $roles], 200);
    }

    public function roleStore(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'string|max:255'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web'
        ]);

        return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);
    }

    public function roleEdit(Role $role)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['role' => $role], 200);
    }

    public function roleUpdate(Request $request, Role $role)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        $role->name = $request->name;
        $role->save();

        return response()->json(['message' => 'Role updated successfully', 'role' => $role], 200);
    }

    public function roleDelete(Role $role)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Prevent deletion of default roles
        if (in_array($role->name, ['superadmin', 'Admin', 'Staff', 'User', 'Secretary'])) {
            return response()->json(['error' => 'Cannot delete default system roles'], 422);
        }

        $role->delete();
        return response()->json(['message' => 'Role deleted successfully'], 200);
    }

    // Designation Management Endpoints

    public function designationIndex()
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $designations = Designation::orderBy('name')->paginate(10);
        return response()->json(['designations' => $designations], 200);
    }

    public function designationStore(Request $request)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $designation = Designation::create($request->all());
        return response()->json(['message' => 'Designation created successfully', 'designation' => $designation], 201);
    }

    public function designationEdit(Designation $designation)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['designation' => $designation], 200);
    }

    public function designationUpdate(Request $request, Designation $designation)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $designation->name = $request->name;
        $designation->description = $request->description;
        $designation->save();

        return response()->json(['message' => 'Designation updated successfully', 'designation' => $designation], 200);
    }

    public function designationDelete(Designation $designation)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $designation->delete();
        return response()->json(['message' => 'Designation deleted successfully'], 200);
    }

    // Search Endpoints

    public function searchUsers(Request $request)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = $request->get('query');
        if (!$query) {
            return response()->json(['users' => []], 200);
        }

        $users = User::where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->with('userDetail')
            ->limit(10)
            ->get();

        return response()->json(['users' => $users], 200);
    }

    public function searchOrganisations(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser->default_role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = $request->get('query');
        if (!$query) {
            return response()->json(['organisations' => []], 200);
        }

        $organisations = Tenant::where('name', 'LIKE', "%{$query}%")
            ->orWhere('category', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json(['organisations' => $organisations], 200);
    }

    public function searchDepartments(Request $request)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = $request->get('query');
        if (!$query) {
            return response()->json(['departments' => []], 200);
        }

        $departments = TenantDepartment::where('name', 'LIKE', "%{$query}%")
            ->with('tenant')
            ->limit(10)
            ->get();

        return response()->json(['departments' => $departments], 200);
    }

    public function searchDocuments(Request $request)
    {
        $authUser = Auth::user();
        $query = $request->get('query');
        
        if (!$query) {
            return response()->json(['documents' => []], 200);
        }

        $documents = Document::where('title', 'LIKE', "%{$query}%")
            ->orWhere('docuent_number', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->with(['uploader'])
            ->limit(10)
            ->get();

        return response()->json(['documents' => $documents], 200);
    }

    // Visitor Activities Management

    public function visitorActivitiesIndex()
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activities = \App\Models\VisitorActivity::orderBy('created_at', 'desc')->paginate(10);
        return response()->json(['visitor_activities' => $activities], 200);
    }

    public function visitorActivitiesShow(\App\Models\VisitorActivity $visitorActivity)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['visitor_activity' => $visitorActivity], 200);
    }

    // Session Check Endpoint

    public function checkSession()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['authenticated' => false], 401);
        }

        return response()->json([
            'authenticated' => true,
            'user' => $user,
            'role' => $user->default_role
        ], 200);
    }

    // Organisation Department View

    public function getOrganisationDepartments($organisationId)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->default_role, ['superadmin', 'Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $organisation = Tenant::find($organisationId);
        if (!$organisation) {
            return response()->json(['error' => 'Organisation not found'], 404);
        }

        $departments = TenantDepartment::where('tenant_id', $organisationId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'organisation' => $organisation,
            'departments' => $departments
        ], 200);
    }
}
