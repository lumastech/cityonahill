<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the single reviewer `comment` into the reasons a decision actually carries:
 * a reject reason (why the plan was sent back) and a revert reason (why a decision
 * that had already been made was withdrawn). `comment` stays as the optional note a
 * reviewer leaves when approving.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->text('reject_reason')->nullable()->after('comment');
            $table->text('revert_reason')->nullable()->after('reject_reason');
            $table->foreignId('reverted_by')->nullable()->after('reviewed_by')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('reverted_at')->nullable()->after('reviewed_at');
        });

        // A rejected plan's feedback lived in `comment`; move it to its own column.
        DB::table('lesson_plans')->where('status', 'rejected')->whereNotNull('comment')
            ->update([
                'reject_reason' => DB::raw('comment'),
                'comment' => null,
            ]);

        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'reverted'])
                ->default('draft')->change();
        });
    }

    public function down(): void
    {
        DB::table('lesson_plans')->where('status', 'reverted')->update(['status' => 'rejected']);

        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                ->default('draft')->change();
        });

        DB::table('lesson_plans')->whereNotNull('reject_reason')
            ->update(['comment' => DB::raw('reject_reason')]);

        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reverted_by');
            $table->dropColumn(['reject_reason', 'revert_reason', 'reverted_at']);
        });
    }
};
