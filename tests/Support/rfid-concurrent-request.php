<?php

// Worker HTTP untuk database test khusus, tidak pernah untuk server aktif.
declare(strict_types=1);
use App\Models\RfidAttendanceEvent;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'rfid_test_concurrency') {
    exit(2);
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
[$script, $directory, $index, $deviceId, $json] = $argv;
RfidAttendanceEvent::creating(function () use ($directory, $index): void {
    touch($directory.'/entered-'.$index);
    $deadline = microtime(true) + 10;
    while (! is_file($directory.'/release') && microtime(true) < $deadline) {
        usleep(10000);
    }
});
touch($directory.'/ready-'.$index);
$deadline = microtime(true) + 10;
while (! is_file($directory.'/start') && microtime(true) < $deadline) {
    usleep(10000);
}
$request = Request::create('/api/rfid/attendance', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
    'HTTP_X_DEVICE_ID' => $deviceId, 'HTTP_X_DEVICE_TOKEN' => 'concurrency-test-'.$deviceId,
], $json);
$response = $kernel->handle($request);
echo json_encode(['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)], JSON_THROW_ON_ERROR);
$kernel->terminate($request, $response);
