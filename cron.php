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
    $bekleyenlerSql = "select sq.id, st.status, sq.sms_content, sq.phone_number, sq.arguments, st.content from %prefix%sms_queue sq left join %prefix%sms_template st ON sq.template_id = st.id  where sq.status=1 AND (st.status=1 OR st.status=0) limit 0,$adet";
    $bekleyenlerSql = str_replace(array('%prefix%'),array($wpdb->prefix),$bekleyenlerSql);

    $smsQs =  $wpdb->get_results($bekleyenlerSql);

    $masterToken = get_option('bulutfon_masterKey');
    $smsBaslik = get_option('bulutfon_sms_baslik');
    $provider = new \Bulutfon\OAuth2\Client\Provider\Bulutfon(array(
        'verifySSL'=>false
    ));

    $ac = new \League\OAuth2\Client\Token\AccessToken(array('access_token'=>$masterToken));

    if(is_array($smsQs) && !empty($smsQs)){
        foreach($smsQs as $sms){
            $arguments = json_decode($sms->arguments, true);
            $content = $sms->content;


            if ($arguments) {
                foreach ($arguments as $arKey => $arValue) {
                    $content = str_replace('{' . $arKey . '}', $arValue, $content);
                }
            }

            $m = array(
                'title' => $smsBaslik,
                'receivers' => '90'.substr($sms->phone_number, -10),
                'content' => $content
            );
            try {
                $sonuc = (array)$provider->sendMessage($ac, $m);
                echo $sonuc['message'] . "<br>";
                if (isset($sonuc["message"]) && $sonuc["message"] == "Messages created successfully") {
                    $sorgu = "update ".$wpdb->prefix."sms_queue set sms_content='$content',status=2 where id=$sms->id";
                    $wpdb->query($sorgu);
                }else{
                    throw new Exception($sonuc["message"]);
                }
            } catch (Exception $e){
                echo $e->getMessage();
                echo '<br>';
            }
        }
    }else{
        echo 'Gönderilmeyi bekleyen sms bulunamadı';
    }


}else{
    echo "Geçersiz Token Key";
}