<?php
global $activeTab;
$page = !isset($_GET['tab']) ? 'general':$_GET['tab'];

$pages['general'] = __DIR__.'/pages/general.php';
$pages['template_setting'] = __DIR__.'/pages/template_setting.php';
$pages['sms_queue'] = __DIR__.'/pages/sms_queue.php';
$pages['sms_bulk'] = __DIR__.'/pages/sms_bulk.php';
$pages['cdr'] = __DIR__.'/pages/cdr.php';
$pages['setting'] = __DIR__.'/pages/setting.php';

foreach($pages as $pKey=>$pValue){
    $activeTab[$pKey] = '';
}

$activeTab[$page] = 'nav-tab-active';

if(isset($pages[$page])){
    require $pages[$page];
    echo '</div>';
} else{
    echo 'Aradiginiz sayfayi bulamadik';
}



