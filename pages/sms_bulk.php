<?php
require __DIR__.'/head.php';
$customers = projekod_get_all_customers();
$sms = projekod_get_sms_template_from_status(1);
projekod_add_to_queue();
?>

<div style="margin:20px;">
    <form method="post" action="">
        <h4>Kullanıcı seçimi yapınız</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th class="manage-column column-cb check-column"><input type="checkbox"></th>
                <th>Ad Soyad</th>
                <th>Mail Adresi</th>
                <th>GSM</th>
                <th>İlk Sipariş Tarihi</th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($customers)): ?>
                <?php foreach($customers as $customer):?>
                <tr class="manage-column column-cb check-column">
                    <td><input type="checkbox" name="ids[]" value="<?=$customer->id?>"></td>
                    <td><?=$customer->first_name?> <?=$customer->last_name?></td>
                    <td><?=$customer->mail?></td>
                    <td><?=$customer->telephone?></td>
                    <td><?=$customer->date?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <h4>SMS Template seçimini yapınız</h4>
        <label class="form-group">
            <select class="selectpicker" data-style="btn-inverse" name="sms_id">
                <?php foreach($sms as $key => $s): ?>
                    <option value="<?=$s->id;?>"><?=$s->name;?></option>
                <?php endforeach;?>
            </select>
        </label>
        <br><br>
        <button type="submit" class="btn btn-success" name="add_queue"><i class="fa fa-check"></i> Kuyruğa Ekle</button>
    </form>
</div>
