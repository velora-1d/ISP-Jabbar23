<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin|admin|finance');
    }

    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['vendor', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total' => PurchaseOrder::count(),
            'draft' => PurchaseOrder::where('status', 'draft')->count(),
            'pending' => PurchaseOrder::where('status', 'pending')->count(),
            'received' => PurchaseOrder::where('status', 'received')->count(),
            'total_value' => PurchaseOrder::whereIn('status', ['approved', 'ordered', 'partial', 'received'])->sum('total'),
        ];

        return view('inventory.purchase-orders.index', compact('purchaseOrders', 'stats'));
    }

    public function create()
    {
        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();
        $inventoryItems = InventoryItem::orderBy('name')->get();
        $poNumber = PurchaseOrder::generatePoNumber();

        return view('inventory.purchase-orders.create', compact('vendors', 'inventoryItems', 'poNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $po = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generatePoNumber(),
            'vendor_id' => $validated['vendor_id'],
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_date'],
            'notes' => $validated['notes'],
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        foreach ($request->items as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'inventory_item_id' => $item['inventory_item_id'] ?? null,
                'item_name' => $item['item_name'],
                'item_code' => $item['item_code'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return redirect()->route('purchase-orders.show', $po)
            ->with('success', 'Purchase Order berhasil dibuat!');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items', 'creator', 'approver']);
        return view('inventory.purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return back()->with('error', 'PO tidak dapat diedit.');
        }

        $vendors = Vendor::where('status', 'active')->orderBy('name')->get();
        $inventoryItems = InventoryItem::orderBy('name')->get();
        $purchaseOrder->load('items');

        return view('inventory.purchase-orders.edit', compact('purchaseOrder', 'vendors', 'inventoryItems'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return back()->with('error', 'PO tidak dapat diedit.');
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $purchaseOrder->update([
            'vendor_id' => $validated['vendor_id'],
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_date'],
            'notes' => $validated['notes'],
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);

        // Delete existing items and recreate
        $purchaseOrder->items()->delete();

        foreach ($request->items as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'inventory_item_id' => $item['inventory_item_id'] ?? null,
                'item_name' => $item['item_name'],
                'item_code' => $item['item_code'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order berhasil diperbarui!');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'cancelled'])) {
            return back()->with('error', 'Hanya PO draft atau cancelled yang dapat dihapus.');
        }

        PurchaseOrder::destroy($purchaseOrder->id);
        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase Order berhasil dihapus!');
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Purchase Order disetujui!');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->update(['status' => 'cancelled']);
        return back()->with('success', 'Purchase Order dibatalkan.');
    }
}
