<?php

require __DIR__ . '/mysqltest.php'; 
require __DIR__ . '/vendor/autoload.php';    //ライブラリロード

//use
use Goutte\Client;

//インスタンス生成
$client = new Client();
$DB_Adapter = new DB_Adapter;

$start_count = $DB_Adapter->getStartCountData();
$max_count = $start_count + 9;
$count = 0;
$insta_user_datas = $DB_Adapter->getAccountData();
foreach($insta_user_datas as $insta_user_data){
    if($count >= $start_count && $count <= $max_count){
        // echo $count.":";
        $insta_user_id = $insta_user_data["profinfo13"];
        // echo "{$count}--------{$insta_user_id}--------\n";
        $DB_Adapter->InsertDataAcquireState($insta_user_id);
        if($count == $max_count){
            exit("Program End!!");
        } 
    }
    $count++;
}
echo "for each end";

