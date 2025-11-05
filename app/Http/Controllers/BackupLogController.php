<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Models\Tenant;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        $authUser = Auth::user();
        $userdetails = UserDetails::where('user_id', $authUser->id)->first();
        $userTenant = Tenant::where('id', $userdetails->tenant_id)->first();
        $backups = BackupLog::orderBy('created_at', 'asc')->get();

        // Spatie backup listing across configured disks
        $spatieBackups = [];
        $disks = config('backup.backup.destination.disks', []);
        $appName = config('backup.backup.name');
        foreach ($disks as $disk) {
            try {
                $files = Storage::disk($disk)->allFiles();
            } catch (\Exception $e) {
                $files = [];
            }
            foreach ($files as $path) {
                // Only show zip files created by the backup package
                if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'zip') {
                    continue;
                }
                $size = 0;
                $lastModified = null;
                try {
                    $size = Storage::disk($disk)->size($path);
                    $lastModified = Storage::disk($disk)->lastModified($path);
                } catch (\Exception $e) {
                    // ignore errors fetching metadata
                }
                $spatieBackups[] = [
                    'disk' => $disk,
                    'path' => $path,
                    'filename' => basename($path),
                    'size' => $size,
                    'last_modified' => $lastModified,
                    'app' => $appName,
                ];
            }
        }
        if (Auth::user()->default_role === 'superadmin') {
            return view('superadmin.backups', compact('backups', 'spatieBackups', 'userTenant', 'authUser'));
        }
        return view('errors.404', compact('authUser', 'userTenant'));
    }

    public function download(BackupLog $backup)
    {
        // $backup = BackupLog::findOrFail($id);
        
        $path = $backup->file_path;
        // dd($path);
    
        if (file_exists($path)) {
            return response()->download($path);
        }
        abort(404, 'Backup file not found.');
    }

    public function destroy(BackupLog $backup)
{
    // Check if user is superadmin
    if (Auth::user()->default_role !== 'superadmin') {
        abort(403, 'Unauthorized action.');
    }

    $path = $backup->file_path;

    // First check if file exists
    if (file_exists($path)) {
        // Delete the file
        if (unlink($path)) {
            // Delete the database record
            $backup->delete();
            $notification = [
                'message' => 'Backup file deleted successfully.',
                'alert-type' => 'success'
            ];
            return redirect()
                ->route('backup.index')
                ->with($notification);
        } else {
            $notification = [
                'message' => 'Failed to delete backup file',
                'alert-type' => 'error'
            ];
            return back()
                ->with($notification );
        }
    }

    // If file doesn't exist, just delete the record
    $backup->delete();
    $notification = [
        'message' => 'Backup record deleted but file was not found',
        'alert-type' => 'warning'
    ];
    return redirect()
        ->route('backup.index')
        ->with($notification);
}

    // -------- Spatie backup actions for database/application ---------

    public function runDatabaseBackup(Request $request)
    {
        if (Auth::user()->default_role !== 'superadmin') {
            abort(403, 'Unauthorized action.');
        }
        $onlyDb = $request->boolean('only_db', false);
        $params = [];
        if ($onlyDb) {
            $params['--only-db'] = true;
        }
        try {
            Artisan::call('backup:run', $params);
            $output = Artisan::output();
            $notification = [
                'message' => 'Backup run triggered successfully.',
                'alert-type' => 'success'
            ];
            return redirect()->route('backup.index')->with($notification)->with('backup_output', $output);
        } catch (\Exception $e) {
            $notification = [
                'message' => 'Backup run failed: ' . $e->getMessage(),
                'alert-type' => 'error'
            ];
            return redirect()->route('backup.index')->with($notification);
        }
    }

    public function cleanDatabaseBackups()
    {
        if (Auth::user()->default_role !== 'superadmin') {
            abort(403, 'Unauthorized action.');
        }
        try {
            Artisan::call('backup:clean');
            $output = Artisan::output();
            $notification = [
                'message' => 'Backup cleanup executed successfully.',
                'alert-type' => 'success'
            ];
            return redirect()->route('backup.index')->with($notification)->with('backup_output', $output);
        } catch (\Exception $e) {
            $notification = [
                'message' => 'Backup cleanup failed: ' . $e->getMessage(),
                'alert-type' => 'error'
            ];
            return redirect()->route('backup.index')->with($notification);
        }
    }

    public function monitorBackups()
    {
        if (Auth::user()->default_role !== 'superadmin') {
            abort(403, 'Unauthorized action.');
        }
        try {
            Artisan::call('backup:monitor');
            $output = Artisan::output();
            $notification = [
                'message' => 'Backup health check completed.',
                'alert-type' => 'success'
            ];
            return redirect()->route('backup.index')->with($notification)->with('backup_output', $output);
        } catch (\Exception $e) {
            $notification = [
                'message' => 'Backup monitor failed: ' . $e->getMessage(),
                'alert-type' => 'error'
            ];
            return redirect()->route('backup.index')->with($notification);
        }
    }

    public function downloadDatabaseBackup($disk, $path)
    {
        if (Auth::user()->default_role !== 'superadmin') {
            abort(403, 'Unauthorized action.');
        }
        $decodedPath = urldecode($path);
        if (!in_array($disk, config('backup.backup.destination.disks', []))) {
            abort(404, 'Disk not configured for backups.');
        }
        if (!Storage::disk($disk)->exists($decodedPath)) {
            abort(404, 'Backup file not found.');
        }
        // Prefer local absolute path when available; otherwise stream the file
        $filename = basename($decodedPath);
        try {
            $absolutePath = Storage::disk($disk)->path($decodedPath);
            if ($absolutePath && file_exists($absolutePath)) {
                return response()->download($absolutePath, $filename);
            }
        } catch (\Exception $e) {
            // Fallback to streaming for non-local disks
        }

        $stream = Storage::disk($disk)->readStream($decodedPath);
        if ($stream === false || $stream === null) {
            abort(404, 'Unable to read backup file stream.');
        }
        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $filename, [
            'Content-Type' => 'application/zip'
        ]);
    }

    public function deleteDatabaseBackup($disk, $path)
    {
        if (Auth::user()->default_role !== 'superadmin') {
            abort(403, 'Unauthorized action.');
        }
        $decodedPath = urldecode($path);
        if (!in_array($disk, config('backup.backup.destination.disks', []))) {
            abort(404, 'Disk not configured for backups.');
        }
        if (!Storage::disk($disk)->exists($decodedPath)) {
            $notification = [
                'message' => 'Backup file was not found.',
                'alert-type' => 'warning'
            ];
            return redirect()->route('backup.index')->with($notification);
        }
        Storage::disk($disk)->delete($decodedPath);
        $notification = [
            'message' => 'Backup file deleted successfully.',
            'alert-type' => 'success'
        ];
        return redirect()->route('backup.index')->with($notification);
    }
}
