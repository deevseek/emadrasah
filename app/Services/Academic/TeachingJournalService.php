<?php
declare(strict_types=1);
namespace App\Services\Academic;
use App\Models\{TeachingJournal,User};use Illuminate\Auth\Access\AuthorizationException;use Illuminate\Support\Facades\DB;
class TeachingJournalService
{
 public const NO_PERSONNEL='Akun Anda belum terhubung dengan Data Personalia. Hubungi Operator Madrasah.';
 public function save(array $data,User $user,?TeachingJournal $journal=null):TeachingJournal
 {return DB::transaction(function()use($data,$user,$journal){$personnel=$user->personnel;if(!$personnel||!$personnel->is_active)throw new AuthorizationException(self::NO_PERSONNEL);if($journal&&!$user->can('teaching-journals.view-all')&&$journal->personnel_id!==$personnel->id)throw new AuthorizationException('Anda hanya dapat mengubah jurnal milik sendiri.');$data['personnel_id']=$user->can('teaching-journals.view-all')&&isset($data['personnel_id'])?(int)$data['personnel_id']:$personnel->id;$target=\App\Models\Personnel::whereKey($data['personnel_id'])->where('is_active',true)->firstOrFail();$data['personnel_id']=$target->id;if($journal){$journal->update($data+['updated_by'=>$user->id]);$message='Mengubah Jurnal Mengajar';}else{$journal=TeachingJournal::create($data+['created_by'=>$user->id]);$message='Menyimpan Jurnal Mengajar';}activity('akademik')->causedBy($user)->performedOn($journal)->withProperties(['tanggal'=>$journal->journal_date->toDateString(),'rombel_id'=>$journal->classroom_id])->log($message);return $journal;});}
 public function delete(TeachingJournal $journal,User $user):void{if(!$user->can('teaching-journals.view-all')&&$journal->personnel_id!==$user->personnel?->id)throw new AuthorizationException;DB::transaction(function()use($journal,$user){activity('akademik')->causedBy($user)->withProperties(['tanggal'=>$journal->journal_date->toDateString(),'rombel_id'=>$journal->classroom_id])->log('Menghapus Jurnal Mengajar');$journal->delete();});}
}
