<?php

namespace meican\aaa\audit;

use Exception;
use meican\bpm\models\BpmFlow;
use meican\circuits\models\Connection;
use meican\circuits\models\ConnectionAuth;
use meican\circuits\models\ConnectionEvent;
use meican\aaa\audit\TraceCircuitLifecycleLogger;
use meican\aaa\audit\BlockchainCircuitLifecycleLogger;
use Yii;

abstract class CircuitLifecycleLogger {

    public static function getInstance() {
        $driver = Yii::$app->params['circuit_lifecycle_logger'] ?? 'trace';
        if ($driver === 'blockchain') {
            return new BlockchainCircuitLifecycleLogger();
        }
        return new TraceCircuitLifecycleLogger();
    }

    abstract static function logConnectionStatusEvent(Connection $connection);
    abstract static function logConnectionAuthEvent(ConnectionAuth $connectionAuth);
    abstract static function logConnectionCircuitEvent(ConnectionEvent $connectionEvent);
    abstract static function logWorkflowNodeEvent(BpmFlow $bpmFlow);
    abstract static function logWorkflowAuthorizationEvent(String $userId, String $connectionId, String $response);
    abstract static function logWorkflowResultEvent(BpmFlow $bpmFlow);

    protected static function validateConnection(Connection $connection) {
        if ($connection == null) {
            throw new Exception();
        }

        if (!isset($connection->external_id)) {
            throw new Exception();
        }
    }
}