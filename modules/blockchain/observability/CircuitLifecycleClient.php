<?php

namespace meican\blockchain\observability;

use Yii;

abstract class CircuitLifecycleClient {

    public static function getInstance(): self {
        $impl = Yii::$app->params['circuit_lifecycle_client'] ?? 'blockchain';
        if ($impl === 'ipfs') {
            return new CircuitLifecycleBlockchainIPFSClient();
        }
        return new CircuitLifecycleBlockchainClient();
    }

    abstract public function setConnectionStatus(
        string $externalId, string $userName, string $reservationName, string $bandwidth,
        string $status, string $resourcesStatus, string $dataplaneStatus,
        string $authStatus, string $start, string $finish
    );

    abstract public function setConnectionAuth(string $externalId, string $domain, string $status);

    abstract public function setConnectionCircuit(string $externalId, string $eventType, string $status);

    abstract public function getCircuitState(string $externalId);
}
