<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            if (!Schema::hasColumn('partners', 'code')) {
                $table->string('code')->unique()->after('name')->nullable();
            }
            if (!Schema::hasColumn('partners', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('partners', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('commission_rate');
            }
            if (!Schema::hasColumn('partners', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('partners', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        // Generate codes for existing partners
        $partners = DB::table('partners')->get();
        foreach ($partners as $partner) {
            if (empty($partner->code)) {
                DB::table('partners')->where('id', $partner->id)->update([
                    'code' => 'PRT-' . str_pad($partner->id, 4, '0', STR_PAD_LEFT)
                ]);
            }
        }

        // Now make code not nullable if it was added
        Schema::table('partners', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['code', 'address', 'status', 'notes', 'updated_at']);
        });
    }
};
