<?php
require __DIR__.'/head.php';
$cdrs = projekod_get_bulutfon_cdr();
?>

<div style="margin:20px;">
    <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th>Armma Tipi</th>
            <th>Yön</th>
            <th>Arayan</th>
            <th>Aranan</th>
            <th>Arama Zamanı</th>
            <th>Cevaplama Zamanı</th>
        </tr>
        </thead>
        <tbody>
        <?php $a = 1; foreach($cdrs as $cdr): $a++; ?>
            <tr>
                <td><?=$cdr['bf_calltype_str'];?></td>
                <td><?=$cdr['direction_str'];?></td>
                <td><?=$cdr['caller_str'];?>
                    <?php
                        if($cdr['old_orders']){
                            echo ' (<a href="#" data-rel="'.$a.'" class="page-title-action shbtn">'.count($cdr['old_orders']).'</a>)';
                        }
                    ?>
                </td>
                <td><?=$cdr['callee_str'];?></td>
                <td><?=$cdr['call_time'];?></td>
                <td><?=$cdr['hangup_time'];?></td>
            </tr>
            <?php
            if($cdr['old_orders']): ?>
                <tr class="tr<?=$a;?> trorders" style="display: none">
                    <td colspan="6" style="border: 1px solid #000; padding: 10px;">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                            <tr>
                                <th>Adı</th>
                                <th>Soyadı</th>
                                <th>Mail</th>
                                <th>Alışveriş Zamanı</th>
                                <th>Başlık</th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cdr['old_orders'] as $order) :?>
                                <tr>
                                    <td><?=$order->first_name; ?></td>
                                    <td><?=$order->last_name; ?></td>
                                    <td><?=$order->mail; ?></td>
                                    <td><?=$order->date; ?></td>
                                    <td><?=$order->title; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        $(document).on('click','.shbtn', function(){
            $('.trorders').hide();
            var id = $(this).attr('data-rel');
            $('.tr'+id).show();
        });
    });
</script>