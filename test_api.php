<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = App\Models\User::whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'pustakawan', 'kurator']))->first();
    $request = Illuminate\Http\Request::create('/api/staff/collections/LIB-BK-2024-0001', 'GET');
    $request->setUserResolver(fn() => $user);
    $controller = app(App\Http\Controllers\Api\StaffCollectionController::class);
    $response = $controller->show($request, 'LIB-BK-2024-0001');
    echo "STATUS: " . $response->status() . "\n";
    echo "CONTENT: " . substr($response->getContent(), 0, 500) . "...\n";
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
}
