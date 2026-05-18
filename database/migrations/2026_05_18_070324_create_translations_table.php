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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 12);
            $table->string('group', 100);
            $table->string('key', 191);
            $table->text('value');
            $table->timestamps();

            $table->unique(['locale', 'group', 'key']);
            $table->index(['locale', 'group']);
            $table->index(['locale', 'updated_at']);
            $table->index('key');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};