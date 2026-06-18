<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Migrations related to patient/pasien ===\n";
$rows = DB::table('migrations')->where('migration', 'like', '%patient%')->orWhere('migration', 'like', '%pasien%')->orderBy('migration')->get(['migration', 'batch']);
foreach ($rows as $r) { echo "  $r->migration (batch $r->batch)\n"; }

echo "\n=== Tables ===\n";
foreach (['pasiens', 'patients', 'medical_records'] as $t) {
    $exists = DB::select("SHOW TABLES LIKE '$t'");
    echo "  $t: " . (empty($exists) ? 'NOT FOUND' : 'EXISTS') . "\n";
}

echo "\n=== Pasien columns ===\n";
$cols = DB::select("SHOW COLUMNS FROM pasiens");
foreach ($cols as $c) { echo "  $c->Field ($c->Type)\n"; }

echo "\n=== Sample pasien ===\n";
$row = DB::table('pasiens')->first();
echo "  id=$row->id nama=$row->nama\n";

echo "\n=== MedicalRecords patient_id values ===\n";
$mrs = DB::table('medical_records')->select('id','patient_id')->take(5)->get();
foreach ($mrs as $r) { echo "  id=$r->id patient_id=$r->patient_id\n"; }
