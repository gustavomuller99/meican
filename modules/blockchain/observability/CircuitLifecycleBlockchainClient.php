<?php

namespace meican\blockchain\observability;

use meican\blockchain\BlockchainClient;

class CircuitLifecycleBlockchainClient extends CircuitLifecycleClient {

    public function setConnectionStatus(
        string $externalId, string $userName, string $reservationName, string $bandwidth,
        string $status, string $resourcesStatus, string $dataplaneStatus,
        string $authStatus, string $start, string $finish
    ) {
        return BlockchainClient::getInstance()->setConnectionStatus(
            $externalId, $userName, $reservationName, $bandwidth,
            $status, $resourcesStatus, $dataplaneStatus,
            $authStatus, $start, $finish
        );
    }

    public function setConnectionAuth(string $externalId, string $domain, string $status) {
        return BlockchainClient::getInstance()->setConnectionAuth($externalId, $domain, $status);
    }

    public function setConnectionCircuit(string $externalId, string $eventType, string $status) {
        return BlockchainClient::getInstance()->setConnectionCircuit($externalId, $eventType, $status);
    }

    public function getCircuitState(string $externalId) {
        return BlockchainClient::getInstance()->getCircuitState($externalId);
    }
}
