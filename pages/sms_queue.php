<?php require __DIR__.'/head.php'; ?>
<?php $queue = projekod_get_sms_queue(); ?>
<div style="margin:20px;">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Numara</th>
                <th>Mesaj İçeriği</th>
                <th>Arguments</th>
                <th>Template ID</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($queue as $q) { ?>
            <?php if($q->status == 1) { ?>
                <tr class="info">
            <?php } else { ?>
                <tr class="success">
            <?php } ?>
            <th><?php echo $q->date_added; ?></th>
            <th><?php echo $q->phone_number;?></th>
            <th><?php echo $q->sms_content;?></th>
            <th><?php echo $q->arguments;?></th>
            <th title="<?php echo projekod_get_sms_template($q->template_id)[0]->content; ?>">
                <a href="#template_edit" data-toggle="tab"><?php echo $q->template_id?></a>
            </th>
            <th>
                <?php if($q->status == 1): ?>
                    <label class="label label-primary">Bekliyor</label>
                <?php elseif($q->status == 2): ?>
                    <label class="label label-primary">Gönderildi</label>
                <?php endif; ?>
            </th>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>