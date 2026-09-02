<?php

$this->params['header'] = [Yii::t('circuits', 'Query'), ['Home', Yii::t('circuits', 'Circuits'), 'Query']];

?>

<?= $this->render('form', [
    'model' => $model,
    'circuitState' => $circuitState,
    'workflowAuth' => $workflowAuth,
]); ?>