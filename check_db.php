<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

// Check if tables exist
$tables = DB::select("SHOW TABLES LIKE 'pasiens'");
echo "pasiens exists: " . (empty($tables) ? 'NO' : 'YES') . PHP_EOL;

$tables2 = DB::select("SHOW TABLES LIKE 'patients'");
echo "patients exists: " . (empty($tables2) ? 'NO' : 'YES') . PHP_EOL;

$cols = DB::select("SHOW COLUMNS FROM medical_records WHERE Field = 'patient_id'");
echo json_encode($cols[0] ?? 'not found');
echo PHP_EOL;

if (!empty($tables)) {
    $pasiens = DB::table('pasiens')->get();
    echo "pasiens count: " . $pasiens->count() . PHP_EOL;
    foreach ($pasiens as $r) { echo $r->id . ": " . $r->nama . PHP_EOL; }
}
