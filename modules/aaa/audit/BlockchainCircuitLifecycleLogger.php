<?php

namespace meican\aaa\audit;

use Yii;
use Exception;
use meican\bpm\models\BpmFlow;
use meican\circuits\models\Connection;
use meican\circuits\models\ConnectionAuth;
use meican\circuits\models\ConnectionEvent;

class BlockchainCircuitLifecycleLogger extends CircuitLifecycleLogger {

    public static function logConnectionStatusEvent(Connection $connection) {
        
    }

    public static function logConnectionAuthEvent(ConnectionAuth $connectionAuth) {
        
    }

    public static function logConnectionCircuitEvent(ConnectionEvent $connectionEvent) {
        
    }

    public static function logWorkflowNodeEvent(BpmFlow $bpmFlow) {
        
        
    }

    public static function logWorkflowAuthorizationEvent(String $userId, String $connectionId, String $response) {
       
    }

    public static function logWorkflowResultEvent(BpmFlow $bpmFlow) {
        
    }
}