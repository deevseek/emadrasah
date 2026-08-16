<?php
declare(strict_types=1);
namespace App\Services\Finance;
use Illuminate\Support\Facades\Http;
class BriConnectionService
{
    public function __construct(private BriConfigurationService $configuration){}
    /** @return array{success:bool,message:string} */
    public function test():array
    {
        if(!$this->configuration->enabled())return ['success'=>false,'message'=>'Integrasi BRI belum diaktifkan.'];
        if(!$this->configuration->clientId())return ['success'=>false,'message'=>'Client ID belum dikonfigurasi.'];
        if(!$this->configuration->privateKey())return ['success'=>false,'message'=>'Private Key belum tersedia.'];
        if(!$this->configuration->baseUrl())return ['success'=>false,'message'=>'Base URL belum dikonfigurasi.'];
        $timestamp=now()->utc()->format('Y-m-d\TH:i:s.v\Z');
        $signature='';
        if(!openssl_sign($this->configuration->clientId().'|'.$timestamp,$signature,$this->configuration->privateKey(),OPENSSL_ALGO_SHA256))return ['success'=>false,'message'=>'Private Key tidak valid.'];
        try{$response=Http::asJson()->timeout(15)->withHeaders(['X-CLIENT-KEY'=>$this->configuration->clientId(),'X-TIMESTAMP'=>$timestamp,'X-SIGNATURE'=>base64_encode($signature)])->post(rtrim($this->configuration->baseUrl(),'/').'/snap/v1.0/access-token/b2b',['grantType'=>'client_credentials']);}
        catch(\Throwable){return ['success'=>false,'message'=>'BRI tidak dapat dihubungi.'];}
        return $response->successful()?['success'=>true,'message'=>'Terhubung']:['success'=>false,'message'=>$response->status()===401||$response->status()===403?'Autentikasi ditolak.':'BRI tidak dapat dihubungi.'];
    }
}
