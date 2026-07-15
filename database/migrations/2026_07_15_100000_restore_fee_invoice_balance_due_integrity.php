<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `balance_due` now represents the immutable net amount billed (amount − discount).
     * An earlier reconcile path decremented it (and could zero it out), so realign every
     * existing row. The amount still owed is derived at read time as balance_due − amount_paid.
     */
    public function up(): void
    {
        DB::table('fee_invoices')->update([
            'balance_due' => DB::raw('amount - discount'),
        ]);
    }

    public function down(): void
    {
        // No-op: the previous mutable balance_due values cannot be reconstructed.
    }
};
