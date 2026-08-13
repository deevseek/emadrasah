<?php
declare(strict_types=1);
namespace App\Services\Academic;
use App\Models\{Classroom,ClassroomJournal,User};use Illuminate\Auth\Access\AuthorizationException;use Illuminate\Support\Facades\DB;
class ClassroomJournalService
{
 public function ensureManage(User $user,Classroom $room):void{if($user->hasAnyRole(['super-admin','operator']))return;if(!$user->personnel||$room->homeroom_personnel_id!==$user->personnel->id)throw new AuthorizationException('Jurnal Kelas hanya dapat diubah oleh wali kelas rombel tersebut.');}
 public function save(array $data,User $user):ClassroomJournal{return DB::transaction(function()use($data,$user){$room=Classroom::findOrFail($data['classroom_id']);$this->ensureManage($user,$room);$journal=ClassroomJournal::where('classroom_id',$room->id)->whereDate('journal_date',$data['journal_date'])->first();$message=$journal?'Mengubah Jurnal Kelas':'Menyimpan Jurnal Kelas';if($journal)$journal->update($data+['updated_by'=>$user->id]);else $journal=ClassroomJournal::create($data+['recorded_by'=>$user->id]);activity('akademik')->causedBy($user)->performedOn($journal)->withProperties(['tanggal'=>$journal->journal_date->toDateString(),'rombel_id'=>$journal->classroom_id])->log($message);return $journal;});}
}
