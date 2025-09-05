<?php 
echo date_default_timezone_get()."\n";
date_default_timezone_set('Africa/Cairo');

$created_at = '2025-08-30 23:20:34';

$created_at = strtotime($created_at);
$nowTime = time();

echo $created_at."\n";
echo $nowTime."\n";
echo $nowTime - $created_at."\n";



if($nowTime - $created_at >= 60 * 15) {
    echo true;
}