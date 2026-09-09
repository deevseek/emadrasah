<x-layouts.app :title="$title" :breadcrumbs="['HRD', 'Absensi Saya']">
<x-ui.card class="attendance-mobile-shell mx-auto max-w-xl">
    <div class="attendance-identity text-center">
        <p class="font-bold text-emerald-950 sm:text-lg">{{ $personnel->full_name }}</p>
        <p class="text-xs text-slate-500 sm:text-sm">{{ now()->translatedFormat('l, d F Y') }} · Shift {{ $personnel->default_shift_number }}</p>
    </div>

    <div class="attendance-result rounded-xl bg-slate-50 px-3 py-2 text-center text-sm" data-result>
        @if(!$attendance)
            <p>Belum check-in</p>
        @else
            <p>Masuk <b>{{ $attendance->check_in_time?->format('H:i') }}</b> · Pulang <b>{{ $attendance->check_out_time?->format('H:i') ?? 'Belum' }}</b></p>
        @endif
    </div>

    @if($security['hrd_attendance_face_enabled'])
        <div class="attendance-camera-section" data-camera-panel>
            <x-face-camera-guide class="attendance-camera" video-id="attendance-face-video" status-id="attendance-camera-status" status="Menyiapkan kamera…" />
            <canvas id="attendance-face-canvas" class="hidden"></canvas>
            <p class="attendance-camera-hint text-center text-[11px] leading-4 text-slate-500">Hadap ke kamera <span aria-hidden="true">•</span> pencahayaan cukup <span aria-hidden="true">•</span> jangan terlalu jauh</p>
        </div>
    @endif

    <div class="attendance-progress text-sm" aria-label="Tahapan absensi">
        <p class="attendance-progress__title font-bold text-emerald-950">{{ !$attendance ? 'ABSENSI MASUK' : 'ABSENSI PULANG' }}</p>
        <div class="attendance-progress__grid">
            <p>✓ Akun Personalia</p>
            <p data-device>○ Perangkat</p>
            @if($security['hrd_attendance_face_enabled'])<p data-face>○ Wajah</p>@endif
            @if($security['hrd_attendance_location_enabled'])<p data-location>○ Lokasi</p>@endif
            <p class="hidden" data-submit>○ Mengirim absensi</p>
        </div>
    </div>

    <div class="attendance-action">
        <p class="attendance-error hidden" data-error role="alert"></p>
        <button type="button" class="btn btn-primary attendance-action__button w-full" data-attendance @disabled($attendance?->check_out_time)>{{ !$attendance ? 'CHECK-IN' : 'CHECK-OUT' }}</button>
        <p class="attendance-privacy text-center text-[11px] leading-4 text-slate-500">Foto terbaik dari pemindaian disimpan privat sebagai bukti. Kamera langsung bukan pemeriksaan liveness.</p>
    </div>
</x-ui.card>
<script>
(()=>{const button=document.querySelector('[data-attendance]');if(!button)return;const csrf=document.querySelector('meta[name="csrf-token"]')?.content,action=@js(!$attendance?'check_in':'check_out'),endpoint=@js(!$attendance?route('hrd.attendance.check-in',[],false):route('hrd.attendance.check-out',[],false)),challengeUrl=@js(route('hrd.attendance.challenge',[],false)),faceUrl=@js(route('hrd.attendance.face',[],false)),faceRequired=@js((bool)$security['hrd_attendance_face_enabled']),locationRequired=@js((bool)$security['hrd_attendance_location_enabled']),maxAccuracy=@js((int)$security['hrd_attendance_max_accuracy_meter']),video=document.getElementById('attendance-face-video'),canvas=document.getElementById('attendance-face-canvas'),cameraStatus=document.querySelector('[data-camera-status]'),camera=document.querySelector('[data-face-camera]'),error=document.querySelector('[data-error]');let stream=null,currentDevice=null;
 const set=(name,text)=>{const el=document.querySelector(`[data-${name}]`);if(el){el.textContent=text;if(name==='submit')el.classList.remove('hidden')}},sleep=ms=>new Promise(resolve=>setTimeout(resolve,ms));
 function cameraMessage(e){return({NotAllowedError:'Izin kamera ditolak. Izinkan kamera pada pengaturan peramban lalu coba lagi.',NotFoundError:'Kamera depan tidak ditemukan pada perangkat ini.',NotReadableError:'Kamera sedang digunakan aplikasi lain atau tidak dapat dibaca.',OverconstrainedError:'Kamera tidak mendukung konfigurasi yang diminta.'})[e.name]||'Kamera tidak dapat dibuka. Silakan periksa perangkat Anda.'}
 function stopCamera(){stream?.getTracks().forEach(track=>track.stop());stream=null;if(video)video.srcObject=null}
 async function startCamera(){if(!faceRequired)return;if(!window.isSecureContext&&!['localhost','127.0.0.1'].includes(location.hostname))throw new Error('Absensi kamera memerlukan koneksi HTTPS.');if(!navigator.mediaDevices?.getUserMedia)throw new Error('Peramban ini tidak mendukung akses kamera.');camera.dataset.guideState='ready';cameraStatus.textContent='Menyiapkan kamera…';try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:1280},height:{ideal:720}},audio:false});video.srcObject=stream;await video.play();cameraStatus.textContent='Posisikan wajah di dalam area.'}catch(e){stopCamera();camera.dataset.guideState='error';throw new Error(cameraMessage(e))}}
 function uuid(){let id=localStorage.getItem('attendance_device_uuid');if(!id){id=crypto.randomUUID();localStorage.setItem('attendance_device_uuid',id)}return id}function device(){return{device_uuid:uuid(),device_name:navigator.platform||'Perangkat pribadi',browser:navigator.userAgentData?.brands?.[0]?.brand||'Browser',platform:navigator.userAgentData?.platform||navigator.platform}}
 async function post(url,body,isForm=false){let response;try{response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json',...(isForm?{}:{'Content-Type':'application/json'})},body:isForm?body:JSON.stringify(body)})}catch{throw new Error('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.')}const json=await response.json().catch(()=>null);if(!response.ok)throw new Error(json?.error?.message||Object.values(json?.errors||{}).flat()[0]||json?.message||'Permintaan gagal.');return json.data}
 function capture(){if(!video.videoWidth)throw new Error('Kamera belum siap. Tunggu beberapa saat.');const scale=Math.min(1,1280/Math.max(video.videoWidth,video.videoHeight));canvas.width=Math.round(video.videoWidth*scale);canvas.height=Math.round(video.videoHeight*scale);canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);return new Promise((resolve,reject)=>canvas.toBlob(blob=>blob?resolve(blob):reject(new Error('Frame wajah gagal diambil.')),'image/jpeg',.88))}
 async function burst(){camera.dataset.guideState='scanning';const frames=[];for(let i=0;i<5;i++){cameraStatus.textContent=`Memindai wajah… ${i+1}/5`;frames.push(await capture());if(i<4)await sleep(200)}return frames}
 function locate(){return new Promise((resolve,reject)=>navigator.geolocation.getCurrentPosition(resolve,reject,{enableHighAccuracy:true,maximumAge:0,timeout:15000}))}
 button.addEventListener('click',async()=>{button.disabled=true;error.classList.add('hidden');try{button.textContent='MEMERIKSA…';if(faceRequired&&!stream)await startCamera();set('device','● Memeriksa perangkat');const d=device(),attendance={...d};currentDevice=d;const challenge=await post(challengeUrl,{action,...d});set('device','✓ Perangkat dikenali');if(faceRequired){button.textContent='MEMVERIFIKASI…';set('face','● Memindai wajah');const frames=await burst();set('face','● Memeriksa kecocokan');cameraStatus.textContent='Memverifikasi wajah…';const form=new FormData();Object.entries({...d,challenge_id:challenge.id,nonce:challenge.nonce}).forEach(([k,v])=>form.append(k,v));frames.forEach((frame,i)=>form.append('snapshots[]',frame,`frame-${i+1}.jpg`));await post(faceUrl,form,true);set('face','✓ Wajah terverifikasi');camera.dataset.guideState='verified';cameraStatus.textContent='Wajah berhasil diverifikasi.'}if(locationRequired){button.textContent='MENDAPATKAN LOKASI…';set('location','● Mendapatkan lokasi');let position;for(let i=0;i<3;i++){position=await locate();if(position.coords.accuracy<=maxAccuracy)break}if(position.coords.accuracy>maxAccuracy)throw new Error('Akurasi GPS masih rendah. Pindah ke area terbuka lalu coba lagi.');set('location',`✓ Lokasi · ${Math.round(position.coords.accuracy)} m`);const c=position.coords;Object.assign(attendance,{latitude:c.latitude,longitude:c.longitude,accuracy:c.accuracy,location_captured_at:new Date(position.timestamp).toISOString(),altitude:c.altitude,altitude_accuracy:c.altitudeAccuracy,heading:c.heading,speed:c.speed})}button.textContent='MENYIMPAN ABSENSI…';set('submit','● Mengirim absensi');const result=await post(endpoint,{...attendance,challenge_id:challenge.id,nonce:challenge.nonce});set('submit','✓ Absensi berhasil');stopCamera();document.querySelector('[data-result]').innerHTML=`<p class="font-bold text-emerald-800">✓ ${result.message} · ${result.time}</p>`;setTimeout(()=>location.reload(),1500)}catch(e){camera.dataset.guideState='error';error.textContent=e.message+(e.message.includes('Perangkat belum disetujui')&&currentDevice?` UUID perangkat: ${currentDevice.device_uuid}.`:'');error.classList.remove('hidden');if(stream)cameraStatus.textContent='Verifikasi gagal. Silakan coba kembali.';button.textContent='COBA LAGI';button.disabled=false}});window.addEventListener('pagehide',stopCamera);document.addEventListener('visibilitychange',()=>{if(document.hidden)stopCamera()});if(faceRequired)startCamera().catch(e=>{error.textContent=e.message;error.classList.remove('hidden');button.disabled=true})})();
</script>
</x-layouts.app>
