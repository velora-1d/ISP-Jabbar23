<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin');
    }

    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by model type
        if ($request->filled('model_type')) {
            $query->where('model_type', 'like', '%' . $request->model_type . '%');
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(25);

        // Get unique actions for filter
        $actions = AuditLog::distinct()->pluck('action');

        // Get users who have logs
        $users = \App\Models\User::whereIn('id', AuditLog::distinct()->pluck('user_id'))->get(['id', 'name']);

        // Stats
        $stats = [
            'total_today' => AuditLog::whereDate('created_at', today())->count(),
            'creates' => AuditLog::where('action', 'create')->whereDate('created_at', today())->count(),
            'updates' => AuditLog::where('action', 'update')->whereDate('created_at', today())->count(),
            'deletes' => AuditLog::where('action', 'delete')->whereDate('created_at', today())->count(),
            'logins' => AuditLog::where('action', 'login')->whereDate('created_at', today())->count(),
        ];

        return view('admin.audit-logs.index', compact('logs', 'actions', 'users', 'stats'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');
        return view('admin.audit-logs.show', compact('auditLog'));
    }

    public function destroy(Request $request)
    {
        // Clear old logs (older than specified days, default 90)
        $days = $request->get('days', 90);
        
        $deleted = AuditLog::where('created_at', '<', now()->subDays($days))->delete();

        AuditLog::log('delete', "Cleared {$deleted} audit logs older than {$days} days");

        return back()->with('success', "{$deleted} log lama berhasil dihapus!");
    }
}
