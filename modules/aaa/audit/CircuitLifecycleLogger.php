<?php

namespace meican\aaa\audit;

use meican\bpm\models\BpmFlow;
use meican\circuits\models\Connection;
use meican\circuits\models\ConnectionAuth;
use meican\circuits\models\ConnectionEvent;
use meican\aaa\audit\TraceCircuitLifecycleLogger;

abstract class CircuitLifecycleLogger {

    public static function getInstance() {
        return new TraceCircuitLifecycleLogger();
    }

    abstract static function logConnectionStatusEvent(Connection $connection);
    abstract static function logConnectionAuthEvent(ConnectionAuth $connectionAuth);
    abstract static function logConnectionCircuitEvent(ConnectionEvent $connectionEvent);
    abstract static function logWorkflowNodeEvent(BpmFlow $bpmFlow);
    abstract static function logWorkflowAuthorizationEvent(String $userId, String $connectionId, String $response);
    abstract static function logWorkflowResultEvent(BpmFlow $bpmFlow);
}