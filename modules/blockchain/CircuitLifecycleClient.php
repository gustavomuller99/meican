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
        return EthereumClient::getInstance()->sendTransactionMeican(
            'setConnectionAuth(string,string,string)',
            [$externalId, $domain, $status]
        );
    }

    public static function setConnectionCircuit(string $externalId, string $eventType, string $status) {
        return EthereumClient::getInstance()->sendTransactionMeican(
            'setConnectionCircuit(string,string,string)',
            [$externalId, $eventType, $status]
        );
    }

    public static function getCircuitState(string $externalId) {
        $client = EthereumClient::getInstance();

        $result = $client->ethCall('getCircuitState(string)', [$externalId]);

        if (!$result || $result === '0x') {
            return [];
        }

        $bytes = hex2bin(substr($result, 2));
        $tuples = $client->decodeGetCircuitState($bytes, [9, 2, 2]);
        $cs = $tuples[0];
        $ca = $tuples[1];
        $cc = $tuples[2];

        return [
            'connectionStatus' => [
                'userName'        => $cs[0] ?? '',
                'reservationName' => $cs[1] ?? '',
                'bandwidth'       => $cs[2] ?? '',
                'status'          => $cs[3] ?? '',
                'resourcesStatus' => $cs[4] ?? '',
                'dataplaneStatus' => $cs[5] ?? '',
                'authStatus'      => $cs[6] ?? '',
                'start'           => $cs[7] ?? '',
                'finish'          => $cs[8] ?? '',
            ],
            'connectionAuth' => [
                'domain' => $ca[0] ?? '',
                'status' => $ca[1] ?? '',
            ],
            'connectionCircuit' => [
                'type'   => $cc[0] ?? '',
                'status' => $cc[1] ?? '',
            ],
        ];
    }

}