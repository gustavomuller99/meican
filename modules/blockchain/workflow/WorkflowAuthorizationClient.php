<?php

namespace meican\blockchain\workflow;

use meican\aaa\models\User;
use meican\blockchain\EthereumClient;

class WorkflowAuthorizationClient {

    public static function requestAuthorization(string $externalId, array $requiredApprovers) {
        return EthereumClient::getInstance()->sendTransactionMeican(
            'requestAuthorization(string,address[])',
            [$externalId, $requiredApprovers]
        );
    }

    public static function submitAuthorization(string $externalId, bool $approve, string $address) {
        return EthereumClient::getInstance()->sendTransaction( 
            'submitAuthorization(string,bool)',
            [$externalId, $approve],
            $address
        );
    }

    public static function getWorkflowAuthorizationState(string $externalId) {
        $client = EthereumClient::getInstance();

        $result = $client->ethCall('getWorkflowAuthorization(string)', [$externalId]);

        if (!$result || $result === '0x') {
            return [];
        }

        $bytes    = hex2bin(substr($result, 2));
        $decoded  = $client->decodeGetWorkflowAuthorization($bytes);

        foreach ($decoded['requiredApprovers'] as $i => $address) {
            $user = User::find()->where(['blockchain_address' => $address])->one();
            if ($user) {
                $decoded['requiredApprovers'][$i] = $address . ' -> ' . $user->name;
            }
        }

        return $decoded;
    }
}