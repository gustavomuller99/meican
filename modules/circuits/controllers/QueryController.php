<?php
/**
 * @copyright Copyright (c) 2012-2016 RNP
 * @license http://github.com/ufrgs-hyman/meican#license
 */

namespace meican\circuits\controllers;

use Yii;
use yii\base\DynamicModel;
use meican\aaa\RbacController;
use meican\blockchain\CircuitLifecycleClient;
use meican\blockchain\WorkflowAuthorizationClient;

class QueryController extends RbacController {

    public function actionIndex() {
        $model = new DynamicModel(['externalId']);
        $model->addRule(['externalId'], 'string');

        $circuitState = null;
        $workflowAuth = null;

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->externalId) {
            $circuitState = CircuitLifecycleClient::getCircuitState($model->externalId);
            $workflowAuth = WorkflowAuthorizationClient::getWorkflowAuthorizationState($model->externalId);
        }

        return $this->render('index', [
            'model' => $model,
            'circuitState' => $circuitState,
            'workflowAuth' => $workflowAuth,
        ]);
    }
}