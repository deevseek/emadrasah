<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 200);
            $table->string('short_name', 100)->nullable();
            $table->string('education_level', 100)->default('Madrasah Ibtidaiyah');
            $table->string('status')->nullable();
            $table->string('nsm', 20)->nullable();
            $table->string('npsn', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('village', 150)->nullable();
            $table->string('district', 150)->nullable();
            $table->string('city', 150)->nullable();
            $table->string('province', 150)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('head_name', 200)->nullable();
            $table->string('head_nip', 30)->nullable();
            $table->string('logo_path')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_profiles');
    }
};
