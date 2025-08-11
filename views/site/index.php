<?php

/* @var $this yii\web\View */

$this->title = 'Sistema de Administración Municipal';
?>
<div class="site-index">

    <div class="jumbotron">
        <h1>¡Bienvenido al SAM!</h1>

        <p class="lead">Sistema de Administración Municipal modernizado</p>

        <p>Versión actualizada con las mejores prácticas de Yii2 y PHP moderno, optimizada para EasyPanel.</p>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4">
                <h2>Módulo SAM</h2>

                <p>Gestión principal del sistema administrativo municipal. Incluye funcionalidades básicas de administración y configuración del sistema.</p>

                <p><a class="btn btn-default" href="<?= \yii\helpers\Url::to(['/sam']) ?>">Acceder &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h2>Módulo SAMSEG</h2>

                <p>Módulo de seguridad y gestión de usuarios. Controla el acceso, permisos y roles dentro del sistema municipal.</p>

                <p><a class="btn btn-default" href="<?= \yii\helpers\Url::to(['/samseg']) ?>">Acceder &raquo;</a></p>
            </div>
            <div class="col-lg-4">
                <h2>Módulo SAMTRIB</h2>

                <p>Sistema tributario municipal. Gestión de impuestos, tasas y contribuciones municipales de forma integral.</p>

                <p><a class="btn btn-default" href="<?= \yii\helpers\Url::to(['/samtrib']) ?>">Acceder &raquo;</a></p>
            </div>
        </div>

        <div class="row" style="margin-top: 30px;">
            <div class="col-lg-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Información del Sistema</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Usuario:</strong> <?= \yii\helpers\Html::encode(Yii::$app->user->identity->username) ?><br>
                                <strong>Último acceso:</strong> <?= date('d/m/Y H:i:s') ?><br>
                                <strong>Entorno:</strong> <?= YII_ENV ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Versión PHP:</strong> <?= PHP_VERSION ?><br>
                                <strong>Versión Yii:</strong> <?= Yii::getVersion() ?><br>
                                <strong>Estado del sistema:</strong> <span class="label label-success">Operativo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>