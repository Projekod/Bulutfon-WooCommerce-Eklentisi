<?php require __DIR__.'/head.php'; ?>

<form method="post" action="options.php">

    <?php settings_fields( 'bulutfon-settings' ); ?>
    <?php do_settings_sections( 'bulutfon-settings' ); ?>
    <table class="form-table">
        <tr><h1>Api Ayarlari</h1></tr><hr>
        <tr valign="top">
            <td scope="row">Master Key [*]:</td>
            <td><input tye="text" name="bulutfon_masterKey" placeholder="Master Key" value="<?php echo esc_attr( get_option('bulutfon_masterKey') ); ?>"  size="50"/></td>
        </tr>
        <tr valign="top">
            <td scope="row">Sms Basligi:</td>
            <td><input tye="text" name="bulutfon_sms_baslik" placeholder="Sms Başlığı" value="<?php echo esc_attr( get_option('bulutfon_sms_baslik') ); ?>" size="50"/></td>
        </tr>
        <tr valign="top">
            <td scope="row">Santral Numaralari:</td>
            <td><input tye="text" name="bulutfon_sms_numaralar" placeholder="90XXXXXXXXXX,90XXXXXXXXXX" value="<?php echo esc_attr( get_option('bulutfon_sms_numaralar') ); ?>" size="50"/></td>
        </tr>
        <tr valign="top"><td colspan="2"><h1>Sms İle Bildirim Ayarları</h1><hr/></td></tr>
        <tr valign="top"><td colspan="2"><input type="checkbox" name="bulutfon_notify_onOrderComplete" value="1"  <?php $val = get_option('bulutfon_notify_onOrderComplete'); echo !empty($val) ? 'checked' : ''?> /> Sipariş tamamlandığında sms gönder</td></tr>
        <tr valign="top"><td colspan="2"><input type="checkbox" name="bulutfon_notify_onOrderStatusChange" value="1"  <?php $val = get_option('bulutfon_notify_onOrderStatusChange'); echo !empty($val) ? 'checked' : ''?>/> Sipariş durumu onaylandığında</td></tr>
        <tr valign="top"><td colspan="2"><input type="checkbox" name="bulutfon_notify_addOrderNote" value="1"  <?php $val = get_option('bulutfon_notify_addOrderNote'); echo !empty($val) ? 'checked' : ''?>/> Sipariş notu eklendiğinde</td></tr>

        <tr valign="top"><td colspan="2"><h1>Sms Gönderme Bilgileri</h1><hr/></td></tr>
        <tr valign="top>">
            <td scope="row">Tek Seferde İşlenecek Adet</td>
            <td><input type="text" name="bulutfon_sms_cronCount" value="<?php echo esc_attr( get_option('bulutfon_sms_cronCount') ); ?>" placeholder="Sms Gönderim Adet"  class="form-control" size="50" /></td>
        </tr>
        <tr valign="top"><td colspan="2">Bulutfon Opencart Eklentisi Smslerini önce kuyruğa alır ardından toplu olarak gönderim yapar.
                Aşağıdaki Cron dosyasını kullanarak sms gönderim kuyruğunu çalıştırabilirsiniz. Bu ayar her çalıştırma sırasında kuyruktan kaçar tane sms gönderileceğini belirtebileceğiniz ayardır.</td>
        </tr>
        <tr valign="top">
            <td scope="row">Link</td>
            <td>
                <code><?=plugins_url('Bulutfon-WooCommerce-Eklentisi/cron.php');?>?token=<?php echo esc_attr( get_option('bulutfon_secureKey') ); ?></code>
                <br/><a href="<?=plugins_url('Bulutfon-WooCommerce-Eklentisi/cron.php');?>?token=<?php echo esc_attr( get_option('bulutfon_secureKey') ); ?>" class="button" target="_blank">Çalıştır</a></td>
        </tr>
        <tr>
            <td scope="row">Güvenlik Kodu</td>
            <td><input type="text" name="bulutfon_secureKey" value="<?php echo esc_attr( get_option('bulutfon_secureKey') ); ?>" placeholder="Güvenlik Kodu"  class="form-control" size="50"/></td>
        </tr>
        <tr><td colspan="2">Yeniden oluşturmak için boş bırakınız</td></tr>
        <tr><td colspan="2">Yukarıda yazılı adresi belli zaman aralıkları ile çalıştırsanız smsleriniz belirlediğiniz şekilde gönderilecektir.</td></tr>
    </table>
    <?php submit_button(); ?>
</form>