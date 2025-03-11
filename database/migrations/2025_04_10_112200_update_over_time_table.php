<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('over_time', function (Blueprint $table) {
            // Drop the 'date' column
            $table->dropColumn('date');
            
            // Change 'from' and 'to' columns to 'datetime' and make them nullable
            $table->dateTime('from')->nullable()->change();  // Change 'from' to 'datetime' and make it nullable
            $table->dateTime('to')->nullable()->change();    // Change 'to' to 'datetime' and make it nullable
            
            // Make 'reason' column nullable
            $table->string('reason')->nullable()->change();  // Make 'reason' column nullable
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('over_time', function (Blueprint $table) {
                // Add back the 'date' column
                $table->date('date');
            
                // Revert 'from' and 'to' columns to 'time' and make them non-nullable
                $table->time('from')->nullable(false)->change();  // Change 'from' back to 'time' and make it not nullable
                $table->time('to')->nullable(false)->change();    // Change 'to' back to 'time' and make it not nullable
                
                // Revert 'reason' column to non-nullable
                $table->string('reason')->nullable(false)->change();
        });
    }
};
