<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\Customer;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Odp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WorkOrderController extends Controller
{
    public function __construct()
    {
        // Permission middleware
        $this->middleware('permission:view work orders')->only(['index', 'show']);
        $this->middleware('permission:create work orders')->only(['create', 'store', 'addItem']);
        $this->middleware('permission:update work order status')->only(['updateStatus']);
        $this->middleware('permission:edit work orders')->only(['removeItem']);
    }

    public function index(Request $request)
    {
        $query = WorkOrder::with(['customer', 'technician']);

        if ($request->has('status') && $request->status != '') {
            $query->where('status', '=', $request->status, 'and');
        }

        if ($request->has('technician_id') && $request->technician_id != '') {
            $query->where('technician_id', '=', $request->technician_id, 'and');
        }

        $workOrders = $query->latest()->paginate(10);
        
        $technicians = User::technicians()->get(); 

        $stats = [
            'pending' => WorkOrder::where('status', '=', 'pending', 'and')->count(['*']),
            'in_progress' => WorkOrder::where('status', '=', 'in_progress', 'and')->count(['*']),
            'completed' => WorkOrder::where('status', '=', 'completed', 'and')->whereDate('completed_date', '=', today())->count(['*']),
        ];

        return view('work-orders.index', compact('workOrders', 'technicians', 'stats'));
    }

    public function create()
    {
        $customers = Customer::all(['*']);
        $technicians = User::technicians()->get(['*']);
        $odps = Odp::where('status', '=', 'active', 'and')->get(['*']); // Get active ODPs
        return view('work-orders.create', compact('customers', 'technicians', 'odps'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'type' => 'required|in:installation,repair,dismantling,survey,maintenance',
            'priority' => 'required|in:low,medium,high,critical',
            'scheduled_date' => 'nullable|date',
            'technician_id' => 'nullable|exists:users,id',
            'odp_id' => 'nullable|exists:odps,id',
            'description' => 'required|string',
        ]);

        $validated['status'] = $validated['technician_id'] ? 'scheduled' : 'pending';

        WorkOrder::create($validated);

        return redirect()->route('work-orders.index')->with('success', 'Work Order berhasil dibuat!');
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['customer', 'technician', 'items.inventoryItem']);
        $inventoryItems = InventoryItem::where('is_active', '=', true, 'and')->get(['*']);
        return view('work-orders.show', compact('workOrder', 'inventoryItems'));
    }

    public function updateStatus(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,scheduled,on_way,in_progress,completed,cancelled',
            'technician_notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'completed' && $workOrder->status !== 'completed') {
            $validated['completed_date'] = now();
            
            // Deduct stock for items used
            DB::transaction(function () use ($workOrder) {
                // Assuming deduction from Main Warehouse (Location ID 1) for simplicity now, 
                // in standard SOP strictly should be from Technician's Car (Location ID 2/3)
                // Let's use Location ID 1 (Gudang Utama) as default source for now.
                $sourceLocationId = 1; 

                foreach ($workOrder->items as $woItem) {
                    $stock = Stock::firstOrCreate(
                        ['inventory_item_id' => $woItem->inventory_item_id, 'location_id' => $sourceLocationId],
                        ['quantity' => 0]
                    );

                    if ($stock->quantity < $woItem->quantity) {
                        // For now allow negative stock or just log it, but standard is prevent.
                        // We will allow it but maybe warn. Let's process it.
                    }

                    $previousQty = $stock->quantity;
                    $stock->quantity -= $woItem->quantity;
                    $stock->save();

                    StockMovement::create([
                        'inventory_item_id' => $woItem->inventory_item_id,
                        'location_id' => $sourceLocationId,
                        'user_id' => Auth::id(),
                        'type' => 'out',
                        'quantity' => $woItem->quantity,
                        'previous_quantity' => $previousQty,
                        'new_quantity' => $stock->quantity,
                        'reference_number' => $workOrder->ticket_number,
                        'notes' => 'Used in Work Order ' . $workOrder->ticket_number,
                    ]);
                }
            });
        }

        $workOrder->update($validated);

        return back()->with('success', 'Status Work Order diperbarui!');
    }

    public function addItem(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.1',
            'notes' => 'nullable|string',
        ]);

        $item = InventoryItem::find($validated['inventory_item_id'], ['*']);
        
        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'inventory_item_id' => $item->id,
            'quantity' => $validated['quantity'],
            'unit' => $item->unit, // Copy unit from master
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Material ditambahkan ke WO!');
    }
    
    public function removeItem(WorkOrderItem $item)
    {
        WorkOrderItem::destroy($item->id);
        return back()->with('success', 'Material dihapus dari WO!');
    }
}
