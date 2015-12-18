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
    register_setting('bulutfon-settings','bulutfon_notify_onNewUser' );
    register_setting('bulutfon-settings','bulutfon_sms_cronCount' );
    register_setting('bulutfon-settings','bulutfon_sms_baslik' );
    register_setting('bulutfon-settings','bulutfon_sms_numaralar' );
    register_setting('bulutfon-settings','ayar_sms_cronCount' );
    register_setting('bulutfon-settings','bulutfon_secureKey' );
}










