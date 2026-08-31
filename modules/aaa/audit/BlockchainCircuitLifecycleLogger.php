<?php

namespace meican\aaa\audit;

use Yii;
use Exception;
use meican\blockchain\CircuitLifecycleClient;
use meican\bpm\models\BpmFlow;
use meican\circuits\models\Connection;
use meican\circuits\models\ConnectionAuth;
use meican\circuits\models\ConnectionEvent;
use meican\blockchain\EthereumClient;

class BlockchainCircuitLifecycleLogger extends CircuitLifecycleLogger {

    public static function logConnectionStatusEvent(Connection $connection) {
        try {
            CircuitLifecycleLogger::validateConnection($connection);

            $reservation = $connection->getReservation()->one();
            $user = $reservation->getRequesterUser()->one();

            CircuitLifecycleClient::setConnectionStatus(
                $connection->external_id,
                $user->name,
                $reservation->name,
                (string) $connection->bandwidth,
                $connection->status,
                $connection->resources_status,
                $connection->dataplane_status,
                $connection->auth_status,
                (string) $connection->start,
                (string) $connection->finish
            );
        } catch (Exception $_) { }
    }

    public static function logConnectionAuthEvent(ConnectionAuth $connectionAuth) {
        try {
            $connection = $connectionAuth->connection;

            CircuitLifecycleLogger::validateConnection($connection);

            CircuitLifecycleClient::setConnectionAuth(
                $connection->external_id,
                $connectionAuth->domain,
                $connectionAuth->status
            );
        } catch (Exception $_) { }
    }

    public static function logConnectionCircuitEvent(ConnectionEvent $connectionEvent) {
        try {
            $connection = $connectionEvent->getConnection()->one();

            CircuitLifecycleLogger::validateConnection($connection);

            CircuitLifecycleClient::setConnectionCircuit(
                $connection->external_id,
                $connectionEvent->type,
                $connectionEvent->status
            );
        } catch (Exception $_) { }
    }

    public static function logWorkflowNodeEvent(BpmFlow $bpmFlow) {
        
    }

    public static function logWorkflowAuthorizationEvent(String $userId, String $connectionId, String $response) {

    }

    public static function logWorkflowResultEvent(BpmFlow $bpmFlow) {

    }
}
