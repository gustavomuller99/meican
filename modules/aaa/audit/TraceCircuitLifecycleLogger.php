<?php

namespace meican\aaa\audit;

use Yii;
use Exception;
use meican\aaa\models\Group;
use meican\bpm\models\BpmFlow;
use meican\circuits\models\Connection;
use meican\circuits\models\ConnectionAuth;
use meican\circuits\models\ConnectionEvent;
use meican\aaa\models\User;

class TraceCircuitLifecycleLogger extends CircuitLifecycleLogger {

    public static function logConnectionStatusEvent(Connection $connection) {
        try {
            TraceCircuitLifecycleLogger::validateConnection($connection);

            $reservation = $connection->getReservation()->one();
            $user = $reservation->getRequesterUser()->one();
            Yii::trace("ConnectionStatusEvent [$connection->external_id]: [$user->name, $reservation->name, $connection->bandwidth, $connection->status, $connection->resources_status, $connection->dataplane_status, $connection->auth_status, $connection->start, $connection->finish]", "blockchain");
        } catch (Exception $_) { }
    }

    public static function logConnectionAuthEvent(ConnectionAuth $connectionAuth) {
        try {
            $connection = Connection::find()->where(['id' => $connectionAuth->connection_id])->one();
            TraceCircuitLifecycleLogger::validateConnection($connection);

            Yii::trace("ConnectionAuthEvent [$connection->external_id]: [$connectionAuth->domain, $connectionAuth->status]", "blockchain");
        } catch (Exception $_) { }
    }

    public static function logConnectionCircuitEvent(ConnectionEvent $connectionEvent) {
        try {
            $connection = Connection::find()->where(['id' => $connectionEvent->conn_id])->one();
            TraceCircuitLifecycleLogger::validateConnection($connection);

            Yii::trace("ConnectionEvent [$connection->external_id]: [$connectionEvent->created_at, $connectionEvent->type]", "blockchain");
        } catch (Exception $_) { }
    }

    public static function logWorkflowNodeEvent(BpmFlow $bpmFlow) {
        try {
            $connection = Connection::find()->where(['id' => $bpmFlow->connection_id])->one();
            TraceCircuitLifecycleLogger::validateConnection($connection);

            $logString = TraceCircuitLifecycleLogger::buildWorkflowNodeEventLogString($bpmFlow);
		    Yii::trace("WorkflowNodeEvent [$connection->external_id]: $logString", "blockchain");
        } catch (Exception $_) { }
        
    }

    public static function logWorkflowAuthorizationEvent(String $userId, String $connectionId, String $response) {
        try {
            $connection = Connection::find()->where(['id' => $connectionId])->one();
            TraceCircuitLifecycleLogger::validateConnection($connection);

            $user = User::find()->where(['id' => $userId])->one();
            $username = ($user != null) ? $user->name : "Unkown user";

            Yii::trace("WorkflowAuthorizationEvent [$connection->external_id]: [$username, $response]", "blockchain");
        } catch (Exception $_) { }
       
    }

    public static function logWorkflowResultEvent(BpmFlow $bpmFlow) {
        try {
            $connection = Connection::find()->where(['id' => $bpmFlow->connection_id])->one();
            TraceCircuitLifecycleLogger::validateConnection($connection);

		    Yii::trace("WorkflowResultEvent [$connection->external_id]: [$bpmFlow->domain, $bpmFlow->type]", "blockchain");
        } catch (Exception $_) { }
    }

    private static function validateConnection(Connection $connection) {
        if ($connection == null) {
            throw new Exception();
        }

        if (!isset($connection->external_id)) {
            throw new Exception();
        }
    }

    private static function buildWorkflowNodeEventLogString(BpmFlow $bpmFlow) {
        switch ($bpmFlow->type) {
            case 'Group':
            case 'Request_Group_Authorization':
                $group = Group::find()->where(['id' => $bpmFlow->value])->one();
                $groupname = ($group != null) ? $group->name : "Unkown group";
                return "[$bpmFlow->domain, $bpmFlow->type, $groupname, $bpmFlow->status]";
            case 'User':
            case 'Request_User_Authorization':
                $user = User::find()->where(['id' => $bpmFlow->value])->one();
                $username = ($user != null) ? $user->name : "Unkown user";
                return "[$bpmFlow->domain, $bpmFlow->type, $username, $bpmFlow->status]";
            default:
                return "[$bpmFlow->domain, $bpmFlow->type, $bpmFlow->operator, $bpmFlow->value, $bpmFlow->status]";
        }
    }
}