<?php
require __DIR__.'/head.php';
$sms = projekod_get_sms_template();
projekod_add_to_sms_template();
?>

<div class="panel-group">
    <form method="POST" action="">
        <?php foreach($sms as $s) { ?>
        <div class="panel panel-default">
            <h4 class="panel-title">
                <a href="#"><?=$s->name; ?></a><hr/>
            </h4>
            <div class="panel-body">
                <textarea rows="3" cols="100" class="form-control" name="templates[<?=$s->name;?>]"><?=$s->content;?></textarea>
            </div>
        </div>
        <?php } ?>

        <h1></h1>
        <div class="panel panel-default">
            <h4 class="panel-title">
                <a href="#">Yeni Sms Template Olustur</a>
            </h4>

            <div class="panel-body">
                <label class="form-group">
                    <h4><b>Sms Ismini giriniz:</b></h4>
                    <input type="text" class="form-control" name="sms_name">
                </label>
                <h4><b>Sms içeriğini giriniz:</b></h4>
                <textarea rows="3" cols="100" class="form-control" name="sms_content"></textarea>
                <div class="well">
                    Gönderiler içerisinde değişken kullanabilirsiniz,<br/>
                    Bu gönderi ile berabar kullanabileceğiniz değişkenler <strong>{adi} {soyadi}</strong>
                </div>
            </div>
        </div>
        <br/>
        <button type="submit" class="btn btn-success" name="template_update"><i class="fa fa-check"></i> Güncelle</button>
    </form>
</div>