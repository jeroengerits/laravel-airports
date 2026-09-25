<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable();
            $table->string('type')->nullable();
            $table->string('name');
            $table->decimal('latitude_deg', 10, 7)->nullable();
            $table->decimal('longitude_deg', 11, 7)->nullable();
            $table->integer('elevation_ft')->nullable();
            $table->string('continent')->nullable();
            $table->string('iso_country', 2)->nullable();
            $table->string('gps_code')->nullable();
            $table->string('iata_code', 3)->nullable();
            $table->string('local_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};
