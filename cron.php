<?php
include '../../../wp-load.php';
include 'bulutfon/autoload.php';

$secureKey = get_option('bulutfon_secureKey');
$action = "cron";
if(isset($_GET["action"])){
    $action = $_GET["action"];
}
$token = $_GET["token"];

if($token == $secureKey){

    global $wpdb;


    $adet = get_option("ayar_sms_cronCount");
    $adet = empty($adet) ? 10 : $adet;
    $bekleyenlerSql = "select * from %prefix%sms_queue sq left join %prefix%sms_template st ON sq.template_id = st.id  where st.status=1 limit 0,$adet";
    $bekleyenlerSql = str_replace(array('%prefix%'),array($wpdb->prefix),$bekleyenlerSql);



    $smsQs =  $wpdb->get_results($bekleyenlerSql);


    $masterToken = get_option('bulutfon_masterKey');
    $smsBaslik = get_option('bulutfon_sms_baslik');
    $provider = new \Bulutfon\OAuth2\Client\Provider\Bulutfon(array(
        'verifySSL'=>false
    ));

    $ac = new \League\OAuth2\Client\Token\AccessToken(array('access_token'=>$masterToken));


    if(is_array($smsQs)){
        foreach($smsQs as $sms){
            $arguments = json_decode($sms->arguments,true);
            $content = $sms->content;


            if($arguments){
                foreach($arguments as $arKey => $arValue){
                    $content = str_replace("{$arKey}",$arValue,$content);
                }
            }

            $m = array(
                'title'=>$smsBaslik,
                'receivers'=>'90'.$sms->phone_number,
                'content'=>$content
            );


            $sonuc =(array) $provider->sendMessage($ac,$m);
            if(isset($sonuc["message"]) && $sonuc["message"]=="Messages created successfully"){
                $sorgu = "update ". $wpdb->prefix."sms_queue set sms_content='$content',status=2";
                $wpdb->query($sorgu);
            }
        }
    }


}else{
    echo "Geçersiz Token Key";
}