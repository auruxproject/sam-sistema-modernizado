<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;

$this->title = $name;
?>
<div class="site-error">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-danger">
        <?= nl2br(Html::encode($message)) ?>
    </div>

    <p>
        El error anterior ocurrió mientras el servidor web procesaba su solicitud.
    </p>
    <p>
        Por favor contacte con nosotros si cree que se trata de un error del servidor. Gracias.
    </p>

    <?php if (YII_ENV_DEV): ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Información de depuración</h3>
        </div>
        <div class="panel-body">
            <p><strong>Archivo:</strong> <?= Html::encode($exception->getFile()) ?></p>
            <p><strong>Línea:</strong> <?= $exception->getLine() ?></p>
            <p><strong>Trace:</strong></p>
            <pre><?= Html::encode($exception->getTraceAsString()) ?></pre>
        </div>
    </div>
    <?php endif; ?>

</div>