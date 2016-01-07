<?php

delete_option('bulutfon_masterKey');
delete_option('bulutfon_notify_onOrderComplete');
delete_option('bulutfon_notify_onOrderStatusChange');
delete_option('bulutfon_notify_addOrderNote');
delete_option('bulutfon_sms_cronCount');
delete_option('bulutfon_sms_baslik');
delete_option('bulutfon_sms_numaralar');
delete_option('ayar_sms_cronCount');
delete_option('bulutfon_secureKey');

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sms_queue");
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sms_template");