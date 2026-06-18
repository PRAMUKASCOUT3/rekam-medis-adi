<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$c = DB::table('medical_records')->count();
echo "medical_records count: $c\n";

$rows = DB::table('medical_records')->select('id','patient_id')->take(5)->get();
echo json_encode($rows);
