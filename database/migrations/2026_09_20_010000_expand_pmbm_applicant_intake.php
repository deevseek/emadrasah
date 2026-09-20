<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pmbm_applicants', function (Blueprint $table): void {
            $table->string('citizenship', 8)->nullable()->after('nik');
            $table->string('religion', 50)->nullable()->after('gender');
            $table->string('aspiration', 100)->nullable()->after('religion');
            $table->unsignedTinyInteger('child_order')->nullable()->after('aspiration');
            $table->unsignedTinyInteger('sibling_count')->nullable()->after('child_order');
        });
        Schema::create('pmbm_document_requirements', function (Blueprint $table): void {
            $table->id(); $table->string('name', 100); $table->string('code', 100)->unique();
            $table->boolean('is_required')->default(false); $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0); $table->timestamps();
        });
        DB::table('pmbm_document_requirements')->insert(array_map(fn ($row) => $row + ['created_at' => now(), 'updated_at' => now()], [['name'=>'Akta Kelahiran','code'=>'akta_kelahiran','is_required'=>true,'is_active'=>true,'sort_order'=>1],['name'=>'Kartu Keluarga','code'=>'kartu_keluarga','is_required'=>true,'is_active'=>true,'sort_order'=>2],['name'=>'KTP Ayah','code'=>'ktp_ayah','is_required'=>false,'is_active'=>true,'sort_order'=>3],['name'=>'KTP Ibu','code'=>'ktp_ibu','is_required'=>false,'is_active'=>true,'sort_order'=>4],['name'=>'KTP Wali','code'=>'ktp_wali','is_required'=>false,'is_active'=>true,'sort_order'=>5],['name'=>'Ijazah TK/RA','code'=>'ijazah_tk_ra','is_required'=>false,'is_active'=>true,'sort_order'=>6],['name'=>'Pas Foto','code'=>'pas_foto','is_required'=>false,'is_active'=>true,'sort_order'=>7]]));
    }
    public function down(): void
    {
        Schema::dropIfExists('pmbm_document_requirements');
        Schema::table('pmbm_applicants', function (Blueprint $table): void { $table->dropColumn(['citizenship','religion','aspiration','child_order','sibling_count']); });
    }
};
