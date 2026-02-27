<?php

namespace App\Helpers;

use App\Models\Activity;
use App\Models\Document;
use App\Models\DocumentRecipient;
use App\Models\FileMovement;
use App\Helpers\PDF;
use App\Models\Attachments;
use App\Models\Memo;
use App\Models\MemoMovement;
use App\Models\MemoRecipient;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\CloudinaryHelper;
use Illuminate\Support\Facades\Validator;
use setasign\Fpdf\Fpdf;
use setasign\Fpdi\Fpdi;

class DocumentStorage
{
    /**Document count for dashboard view */
    public static function documentCount()
    {
        $received_documents_count = FileMovement::with(['document_recipients', 'document'])
            ->where('recipient_id', Auth::user()->id)->count();
        $sent_documents_count = FileMovement::with(['document_recipients', 'document'])
            ->where('sender_id', Auth::user()->id)->count();

        $uploaded_documents_count = Document::where('uploaded_by', Auth::user()->id)->count();
        $totalAmount = Payment::where('tenant_id', Auth::user()->userDetail->tenant_id)->sum('transAmount');

        return [
            $received_documents_count,
            $sent_documents_count,
            $uploaded_documents_count,
            $totalAmount
        ];
    }

    /**
     * Get all documents for the logged in user
     */
    public static function myDocuments()
    {
        $documents = Document::with('user')->where('uploaded_by', Auth::user()->id)
            ->orderBy('id', 'desc')
            ->get();


        return $documents;
    }

    /**
     * Store document in the database
     */
    public static function storeDocument($data)
    {
        $authUser = Auth::user();
        $tenantId = $authUser->userDetail->tenant_id;
        $uploadedBy = $data['uploaded_by'];
        $departmentId = $authUser->userDetail->department_id;

        $data->validate([
            'title' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'file_path' => 'required|file|mimes:pdf|max:10240',
            'description' => 'required|string',
            'metadata' => 'nullable|json',
        ]);

        // $fileUrl = null;
        // $publicId = null;

        // if ($data->hasFile('file_path')) {
        //     $file = $data->file('file_path');
        //     $cloudinary = new CloudinaryHelper();
        //     $folder = 'documents/' . $tenantId . '/' . $uploadedBy;
        //     $result = $cloudinary->upload($file, $folder);

        //     $fileUrl = $result['secure_url'];
        //     $publicId = $result['public_id'];
        // }

        try {
            $cloudinary = new CloudinaryHelper();
            $folder = 'edms_documents/' . $tenantId . '/' . $uploadedBy;
            $result = $cloudinary->upload($data->file('file_path'), $folder);


            // Create the document record and return reference info
            $doc = Document::create([
                'title' => $data->title,
                'docuent_number' => $data->document_number,
                'file_path' => $result['secure_url'],
                'uploaded_by' => $authUser->id,
                'status' => $data->status ?? 'pending',
                'description' => $data->description,
            ]);
        } catch (\Exception $e) {
            Log::error('Document upload error: ' . $e->getMessage());

            return [
                'status' => 'error',
                'errors' => ['file_path' => $e->getMessage()]
            ];
        }
        // Log the activity
        Activity::create([
            'action' => 'You uploaded a document',
            'user_id' => $authUser->id,
        ]);

        return [
            'status' => 'success',
            'message' => 'Document submitted successfully!',
            'document_id' => isset($doc) ? $doc->id : null,
            'document_number' => isset($doc) ? $doc->docuent_number : null,
        ];
    }

    /**
     * Get a single document
     */
    public static function getDocument($document)
    {
        $tenantId = $document->tenant_id;
        $departmentId = $document->department_id;
        $filePath = $document->file_path;

        $path = public_path('documents/' . $filePath);

        if (file_exists($path)) {
            return response()->file($path);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'File not found',
        ], 404);
    }

    /**
     * Send document to a recipient
     */
    public static function sendDocument($data)
    {
        // dd($data);
        $data->validate([
            'recipient_id' => 'required|array', // Validate that it's an array
            'recipient_id.*' => 'exists:users,id',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // if ($data->hasFile('attachment')) {
        //     // Get the uploaded file
        //     $file = $data->file('attachment');

        //     // Define the path where you want to store the file
        //     $destinationPath = public_path('documents/attachments');

        //     // Generate a unique filename (optional)
        //     $fileName = time() . '_' . $file->getClientOriginalName();

        //     // Move the file to the public attachments directory
        //     $file->move($destinationPath, $fileName);
        // }
        if($data->hasFile('attachment')){
            try{
                $cloudinary = new CloudinaryHelper();
                $folder = 'edms_attachments';
                $fileName = $cloudinary->upload($data->file('attachment'), $folder);
                $data->attachment = $fileName;
            }catch(\Exception $e){
                Log::error($e);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload file to Cloudinary. Please try again later.',
                ], 500);
            }
        }

        foreach ($data->recipient_id as $recipient) {
            $document_action = FileMovement::create([
                'recipient_id' => $recipient,
                'sender_id' => Auth::user()->id,
                'message' => $data->message,
                'document_id' => $data->document_id,
            ]);
            if ($data->hasFile('attachment')) {
                Attachments::create([
                    'file_movement_id' => $document_action->id,
                    'attachment' => $fileName['secure_url'],
                    'document_id' => $data->document_id,
                ]);
            }
            DocumentRecipient::create([
                'file_movement_id' => $document_action->id,
                'recipient_id' => $recipient,
                'user_id' => Auth::user()->id,
                'created_at' => now(),
            ]);
            $activities = [
                [
                    'action' => 'Sent Document',
                    'user_id' => Auth::user()->id,
                    'created_at' => now(),
                ],
                [
                    'action' => 'Document Received',
                    'user_id' => $recipient,
                    'created_at' => now(),
                ],
            ];
            $lastMovement = FileMovement::where('document_id', $data->document_id)
                ->where('id', '<', $document_action->id)
                ->orderBy('id', 'desc')
                ->first();
            if ($lastMovement && $lastMovement->sender_id !== Auth::id()) {
                $senderName = optional(\App\Models\User::find(Auth::id()))->name ?? 'Unknown';
                $recipientName = optional(\App\Models\User::find($recipient))->name ?? 'Unknown';
                $activities[] = [
                    'action' => "Forwarded by {$senderName} to {$recipientName}",
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                ];
            }
            Activity::insert($activities);
        }
        return [
            'status' => 'success',
            'message' => 'Document sent successfully!',
        ];
    }
    // public static function sendDocument($data)
    // {
    //     $data->validate([
    //         'recipient_id' => 'required|array',
    //         'recipient_id.*' => 'exists:users,id',
    //         'message' => 'nullable|string',
    //         'attachment' => 'nullable|file|mimes:pdf|max:2048',
    //     ]);

    //     $recipients = array_unique($data->recipient_id);
    //     $fileName = null;

    //     if ($data->hasFile('attachment')) {
    //         $file = $data->file('attachment');
    //         $destinationPath = public_path('documents/attachments');
    //         $fileName = time() . '_' . $file->getClientOriginalName();
    //         $file->move($destinationPath, $fileName);
    //     }

    //     DB::transaction(function () use ($data, $recipients, $fileName) {
    //         foreach ($recipients as $recipient) {
    //             $document_action = FileMovement::firstOrCreate([
    //                 'recipient_id' => $recipient,
    //                 'sender_id' => Auth::user()->id,
    //                 'document_id' => $data->document_id,
    //             ], [
    //                 'message' => $data->message,
    //             ]);

    //             if ($fileName) {
    //                 Attachments::firstOrCreate([
    //                     'file_movement_id' => $document_action->id,
    //                     'document_id' => $data->document_id,
    //                 ], [
    //                     'attachment' => $fileName,
    //                 ]);
    //             }

    //             DocumentRecipient::firstOrCreate([
    //                 'file_movement_id' => $document_action->id,
    //                 'recipient_id' => $recipient,
    //                 'user_id' => Auth::user()->id,
    //             ]);

    //             Activity::firstOrCreate([
    //                 'action' => 'Sent Document',
    //                 'user_id' => Auth::user()->id,
    //             ]);

    //             Activity::firstOrCreate([
    //                 'action' => 'Document Received',
    //                 'user_id' => $recipient,
    //             ]);
    //         }
    //     });

    //     return [
    //         'status' => 'success',
    //         'message' => 'Document sent successfully!',
    //     ];
    // }


    public static function sendMemo($data)
    {
        $data->validate([
            'recipient_id' => 'required|array', // Validate that it's an array
            'recipient_id.*' => 'exists:users,id',
            // 'recipient_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            // 'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // if ($data->hasFile('attachment')) {
        //     // Get the uploaded file
        //     $file = $data->file('attachment');

        //     // Define the path where you want to store the file
        //     $destinationPath = public_path('documents/attachments');

        //     // Generate a unique filename (optional)
        //     $fileName = time() . '_' . $file->getClientOriginalName();

        //     // Move the file to the public attachments directory
        //     $file->move($destinationPath, $fileName);
        // }

        foreach ($data->recipient_id as $recipient) {
            $document_action = MemoMovement::create([
                'recipient_id' => $recipient,
                'sender_id' => Auth::user()->id,
                'message' => $data->message,
                'memo_id' => $data->memo_id,
            ]);
            // if ($data->hasFile('attachment')) {
            //     Attachments::create([
            //         'file_movement_id' => $document_action->id,
            //         'attachment' => $fileName,
            //         'document_id' => $data->document_id,
            //     ]);
            // }
            MemoRecipient::create([
                'memo_movement_id' => $document_action->id,
                'recipient_id' => $recipient,
                'user_id' => Auth::user()->id,
                'created_at' => now(),
            ]);
            Activity::insert([
                [
                    'action' => 'Sent Memo',
                    'user_id' => Auth::user()->id,
                    'created_at' => now(),
                ],
                [
                    'action' => 'Memo Received',
                    'user_id' => $recipient,
                    'created_at' => now(),
                ],
            ]);
        }
        return [
            'status' => 'success',
            'message' => 'Memo sent successfully!',
        ];
    }
    // public static function reviewedDocument($data)
    // {
    //     $getsender = $data['sender'];
    //     $sender = $getsender['id'];
    //     $getrecipient = $data['recipient'];
    //     $recipient = $getrecipient['id'];
    //     $validator = Validator::make($data, [
    //         'recipient_id' => 'required|array', 
    //         'recipient_id.*' => 'exists:users,id', 
    //         'document_id' => 'required|exists:documents,id',
    //         'message' => 'nullable|string',
    //     ]);
    //     if ($validator->fails()) {
    //         return [
    //             'status' => 'error',
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors(),
    //         ];
    //     }
    //     $validated = $validator->validated();
    //     $authUserId = Auth::user()->id; // Current user ID
    //     $currentTime = now(); // Current timestamp for reuse

    //     foreach ($validated['recipient_id'] as $recipientId) {
    //         // Create a file movement record
    //         $documentAction = FileMovement::create([
    //             'recipient_id' => $recipientId,
    //             'sender_id' => $sender,
    //             'message' => $validated['message'] ?? null,
    //             'document_id' => $validated['document_id'],
    //         ]);

    //         // Create a document recipient record
    //         DocumentRecipient::create([
    //             'file_movement_id' => $documentAction->id,
    //             'recipient_id' => $recipient,
    //             'user_id' => $authUserId,
    //             'created_at' => $currentTime,
    //         ]);

    //         // Log activities
    //         Activity::insert([
    //             [
    //                 'action' => 'Reviewed Document',
    //                 'user_id' => $authUserId,
    //                 'created_at' => $currentTime,
    //             ],
    //             [
    //                 'action' => 'Sent Document',
    //                 'user_id' => $authUserId,
    //                 'created_at' => $currentTime,
    //             ],
    //             [
    //                 'action' => 'Document Received',
    //                 'user_id' => $recipient,
    //                 'created_at' => $currentTime,
    //             ],
    //         ]);
    //     }

    //     return [
    //         'status' => 'success',
    //         'message' => 'Document sent successfully!',
    //     ];
    // }
    // public static function reviewedDocument(array $data)
    // {
    //     dd($data);
    //     // Extract sender and recipient IDs
    //     $sender = $data['sender']['id'] ?? null;
    //     $recipient = $data['recipient']['id'] ?? null;

    //     // Validate the input data
    //     $validator = Validator::make($data, [
    //         'recipient_id' => 'required|array', // Ensure recipient_id is an array
    //         'recipient_id.*' => 'exists:users,id', // Validate each recipient ID
    //         'document_id' => 'required|exists:documents,id', // Ensure the document exists
    //         'message' => 'nullable|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return [
    //             'status' => 'error',
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors(),
    //         ];
    //     }

    //     $validated = $validator->validated();
    //     $authUserId = Auth::id(); // Current user ID
    //     $currentTime = now(); // Current timestamp for reuse

    //     $activities = []; // Store activities for batch insertion
    //     $documentRecipients = []; // Store document recipient records for batch insertion

    //     foreach ($validated['recipient_id'] as $recipientId) {
    //         // Create a file movement record
    //         $documentAction = FileMovement::create([
    //             'recipient_id' => $recipientId,
    //             'sender_id' => $sender,
    //             'message' => $validated['message'] ?? null,
    //             'document_id' => $validated['document_id'],
    //         ]);

    //         // Prepare a document recipient record
    //         $documentRecipients[] = [
    //             'file_movement_id' => $documentAction->id,
    //             'recipient_id' => $recipientId,
    //             'user_id' => $authUserId,
    //             'created_at' => $currentTime,
    //         ];

    //         // Prepare activity log entries
    //         $activities[] = [
    //             'action' => 'Reviewed Document',
    //             'user_id' => $authUserId,
    //             'created_at' => $currentTime,
    //         ];
    //         $activities[] = [
    //             'action' => 'Sent Document',
    //             'user_id' => $authUserId,
    //             'created_at' => $currentTime,
    //         ];
    //         $activities[] = [
    //             'action' => 'Document Received',
    //             'user_id' => $recipientId,
    //             'created_at' => $currentTime,
    //         ];
    //     }

    //     // Insert all document recipient records in a single query
    //     if (!empty($documentRecipients)) {
    //         DocumentRecipient::insert($documentRecipients);
    //     }

    //     // Insert all activities in a single query
    //     if (!empty($activities)) {
    //         Activity::insert($activities);
    //     }

    //     return [
    //         'status' => 'success',
    //         'message' => 'Document sent successfully!',
    //     ];
    // }

    public static function reviewedDocument(array $data, $recipient)
    {
        $recipientID = $recipient->id;

        // Validate input data
        $validator = Validator::make($data, [
            'sender.id' => 'required|exists:users,id',
            'recipient.id' => 'required|exists:users,id',
            'document_id' => 'required|exists:documents,id',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ];
        }


        $validated = $validator->validated();

        $senderId = $validated['sender']['id'];
        $authUserId = Auth::user()->id;
        $currentTime = now();

        // Process each recipient
        // foreach ($validated['recipient_id'] as $recipientId) {
        try {
            // Create file movement record
            $documentAction = FileMovement::create([
                'recipient_id' => $recipientID,
                'sender_id' => $senderId,
                'message' => $validated['message'] ?? null,
                'document_id' => $validated['document_id'],
            ]);

            // Create document recipient record
            DocumentRecipient::create([
                'file_movement_id' => $documentAction->id,
                'recipient_id' => $recipientID,
                'user_id' => $authUserId,
                'created_at' => $currentTime,
            ]);

            // Update document status in the documents table
            $document = Document::find($validated['document_id']);
            if ($document) {
                $document->status = 'processing'; // Update the status as needed
                $document->save();
            }

            // Log activities
            Activity::insert([
                [
                    'action' => 'Reviewed Document',
                    'user_id' => $authUserId,
                    'created_at' => $currentTime,
                ],
                [
                    'action' => 'Document Received',
                    'user_id' => $recipientID,
                    'created_at' => $currentTime,
                ],
            ]);
        } catch (\Exception $e) {
            // Log and return error on failure
            Log::error('Error processing recipient: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'An error occurred while processing the document.',
            ];
        }
        // }

        return [
            'status' => 'success',
            'message' => 'Document reviewed and sent successfully!',
        ];
    }


    /**
     * Get all sent documents
     */
    public static function getSentDocuments()
    {
        $authUser = Auth::user();
        try {
            // Eager load both relationships and paginate the results
            $sent_documents = FileMovement::with(['document_recipients', 'document'])
                ->where('sender_id',  $authUser->id)
                ->orderBy('id', 'desc')
                ->get();

            // Fetch recipient details for each sent document
            foreach ($sent_documents as $key => $value) {
                $recipient = User::with('userDetail.tenant')->where('id', $value->recipient_id)->get(['id', 'name', 'email']);
                // You can attach recipient details to the document if needed
                $value->recipient_details = $recipient; // Optional: Attach recipient details
            }

            return [$sent_documents, $recipient];
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error retrieving received documents: ' . $e->getMessage());
            // Return an empty collection and null sender details
            return [collect(), null];
        }
    }

    /**
     * Get all received documents
     */
    public static function getReceivedDocuments()
    {
        try {
            // Eager load both relationships and paginate the results
            $received_documents = FileMovement::with(['document_recipients', 'document', 'sender.userDetail.tenant_department'])
                ->where('recipient_id', Auth::user()->id)
                ->orderBy('id', 'desc')
                ->get();

            // Fetch sender details for each received document
            foreach ($received_documents as $key => $value) {
                // $value->sender_details = User::select('name', 'email')->find($value->sender_id);
                $value->sender_details = User::with('userDetail')->find($value->sender_id);
            }

            return [$received_documents];
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error retrieving received documents: ' . $e->getMessage());
            // Return an empty collection and null sender details
            return [collect(), null];
        }
    }

    /**
     * Get single document details
     */
    public static function getDocumentDetails($received)
    {
        $document = Document::with('user')->find($received);
        // dd($document);
        return $document;
    }

    public static function getUserRecipients()
    {
        $adminWithTenantDetails = User::where('default_role', 'Secretary')
            ->whereHas('userDetail', function ($query) {
                $query->whereNotNull('tenant_id');
            })
            ->with(['userDetail.tenant'])
            ->get()
            ->map(function ($user) {
                return [
                    'admin_id' => $user->id,
                    'tenant_id' => $user->userDetail->tenant->id ?? null,
                    'tenant_name' => $user->userDetail->tenant->name ?? null,
                ];
            });

        return $adminWithTenantDetails;
    }

    public static function getSentMemos()
    {
        $authUser = Auth::user();
        try {
            // Eager load both relationships and paginate the results
            $sent_documents = MemoMovement::with(['memo_recipients', 'memo'])
                ->where('sender_id',  $authUser->id)
                ->orderBy('id', 'desc')
                ->get();

            // Fetch recipient details for each sent document
            foreach ($sent_documents as $key => $value) {
                $recipient = User::with('userDetail.tenant')->where('id', $value->recipient_id)->get(['id', 'name', 'email']);
                // You can attach recipient details to the document if needed
                $value->recipient_details = $recipient; // Optional: Attach recipient details
            }

            return [$sent_documents, $recipient];
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error retrieving received documents: ' . $e->getMessage());
            // Return an empty collection and null sender details
            return [collect(), null];
        }
    }

    public static function getReceivedMemos()
    {
        try {
            // Eager load both relationships and paginate the results
            $received_documents = MemoMovement::with(['memo_recipients', 'memo', 'sender.userDetail.tenant_department'])
                ->where('recipient_id', Auth::user()->id)
                ->orderBy('id', 'desc')
                ->get();
            // dd($received_documents);
            // Fetch sender details for each received document
            foreach ($received_documents as $key => $value) {
                // $value->sender_details = User::select('name', 'email')->find($value->sender_id);
                $value->sender_details = User::with('userDetail')->find($value->sender_id);
            }

            return [$received_documents];
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error retrieving received documents: ' . $e->getMessage());
            // Return an empty collection and null sender details
            return [collect(), null];
        }
    }
}
