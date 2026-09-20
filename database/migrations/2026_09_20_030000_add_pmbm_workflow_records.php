<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('pmbm_settings', function (Blueprint $table): void {
            $table->boolean('require_fitting')->default(false)->after('offline_registration_enabled');
            $table->boolean('require_parent_interview')->default(false)->after('require_fitting');
            $table->boolean('require_child_observation')->default(false)->after('require_parent_interview');
            $table->boolean('require_headmaster_approval')->default(false)->after('require_child_observation');
            $table->boolean('require_payment_before_registration_complete')->default(true)->after('require_headmaster_approval');
        });
        Schema::create('pmbm_fittings', function (Blueprint $table): void {
            $table->id(); $table->foreignId('applicant_id')->unique()->constrained('pmbm_applicants')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable(); $table->string('location')->nullable(); $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attendance_status', 20)->default('scheduled'); $table->string('shirt_size', 20)->nullable(); $table->string('pants_size', 20)->nullable(); $table->string('skirt_size', 20)->nullable(); $table->string('hijab_size', 20)->nullable(); $table->string('cap_size', 20)->nullable(); $table->string('socks_size', 20)->nullable(); $table->json('other_sizes')->nullable(); $table->text('notes')->nullable(); $table->timestamp('completed_at')->nullable(); $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('pmbm_interview_questions', function (Blueprint $table): void {
            $table->id(); $table->string('code')->unique(); $table->text('question'); $table->string('type', 20)->default('textarea'); $table->json('options')->nullable(); $table->boolean('required')->default(false); $table->boolean('active')->default(true); $table->unsignedSmallInteger('sort_order')->default(0); $table->timestamps();
        });
        DB::table('pmbm_interview_questions')->insert(array_map(fn (array $row) => $row + ['created_at' => now(), 'updated_at' => now()], [
['code'=>'motivasi_madrasah','question'=>'Apa motivasi memilih madrasah ini?','type'=>'textarea','required'=>true,'active'=>true,'sort_order'=>1],
['code'=>'pendampingan_anak','question'=>'Bagaimana pola pendampingan anak di rumah?','type'=>'textarea','required'=>true,'active'=>true,'sort_order'=>2],
['code'=>'pendamping_belajar','question'=>'Siapa yang mendampingi anak belajar?','type'=>'text','required'=>true,'active'=>true,'sort_order'=>3],
['code'=>'tata_tertib','question'=>'Apakah keluarga siap mengikuti tata tertib madrasah?','type'=>'yes_no','required'=>true,'active'=>true,'sort_order'=>4],
['code'=>'informasi_anak','question'=>'Informasi penting tentang anak.','type'=>'textarea','required'=>false,'active'=>true,'sort_order'=>5],
['code'=>'komunikasi_orang_tua','question'=>'Bagaimana komunikasi orang tua dengan madrasah?','type'=>'textarea','required'=>false,'active'=>true,'sort_order'=>6],
['code'=>'hal_khusus','question'=>'Hal khusus yang perlu diketahui madrasah.','type'=>'textarea','required'=>false,'active'=>true,'sort_order'=>7],
]));
        Schema::create('pmbm_interviews', function (Blueprint $table): void {
            $table->id(); $table->foreignId('applicant_id')->unique()->constrained('pmbm_applicants')->cascadeOnDelete(); $table->timestamp('scheduled_at')->nullable(); $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('started_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->text('summary')->nullable(); $table->text('public_note')->nullable(); $table->text('internal_note')->nullable(); $table->timestamps();
        });
        Schema::create('pmbm_interview_answers', function (Blueprint $table): void {$table->id(); $table->foreignId('interview_id')->constrained('pmbm_interviews')->cascadeOnDelete(); $table->foreignId('question_id')->constrained('pmbm_interview_questions')->cascadeOnDelete(); $table->json('answer')->nullable(); $table->timestamps(); $table->unique(['interview_id','question_id']);});
        Schema::create('pmbm_observations', function (Blueprint $table): void {$table->id();$table->foreignId('applicant_id')->unique()->constrained('pmbm_applicants')->cascadeOnDelete();$table->foreignId('observer_id')->nullable()->constrained('users')->nullOnDelete();$table->timestamp('scheduled_at')->nullable();$table->timestamp('completed_at')->nullable();$table->text('summary')->nullable();$table->text('internal_note')->nullable();$table->string('result',30)->nullable();$table->timestamps();});
        Schema::create('pmbm_decisions', function (Blueprint $table): void {$table->id();$table->foreignId('applicant_id')->unique()->constrained('pmbm_applicants')->cascadeOnDelete();$table->string('decision',20);$table->text('public_note')->nullable();$table->text('internal_note')->nullable();$table->foreignId('decided_by')->constrained('users')->restrictOnDelete();$table->timestamp('decided_at');$table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();$table->timestamp('approved_at')->nullable();$table->unsignedInteger('waitlist_order')->nullable();$table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();$table->timestamp('promoted_at')->nullable();$table->text('promotion_reason')->nullable();$table->timestamps();});
    }
    public function down(): void { Schema::dropIfExists('pmbm_decisions');Schema::dropIfExists('pmbm_observations');Schema::dropIfExists('pmbm_interview_answers');Schema::dropIfExists('pmbm_interviews');Schema::dropIfExists('pmbm_interview_questions');Schema::dropIfExists('pmbm_fittings');Schema::table('pmbm_settings',fn(Blueprint $t)=>$t->dropColumn(['require_fitting','require_parent_interview','require_child_observation','require_headmaster_approval','require_payment_before_registration_complete'])); }
};
