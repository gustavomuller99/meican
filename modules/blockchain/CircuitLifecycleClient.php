<?php

namespace meican\blockchain;

use meican\blockchain\EthereumClient;

class CircuitLifecycleClient {

    public static function setConnectionStatus(
        string $externalId, string $userName, string $reservationName, string $bandwidth,
        string $status, string $resourcesStatus, string $dataplaneStatus,
        string $authStatus, string $start, string $finish
    ) {
        $tupleArgs = [$userName, $reservationName, $bandwidth, $status, $resourcesStatus, $dataplaneStatus, $authStatus, $start, $finish];
        return EthereumClient::getInstance()->sendTransactionTuple(
            'setConnectionStatus(string,(string,string,string,string,string,string,string,string,string))',
            $externalId,
            $tupleArgs
        );
    }

    public static function setConnectionAuth(string $externalId, string $domain, string $status) {
        return EthereumClient::getInstance()->sendTransaction(
            'setConnectionAuth(string,string,string)',
            [$externalId, $domain, $status]
        );
    }

    public static function setConnectionCircuit(string $externalId, string $eventType, string $status) {
        return EthereumClient::getInstance()->sendTransaction(
            'setConnectionCircuit(string,string,string)',
            [$externalId, $eventType, $status]
        );
    }

    public static function getCircuitState(string $externalId): array {
        $client = EthereumClient::getInstance();

        $result = $client->ethCall('getCircuitState(string)', [$externalId]);

        if (!$result || $result === '0x') {
            return [];
        }

        $bytes = hex2bin(ltrim($result, '0x'));
        $strings = $client->decodeStringTuple($bytes);

        return [
            'connectionStatus' => [
                'userName'        => $strings[0] ?? '',
                'reservationName' => $strings[1] ?? '',
                'bandwidth'       => $strings[2] ?? '',
                'status'          => $strings[3] ?? '',
                'resourcesStatus' => $strings[4] ?? '',
                'dataplaneStatus' => $strings[5] ?? '',
                'authStatus'      => $strings[6] ?? '',
                'start'           => $strings[7] ?? '',
                'finish'          => $strings[8] ?? '',
            ],
            'connectionAuth' => [
                'domain' => $strings[9]  ?? '',
                'status' => $strings[10] ?? '',
            ],
            'connectionCircuit' => [
                'type'   => $strings[11] ?? '',
                'status' => $strings[12] ?? '',
            ],
        ];
    }

}