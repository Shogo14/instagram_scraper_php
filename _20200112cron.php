<?php

require __DIR__ . '/mysqltest.php'; 
require __DIR__ . '/vendor/autoload.php';    //ライブラリロード

//use
use Goutte\Client;

//インスタンス生成
$client = new Client();
$DB_Adapter = new DB_Adapter;
$instagram = new \InstagramScraper\Instagram();

//1ユーザーごとに最新投稿から取得する投稿の数値
$getPostCountByUser = (int)$DB_Adapter->getPostCountByUser()['param_value'];
//1周回ごとに取得する投稿の限界値
$onceGetLimit       = (int)$DB_Adapter->getOnceGetPostLimit()['param_value'];
//1投稿を取得する度に待つ秒数
$onceGetWaitSecond  = (int)$DB_Adapter->getOnceGetWaitSecond()['param_value'];
//限界値を取得するたびに待つ秒数
$limitGetWaitSecond = (int)$DB_Adapter->getLimitGetWaitSecond()['param_value'];

$insta_user_datas = $DB_Adapter->getAccountData();
foreach($insta_user_datas as $insta_user_data){
    $insta_user_id = $insta_user_data["profinfo13"];
    $data_name2 = $insta_user_data["name2"];
    echo "--------{$insta_user_id}--------\n";
    $isPrivate = 0;
    try {
        $isPrivate = $instagram->getAccount($insta_user_id)->isPrivate();
        $user_medias = $instagram->getMedias($insta_user_id, $getPostCountByUser);
    } catch (InstagramScraper\Exception\InstagramNotFoundException $e) {
        echo '捕捉した例外: ',  $e->getMessage(), "\n";
        $DB_Adapter->InsertNotGetAccountList($insta_user_id,$data_name2,'Delete');
        $user_medias = [];
    }
    if($isPrivate == 1){
        echo "非公開ユーザー:{$insta_user_id} \n";
        $DB_Adapter->InsertNotGetAccountList($insta_user_id,$data_name2,'Private');
        $user_medias = [];
    }
    foreach($user_medias as $user_media){
        $shortcode = $user_media->getShortCode();
        $mediatype = $user_media->getType();
        $media_post_id = $user_media->getId();
        $caption = $user_media->getCaption();
        $posted_date = date('Y/m/d H:i:s', $user_media->getCreatedTime());
        $exist_post = $DB_Adapter->AlreadyExistPost($media_post_id);
        if(!$exist_post){
            $media_url = "https://www.instagram.com/p/".$shortcode;
            $arrMedia_src = array();
            echo "Shortcode: {$shortcode}\n";
            echo "Media type (video or image): {$mediatype}\n";
    
            switch ($mediatype) {
                case 'video':
                    $crawler = $client->request('GET',$media_url);
                    $video_src = $crawler->filterXPath('//meta[@property="og:video"]')->attr('content');
                    $arrMedia_src[$video_src] = $mediatype;
                    break;
                case 'image':
                    $image_src = $user_media->getImageHighResolutionUrl();
                    $arrMedia_src[$image_src] = $mediatype;
                    break;
                case 'sidecar':
                    $sidecar_medias = $instagram->getMediaByUrl($media_url)->getSidecarMedias();
                    foreach($sidecar_medias as $sidecar_media){
                        $type = $sidecar_media['type'];
                        if($type == 'video'){
                            $media_src = $sidecar_media['videoStandardResolutionUrl'];
                        }elseif($type == 'image'){
                            $media_src = $sidecar_media['imageHighResolutionUrl'];
                        }
                        $arrMedia_src[$media_src] = $type;
                    }
                    break;
            }

            $DB_Adapter->InsertPosts($media_post_id, $data_name2, $caption, $insta_user_id, $posted_date);
            foreach($arrMedia_src as $media_src => $media_type){
                $DB_Adapter->InsertPostMedia($media_post_id,$media_type,$media_src);
            }
        }else{
            echo "Already exist!!\n";
        }
        echo "limit!: {$onceGetLimit}\n";
        if($onceGetLimit == 0){
            sleep($limitGetWaitSecond);
            $onceGetLimit = (int)$DB_Adapter->getOnceGetPostLimit()['param_value'];
        }else{
            sleep($onceGetWaitSecond);
            --$onceGetLimit;
        }
    }
}
