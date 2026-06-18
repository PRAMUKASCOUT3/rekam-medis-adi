<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$r = DB::selectOne("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medical_records' AND COLUMN_NAME = 'patient_id'");
if ($r) {
    echo "FK: " . $r->CONSTRAINT_NAME . PHP_EOL;
} else {
    echo "No FK found\n";
}

$cols = DB::select("SHOW COLUMNS FROM medical_records WHERE Field = 'patient_id'");
echo json_encode($cols);
