<?php

require __DIR__.'/db.php';

add_action( 'admin_menu', 'projekod_create_admin_menu' );

function projekod_create_admin_menu() {
    add_menu_page( 'Bulutfon WooCommerce Eklentisi', 'Bulutfon', 'manage_options', 'bulutfon/admin.php', '', plugins_url( 'bulutfon/images/menu_icon.png' ), 15 );
}

add_action( 'admin_init', 'projekod_register_settings' );

function projekod_register_settings(){
    register_setting('bulutfon-settings','bulutfon_masterKey' );
    register_setting('bulutfon-settings','bulutfon_notify_onOrderComplete' );
    register_setting('bulutfon-settings','bulutfon_notify_onOrderStatusChange' );
    register_setting('bulutfon-settings','bulutfon_notify_addOrderNote' );
    register_setting('bulutfon-settings','bulutfon_sms_cronCount' );
    register_setting('bulutfon-settings','bulutfon_sms_baslik' );
    register_setting('bulutfon-settings','bulutfon_sms_numaralar' );
    register_setting('bulutfon-settings','ayar_sms_cronCount' );
    register_setting('bulutfon-settings','bulutfon_secureKey' );
}

function action_order_status_completed( $order_id ) {
    global $wpdb;

    $argument = [
        'ad' => get_post_meta($order_id,'_billing_first_name',true),
        'soyad' => get_post_meta($order_id,'_billing_last_name',true),
    ];
    $link = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."sms_template WHERE name = 'Sipariş Onaylandı'");
    $wpdb->insert($wpdb->prefix.'sms_queue',[
        'date_added' => date('Y-m-d H:i:s'),
        'status' => '1',
        'sms_content' => null,
        'template_id' => $link->id,
        'phone_number' => get_post_meta($order_id,'_billing_phone',true),
        'arguments' => json_encode($argument)
    ]);
};

add_action( 'woocommerce_order_status_completed', 'action_order_status_completed', 10, 3 );

function action_new_customer_note($data){
    global $wpdb;

    $argument = [
        'ad' => get_post_meta($data['order_id'],'_billing_first_name',true),
        'soyad' => get_post_meta($data['order_id'],'_billing_last_name',true),
        'not' => $data['customer_note']
    ];

    $link = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."sms_template WHERE name = 'Sipariş Notu'");

    $wpdb->insert($wpdb->prefix.'sms_queue',[
        'date_added' => date('Y-m-d H:i:s'),
        'status' => '1',
        'sms_content' => null,
        'template_id' => $link->id,
        'phone_number' => get_post_meta($data['order_id'],'_billing_phone',true),
        'arguments' => json_encode($argument)
    ]);
}

add_action('woocommerce_new_customer_note', 'action_new_customer_note', 10, 1);

function action_new_order($order_id){
    global $wpdb;

    $order = wc_get_order($order_id);

    $argument = [
        'ad' => $order ->billing_first_name,
        'soyad' => $order ->billing_last_name,
    ];

    $link = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."sms_template WHERE name = 'Sipariş Oluşturuldu'");

    $wpdb->insert($wpdb->prefix.'sms_queue',[
        'date_added' => date('Y-m-d H:i:s'),
        'status' => '1',
        'sms_content' => null,
        'template_id' => $link->id,
        'phone_number' => $order ->billing_phone,
        'arguments' => json_encode($argument)
    ]);
}

add_action('woocommerce_order_details_after_order_table', 'action_new_order', 10, 1);








