<?php

function projekod_register_plugin(){
    projekod_create_options();
    projekod_create_tables();
    projekod_insert_sms_default_template();

}

function projekod_get_sms_queue(){
    global $wpdb;
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'sms_queue');
}

function projekod_get_sms_template($id = ''){
    global $wpdb;
    if($id == '') {
        return $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'sms_template');
    }else{
        return $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."sms_template WHERE id = '".$id."'");
    }
}

function projekod_get_sms_template_from_status($status){
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."sms_template WHERE status = '".$status."'");
}

function projekod_add_to_queue(){
    global $wpdb;
    if(isset($_POST) && $_POST){
        print_r($_POST);
        $argument = [];
        foreach($_POST['customer'] as $customer){
//            $argument['ad'] = $customer['first_name'];
//            $argument['soyad'] = $customer['last_name'];
//            $json = json_encode($argument);
//            $wpdb->insert($wpdb.'sms_queue',[
//                'date_added' => date('Y-m-d H:i:s'),
//                'status' => 1,
//                'sms_content' => null,
//                'template_id' => $_POST['sms_id'],
//                'phone_number' => $customer['telephone'],
//                'arguments' => $json
//            ]);
        }
    }
}

function projekod_add_to_sms_template(){
    global $wpdb;
    if(isset($_POST) && $_POST){
        foreach($_POST['templates'] as $key=>$template){
            $wpdb->update($wpdb->prefix.'sms_template', [
                'content' => $template,
                'date_added' => date('Y-m-d H:i:s')
            ],[
                'name' => $key
            ]);
        }

        $wpdb->insert($wpdb->prefix.'sms_template',[
            'name' => $_POST['sms_name'],
            'content' => $_POST['sms_content'],
            'status' => '1',
            'date_added' => date('Y-m-d H:i:s')
        ]);
    }
}

function projekod_get_all_customers(){
    $sql = "
        SELECT
    m1.meta_value as 'telephone',
    m2.meta_value as 'first_name',
    m3.meta_value as 'last_name',
    m4.meta_value as 'mail',
    p.post_title as 'title',
    p.post_date as 'date',
    p.id
    FROM %prefix%posts p
    LEFT JOIN %prefix%postmeta m1
     ON p.id = m1.post_id AND m1.meta_key = '_billing_phone'
    LEFT JOIN %prefix%postmeta m2
     ON p.id = m2.post_id AND m2.meta_key = '_billing_first_name'
    LEFT JOIN %prefix%postmeta m3
     ON p.id = m3.post_id AND m3.meta_key = '_billing_last_name'
    LEFT JOIN %prefix%postmeta m4
     ON p.id = m4.post_id AND m4.meta_key = '_billing_email'
    where m1.meta_value is not null
    group by m1.meta_value
    order by p.post_date asc
    ";

    global $wpdb;

    $sql= str_replace("%prefix%",$wpdb->prefix,$sql);
    $result = $wpdb->get_results($sql);
    return $result;

}


function projekod_get_user_from_number($number){

    $sql = "
        SELECT
    m1.meta_value as 'telephone',
    m2.meta_value as 'first_name',
    m3.meta_value as 'last_name',
    m4.meta_value as 'mail',
    p.post_title as 'title',
    p.post_date as 'date'
    FROM %prefix%posts p
    LEFT JOIN %prefix%postmeta m1
     ON p.id = m1.post_id AND m1.meta_key = '_billing_phone'
    LEFT JOIN %prefix%postmeta m2
     ON p.id = m2.post_id AND m2.meta_key = '_billing_first_name'
    LEFT JOIN %prefix%postmeta m3
     ON p.id = m3.post_id AND m3.meta_key = '_billing_last_name'
    LEFT JOIN %prefix%postmeta m4
     ON p.id = m4.post_id AND m4.meta_key = '_billing_email'
    where
     m1.meta_value like '%$number'";

    global $wpdb;

    $sql= str_replace("%prefix%",$wpdb->prefix,$sql);
    $result = $wpdb->get_results($sql);
    return $result;

}

function projekod_insert_sms_default_template(){
    global $wpdb;
    $link = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."sms_template WHERE name = 'Sipariş Oluşturuldu' or name = 'Sipariş Durumu Güncelledi'");
    if($link == null){
        $wpdb->insert($wpdb->prefix.'sms_template', [
            'date_added' => date('Y-m-d H:i:s'),
            'status' => '0',
            'name' => 'Sipariş Oluşturuldu',
            'content' => '{ad}{soyad}{fiyat}{siparis_numarasi}'
        ]);
        $wpdb->insert($wpdb->prefix.'sms_template', [
            'date_added' => date('Y-m-d H:i:s'),
            'status' => '0',
            'name' => 'Sipariş Durumu Güncelledi',
            'content' => '{siparis_durumu}{siparis_numarasi}'
        ]);
    }
}

function projekod_insert_queue(){
    global $wpdb;
    $wpdb->insert($wpdb->prefix.'sms_template', [
        'date_added' => date('Y-m-d H:i:s'),
        'status' => '1',
        'name' => 'Sipariş Oluşturuldu',
        'content' => '{ad}{soyad}{fiyat}{siparis_numarasi}'
    ]);
}

function projekod_create_options(){
    add_option('bulutfon_masterKey' );
    add_option('bulutfon_notify_onOrderComplete' );
    add_option('bulutfon_notify_onOrderStatusChange' );
    add_option('bulutfon_notify_onNewUser' );
    add_option('bulutfon_sms_cronCount' );
    add_option('bulutfon_sms_baslik' );
    add_option('bulutfon_sms_numaralar' );
    add_option('ayar_sms_cronCount' );
    add_option('bulutfon_secureKey', md5(uniqid().$_SERVER["HTTP_HOST"]));
}

function projekod_create_tables(){
    global $wpdb;

    $table1_name = $wpdb->prefix.'sms_queue';
    $table1_sql = "CREATE TABLE IF NOT EXISTS `" .$table1_name. "` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date_added` datetime NOT NULL,
          `status` tinyint(1) DEFAULT '1',
          `sms_content` text(2),
          `template_id` int(11) NOT NULL,
          `phone_number` text(2) NOT NULL,
          `arguments` text(2) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";

    $table2_name = $wpdb->prefix.'sms_template';
    $table2_sql = "CREATE TABLE IF NOT EXISTS `" .$table2_name."` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date_added` datetime,
          `status` tinyint(1) DEFAULT '1',
          `name` text(2) NOT NULL,
          `content` text(2) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    if($wpdb->get_var("show tables like '$table1_name'") != $table1_name) {
        dbDelta($table1_sql);
    }

    if($wpdb->get_var("show tables like '$table2_name'") != $table2_name) {
        dbDelta($table2_sql);
    }
}

projekod_register_plugin();

