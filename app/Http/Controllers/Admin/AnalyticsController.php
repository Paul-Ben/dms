<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\VisitorActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser || !in_array($authUser->default_role, ['Admin', 'IT Admin'])) {
            return redirect()->route('dashboard')->with([
                'message' => 'Unauthorized: Admin only.',
                'alert-type' => 'error'
            ]);
        }

        $userDetail = UserDetails::where('user_id', $authUser->id)->first();
        $tenantId = optional($userDetail)->tenant_id;
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        // Filters
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'role' => 'nullable|string',
        ]);
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : Carbon::now()->endOfDay();
        $roleFilter = $request->input('role');

        // Resolve users in tenant
        $tenantUserIds = UserDetails::where('tenant_id', $tenantId)->pluck('user_id');
        if ($roleFilter) {
            $tenantUserIds = User::whereIn('id', $tenantUserIds)->where('default_role', $roleFilter)->pluck('id');
        }

        // Available roles for filter dropdown
        $availableRoles = User::whereIn('id', UserDetails::where('tenant_id', $tenantId)->pluck('user_id'))
            ->select('default_role')->distinct()->orderBy('default_role')->pluck('default_role')->toArray();

        // Login frequency (daily) — count POST /login events
        $loginEvents = VisitorActivity::whereIn('user_id', $tenantUserIds)
            ->whereBetween('created_at', [$from, $to])
            ->where('method', 'POST')
            ->where('url', 'like', '%/login%')
            ->get(['user_id', 'created_at']);

        $loginDailyCounts = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $label = $day->format('Y-m-d');
            $loginDailyCounts[$label] = 0;
        }
        foreach ($loginEvents as $ev) {
            $label = Carbon::parse($ev->created_at)->format('Y-m-d');
            if (isset($loginDailyCounts[$label])) {
                $loginDailyCounts[$label]++;
            }
        }

        // Feature usage — count Activity actions
        $featureUsageRows = Activity::whereIn('user_id', $tenantUserIds)
            ->whereBetween('created_at', [$from, $to])
            ->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();
        $featureUsageLabels = $featureUsageRows->pluck('action')->toArray();
        $featureUsageCounts = $featureUsageRows->pluck('count')->toArray();

        // Active minutes (approx): unique minute buckets per day from VisitorActivity
        $visitorRows = VisitorActivity::whereIn('user_id', $tenantUserIds)
            ->whereBetween('created_at', [$from, $to])
            ->get(['user_id', 'created_at']);
        $activeMinutesDaily = [];
        foreach ($loginDailyCounts as $label => $_) {
            $activeMinutesDaily[$label] = 0;
        }
        $seen = [];
        foreach ($visitorRows as $row) {
            $ts = Carbon::parse($row->created_at);
            $dayLabel = $ts->format('Y-m-d');
            $minuteBucket = $ts->format('Y-m-d H:i');
            $key = $dayLabel . '|' . $minuteBucket;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                if (isset($activeMinutesDaily[$dayLabel])) {
                    $activeMinutesDaily[$dayLabel]++;
                }
            }
        }

        // Actions performed per user
        $actionsPerUser = Activity::whereIn('user_id', $tenantUserIds)
            ->whereBetween('created_at', [$from, $to])
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->get();
        $usersById = User::whereIn('id', $actionsPerUser->pluck('user_id'))
            ->get(['id', 'name', 'default_role'])
            ->keyBy('id');

        // Summary metrics
        $userCount = count($tenantUserIds);
        $totalLogins = array_sum($loginDailyCounts);
        $totalActions = $featureUsageRows->sum('count');

        // Chart series
        $dailyLabels = array_keys($loginDailyCounts);
        $loginSeries = array_values($loginDailyCounts);
        $activeMinutesSeries = array_values($activeMinutesDaily);

        return view('admin.analytics.index', [
            'authUser' => $authUser,
            'tenant' => $tenant,
            'availableRoles' => $availableRoles,
            'roleFilter' => $roleFilter,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'userCount' => $userCount,
            'totalLogins' => $totalLogins,
            'totalActions' => $totalActions,
            'dailyLabels' => $dailyLabels,
            'loginSeries' => $loginSeries,
            'activeMinutesSeries' => $activeMinutesSeries,
            'featureUsageLabels' => $featureUsageLabels,
            'featureUsageCounts' => $featureUsageCounts,
            'actionsPerUser' => $actionsPerUser,
            'usersById' => $usersById,
        ]);
    }
}