<?php
declare(strict_types=1);
namespace App\Services\Classrooms;
use App\Models\{Classroom,Personnel,Student,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ClassroomService {
 public function create(User $actor,array $data):Classroom{return DB::transaction(function()use($actor,$data){$this->validateHomeroom($data['homeroom_personnel_id']??null,(int)$data['academic_year_id']);$data['created_by']=$data['updated_by']=$actor->id;$c=Classroom::create($data);activity('kelas-rombel')->causedBy($actor)->performedOn($c)->log("Menambahkan rombel {$c->display_name}.");return $c;});}
 public function update(User $actor,Classroom $c,array $data):Classroom{return DB::transaction(function()use($actor,$c,$data){if(array_key_exists('homeroom_personnel_id',$data))$this->validateHomeroom($data['homeroom_personnel_id'],(int)($data['academic_year_id']??$c->academic_year_id),$c->id);$c->update([...$data,'updated_by'=>$actor->id]);activity('kelas-rombel')->causedBy($actor)->performedOn($c)->log("Memperbarui rombel {$c->display_name}.");return $c;});}
 public function setActive(User $actor,Classroom $c,bool $active):void{if(!$active&&$c->activeMemberships()->exists())throw ValidationException::withMessages(['classroom'=>'Rombel belum dapat dinonaktifkan karena masih memiliki siswa aktif.']);$c->update(['is_active'=>$active,'updated_by'=>$actor->id]);activity('kelas-rombel')->causedBy($actor)->performedOn($c)->log(($active?'Mengaktifkan':'Menonaktifkan')." rombel {$c->display_name}.");}
    public function assignHomeroom(User $actor, Classroom $c, ?int $id): void
    {
        DB::transaction(function () use ($actor, $c, $id) {
            $rooms = Classroom::where('academic_year_id', $c->academic_year_id)
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $room = $rooms->get($c->id);
            if (! $room) {
                throw ValidationException::withMessages(['homeroom_personnel_id' => 'Data rombel telah berubah. Muat ulang halaman sebelum menetapkan wali kelas.']);
            }
            $this->validateHomeroom($id, $room->academic_year_id, $room->id);
            $old = $room->homeroom_name;
            $room->update(['homeroom_personnel_id' => $id, 'updated_by' => $actor->id]);
            $room->refresh();
            activity('kelas-rombel')->causedBy($actor)->performedOn($room)
                ->withProperties(['wali_kelas_lama' => $old])
                ->log("Menetapkan {$room->homeroom_name} sebagai wali kelas {$room->display_name}.");
        });
    }

    public function swapHomerooms(User $actor, Classroom $classroom, int $targetId): void
    {
        DB::transaction(function () use ($actor, $classroom, $targetId) {
            // Kunci rombel dalam urutan tetap agar dua pertukaran bersamaan tidak saling menimpa.
            $rooms = Classroom::where('academic_year_id', $classroom->academic_year_id)
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $rooms->get($classroom->id);
            $target = $rooms->get($targetId);
            if (! $source || ! $target || $source->id === $target->id || ! $source->is_active || ! $target->is_active) {
                throw ValidationException::withMessages(['target_classroom_id' => 'Pilih rombel aktif lain pada tahun ajaran yang sama.']);
            }
            if (! $source->homeroom_personnel_id || ! $target->homeroom_personnel_id) {
                throw ValidationException::withMessages(['target_classroom_id' => 'Kedua rombel harus memiliki wali kelas untuk melakukan pertukaran.']);
            }
            $except = [$source->id, $target->id];
            $this->validateHomeroom($source->homeroom_personnel_id, $source->academic_year_id, $except);
            $this->validateHomeroom($target->homeroom_personnel_id, $source->academic_year_id, $except);
            $sourceTeacher = $source->homeroom_personnel_id;
            $targetTeacher = $target->homeroom_personnel_id;
            $source->update(['homeroom_personnel_id' => $targetTeacher, 'updated_by' => $actor->id]);
            $target->update(['homeroom_personnel_id' => $sourceTeacher, 'updated_by' => $actor->id]);
            foreach ([$source, $target] as $room) {
                activity('kelas-rombel')->causedBy($actor)->performedOn($room)
                    ->withProperties([
                        'rombel_pasangan_id' => $room->id === $source->id ? $target->id : $source->id,
                        'wali_kelas_lama_id' => $room->id === $source->id ? $sourceTeacher : $targetTeacher,
                        'wali_kelas_baru_id' => $room->homeroom_personnel_id,
                    ])->log('Menukar wali kelas antarrombel.');
            }
        });
    }
 public function validateHomeroom(?int $id,int $year,int|array|null $except=null):void{if(!$id)return;$p=Personnel::find($id);if(!$p||!$p->is_active)throw ValidationException::withMessages(['homeroom_personnel_id'=>'Personalia yang dipilih sedang nonaktif.']);if(!str($p->position)->lower()->contains(['guru','kepala madrasah']))throw ValidationException::withMessages(['homeroom_personnel_id'=>'Wali kelas harus merupakan Guru atau Kepala Madrasah.']);$exists=Classroom::where('academic_year_id',$year)->where('homeroom_personnel_id',$id)->where('is_active',true)->when($except,fn($q)=>$q->whereNotIn('id',(array)$except))->exists();if($exists)throw ValidationException::withMessages(['homeroom_personnel_id'=>'Guru tersebut sudah menjadi wali kelas pada rombel lain di tahun ajaran yang sama.']);}
 public function statistics(?int $year):array{if(!$year)return ['total'=>0,'placed'=>0,'unplaced'=>Student::where('status','active')->count(),'without_homeroom'=>0,'over_capacity'=>0];$rooms=Classroom::where('academic_year_id',$year)->withCount('activeMemberships')->get();$placed=\App\Models\ClassroomMembership::where('academic_year_id',$year)->where('status','active')->distinct('student_id')->count('student_id');return ['total'=>$rooms->count(),'placed'=>$placed,'unplaced'=>max(0,Student::where('status','active')->count()-$placed),'without_homeroom'=>$rooms->whereNull('homeroom_personnel_id')->count(),'over_capacity'=>$rooms->filter->is_over_capacity->count()];}
}
