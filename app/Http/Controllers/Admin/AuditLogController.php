<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        if ($request->has('record_id')) {
            $query->where('record_id', $request->record_id);
        }

        $logs = $query->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog)
    {
        return response()->json($auditLog->load('user'));
    }

    /**
     * Get audit log statistics.
     */
    public function statistics(Request $request)
    {
        $query = AuditLog::query();

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $totalActions = $query->count();

        $byModule = AuditLog::selectRaw('module, COUNT(*) as count')
            ->groupBy('module')
            ->get()
            ->pluck('count', 'module');

        $byAction = AuditLog::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->get()
            ->pluck('count', 'action');

        $byUser = AuditLog::with('user')
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'total_actions' => $totalActions,
            'by_module' => $byModule,
            'by_action' => $byAction,
            'top_users' => $byUser,
        ]);
    }

    /**
     * Export audit logs to CSV.
     */
    public function export(Request $request)
    {
        $query = AuditLog::with('user');

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('module')) {
            $query->where('module', $request->module);
        }
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $csvData = "ID,User,Action,Module,Table,Record ID,IP Address,Created At\n";

        foreach ($logs as $log) {
            $userName = $log->user?->name ?? 'N/A';
            $csvData .= "{$log->id},\"{$userName}\",{$log->action},{$log->module},{$log->table_name},{$log->record_id},{$log->ip_address},{$log->created_at}\n";
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
    }
}
