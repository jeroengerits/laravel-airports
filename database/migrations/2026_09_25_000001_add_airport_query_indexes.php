<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('airports', function (Blueprint $table): void {
            $table->index('iata_code');
            $table->index('gps_code');
            $table->index(['iso_country', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table): void {
            $table->dropIndex(['iata_code']);
            $table->dropIndex(['gps_code']);
            $table->dropIndex(['iso_country', 'type']);
        });
    }
};
