<?php

use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

$form = ActiveForm::begin([
    'id'     => 'query-form',
    'method' => 'post',
    'layout' => 'horizontal',
]);

?>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Yii::t('circuits', 'Query'); ?></h3>
    </div>
    <div class="box-body">
        <?= $form->field($model, 'externalId')->textInput(['placeholder' => 'External ID']); ?>
    </div>
    <div class="box-footer">
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-6">
                <button type="submit" class="btn btn-primary"><?= Yii::t('circuits', 'Search'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>

<?php if ($circuitState !== null): ?>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Yii::t('circuits', 'Circuit State'); ?></h3>
    </div>
    <div class="box-body">
        <?php if (empty($circuitState)): ?>
            <p class="text-muted"><?= Yii::t('circuits', 'No circuit state found for this External ID.'); ?></p>
        <?php else: ?>

            <h4><?= Yii::t('circuits', 'Connection Status'); ?></h4>
            <table class="table table-bordered table-condensed">
                <?php foreach ($circuitState['connectionStatus'] as $key => $value): ?>
                <tr>
                    <th class="col-sm-3"><?= Html::encode($key); ?></th>
                    <td><?= Html::encode($value); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h4><?= Yii::t('circuits', 'Connection Auth'); ?></h4>
            <table class="table table-bordered table-condensed">
                <?php foreach ($circuitState['connectionAuth'] as $key => $value): ?>
                <tr>
                    <th class="col-sm-3"><?= Html::encode($key); ?></th>
                    <td><?= Html::encode($value); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h4><?= Yii::t('circuits', 'Connection Circuit'); ?></h4>
            <table class="table table-bordered table-condensed">
                <?php foreach ($circuitState['connectionCircuit'] as $key => $value): ?>
                <tr>
                    <th class="col-sm-3"><?= Html::encode($key); ?></th>
                    <td><?= Html::encode($value); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

        <?php endif; ?>
    </div>
</div>

<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Yii::t('circuits', 'Workflow Authorization'); ?></h3>
    </div>
    <div class="box-body">
        <?php if (empty($workflowAuth)): ?>
            <p class="text-muted"><?= Yii::t('circuits', 'No workflow authorization found for this External ID.'); ?></p>
        <?php else: ?>

            <table class="table table-bordered table-condensed">
                <tr>
                    <th class="col-sm-3"><?= Yii::t('circuits', 'Status'); ?></th>
                    <td><?= Html::encode($workflowAuth['status']); ?></td>
                </tr>
                <tr>
                    <th class="col-sm-3"><?= Yii::t('circuits', 'Approver'); ?></th>
                    <td><?= Html::encode($workflowAuth['approver']); ?></td>
                </tr>
            </table>

            <h4><?= Yii::t('circuits', 'Required Approvers'); ?></h4>
            <?php if (empty($workflowAuth['requiredApprovers'])): ?>
                <p class="text-muted"><?= Yii::t('circuits', 'None'); ?></p>
            <?php else: ?>
                <ul>
                    <?php foreach ($workflowAuth['requiredApprovers'] as $addr): ?>
                        <li><?= Html::encode($addr); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
