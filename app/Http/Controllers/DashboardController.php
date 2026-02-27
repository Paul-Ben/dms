<?php

namespace App\Http\Controllers;

use App\Helpers\DocumentStorage;
use App\Models\Activity;
use App\Models\FileMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // Ensure the user is authenticated
    }

    public function index()
    {
        $authUser = Auth::user();
        $role = $authUser->default_role;
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();


        $views = [
            'superadmin' => 'superadmin.index',
            'Admin' => 'admin.index',
            'Secretary' => 'secretary.index',
            'Staff' => 'staff.index',
            'User' => 'user.index',
            'IT Admin' => 'staff.index',
        ];


        if (!array_key_exists($role, $views)) {
            return view('errors.404');
        }


        list($recieved_documents_count, $sent_documents_count, $uploaded_documents_count, $totalAmount) = DocumentStorage::documentCount();
       
        // $activities = Activity::with('user')
        //     ->where('user_id', $authUser->id)
        //     ->orderBy('id', 'desc')
        //     ->take(10)
        //     ->get();

        $activities = FileMovement::with(['sender.userDetail', 'recipient.userDetail', 'document'])
            ->where(function ($query) use ($authUser) {
                $query->where('sender_id', $authUser->id)
                      ->orWhere('recipient_id', $authUser->id);
            })
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($movement) use ($authUser) {
                $isSender = $movement->sender_id === $authUser->id;
                // If I am the sender, the "other" person is the recipient.
                // If I am the recipient, the "other" person is the sender.
                $otherUser = $isSender ? $movement->recipient : $movement->sender;
                
                $actionText = $isSender ? 'Sent Document' : 'Received Document';
                 if ($movement->document) {
                     $actionText .= ': ' . $movement->document->title;
                 }
 
                 // Determine correct route based on direction
                 $routeName = $isSender ? 'document.view_sent' : 'document.view';

                 return (object) [
                     'user' => $otherUser,
                     'action' => $actionText,
                     'created_at' => $movement->created_at,
                     'document_id' => $movement->document_id,
                     'url' => route($routeName, $movement->id),
                     // Pass full document object if needed
                     'document' => $movement->document
                 ];
             });

        return view($views[$role], compact('recieved_documents_count', 'sent_documents_count', 'uploaded_documents_count', 'activities', 'userTenant', 'totalAmount',  'authUser'));
    }
}
