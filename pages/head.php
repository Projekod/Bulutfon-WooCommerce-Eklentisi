<?php
global $activeTab; ?>
<div class="wrap">
    <h2 class="nav-tab-wrapper">
        <a href="<?=menu_page_url( 'bulutfon/admin.php', false ); ?>&tab=general" class="nav-tab <?=$activeTab['general']; ?>">Bilgilendirme</a>
        <a href="<?=menu_page_url( 'bulutfon/admin.php', false ); ?>&tab=template_setting" class="nav-tab <?=$activeTab['template_setting']; ?>">Template Ayarları</a>
        <a href="<?=menu_page_url( 'bulutfon/admin.php', false ); ?>&tab=sms_queue" class="nav-tab <?=$activeTab['sms_queue']; ?>">Sms Kuyruğu</a>
        <a href="<?=menu_page_url( 'bulutfon/admin.php', false ); ?>&tab=sms_bulk" class="nav-tab <?=$activeTab['sms_bulk']; ?>">Toplu Sms Gönder</a>
        <a href="<?=menu_page_url( 'bulutfon/admin.php', false ); ?>&tab=cdr" class="nav-tab <?=$activeTab['cdr']; ?>">Arama Geçmişi</a>
        <a href="<?=menu_page_url( 'bulutfon/admin.php', false ); ?>&tab=setting" class="nav-tab <?=$activeTab['setting']; ?>">Ayarlar</a>
    </h2>

