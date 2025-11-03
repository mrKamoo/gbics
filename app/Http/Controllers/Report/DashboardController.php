<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Gbic;
use App\Models\NetworkSwitch;
use App\Models\PatchCord;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function stats()
    {
        $stats = [
            'gbics' => [
                'total' => Gbic::count(),
                'in_stock' => Gbic::where('status', 'in_stock')->count(),
                'assigned' => Gbic::where('status', 'assigned')->count(),
                'faulty' => Gbic::where('status', 'faulty')->count(),
            ],
            'switches' => [
                'total' => NetworkSwitch::count(),
                'deployed' => NetworkSwitch::where('status', 'deployed')->count(),
                'in_stock' => NetworkSwitch::where('status', 'in_stock')->count(),
                'maintenance' => NetworkSwitch::where('status', 'maintenance')->count(),
            ],
            'patch_cords' => [
                'total' => PatchCord::count(),
                'in_stock' => PatchCord::where('status', 'in_stock')->count(),
                'deployed' => PatchCord::where('status', 'deployed')->count(),
                'faulty' => PatchCord::where('status', 'faulty')->count(),
            ],
            'assignments' => [
                'active' => Assignment::whereNull('unassigned_at')->count(),
                'total' => Assignment::count(),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Get recent activity.
     */
    public function recentActivity()
    {
        $recentMovements = StockMovement::with(['movable', 'performedByUser', 'fromSite', 'toSite'])
            ->orderBy('performed_at', 'desc')
            ->limit(10)
            ->get();

        $recentAssignments = Assignment::with(['assignable', 'switch', 'assignedByUser'])
            ->orderBy('assigned_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'movements' => $recentMovements,
            'assignments' => $recentAssignments,
        ]);
    }

    /**
     * Get stock overview.
     */
    public function stockOverview()
    {
        $overview = [
            'gbics_by_status' => Gbic::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            'switches_by_status' => NetworkSwitch::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            'patch_cords_by_status' => PatchCord::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
        ];

        return response()->json($overview);
    }

    /**
     * Get alerts summary.
     */
    public function alertsSummary()
    {
        // This would return alerts when the Alert model is fully implemented
        return response()->json([
            'total' => 0,
            'unread' => 0,
            'critical' => 0,
            'warning' => 0,
        ]);
    }
}
