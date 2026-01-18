<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin');
    }

    public function index()
    {
        $gateways = [
            'midtrans' => [
                'name' => 'Midtrans',
                'enabled' => Setting::get('midtrans_enabled', false),
                'server_key' => Setting::get('midtrans_server_key', ''),
                'client_key' => Setting::get('midtrans_client_key', ''),
                'is_production' => Setting::get('midtrans_is_production', false),
            ],
            'xendit' => [
                'name' => 'Xendit',
                'enabled' => Setting::get('xendit_enabled', false),
                'secret_key' => Setting::get('xendit_secret_key', ''),
                'public_key' => Setting::get('xendit_public_key', ''),
            ],
            'manual' => [
                'name' => 'Transfer Manual',
                'enabled' => Setting::get('manual_transfer_enabled', true),
                'bank_name' => Setting::get('manual_bank_name', ''),
                'account_number' => Setting::get('manual_account_number', ''),
                'account_name' => Setting::get('manual_account_name', ''),
            ],
        ];

        return view('settings.payment-gateways.index', compact('gateways'));
    }

    public function update(Request $request)
    {
        // Midtrans settings
        Setting::set('midtrans_enabled', $request->boolean('midtrans_enabled'));
        Setting::set('midtrans_server_key', $request->input('midtrans_server_key', ''));
        Setting::set('midtrans_client_key', $request->input('midtrans_client_key', ''));
        Setting::set('midtrans_is_production', $request->boolean('midtrans_is_production'));

        // Xendit settings
        Setting::set('xendit_enabled', $request->boolean('xendit_enabled'));
        Setting::set('xendit_secret_key', $request->input('xendit_secret_key', ''));
        Setting::set('xendit_public_key', $request->input('xendit_public_key', ''));

        // Manual Transfer settings
        Setting::set('manual_transfer_enabled', $request->boolean('manual_transfer_enabled'));
        Setting::set('manual_bank_name', $request->input('manual_bank_name', ''));
        Setting::set('manual_account_number', $request->input('manual_account_number', ''));
        Setting::set('manual_account_name', $request->input('manual_account_name', ''));

        return back()->with('success', 'Pengaturan payment gateway berhasil disimpan!');
    }

    public function testConnection(Request $request, string $gateway)
    {
        // In real implementation, this would test the actual gateway connection
        $success = match ($gateway) {
            'midtrans' => $this->testMidtrans(),
            'xendit' => $this->testXendit(),
            default => false,
        };

        if ($success) {
            return response()->json(['success' => true, 'message' => 'Koneksi berhasil!']);
        }

        return response()->json(['success' => false, 'message' => 'Koneksi gagal!'], 400);
    }

    private function testMidtrans(): bool
    {
        // Placeholder for actual Midtrans connection test
        $serverKey = Setting::get('midtrans_server_key', '');
        return !empty($serverKey);
    }

    private function testXendit(): bool
    {
        // Placeholder for actual Xendit connection test
        $secretKey = Setting::get('xendit_secret_key', '');
        return !empty($secretKey);
    }
}
