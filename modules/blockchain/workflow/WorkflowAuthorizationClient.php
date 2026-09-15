<?php

namespace meican\blockchain\workflow;

use meican\aaa\models\User;
use meican\blockchain\BlockchainClient;

class WorkflowAuthorizationClient {

    public static function requestAuthorization(string $externalId, array $requiredApprovers) {
        return BlockchainClient::getInstance()->requestAuthorization($externalId, $requiredApprovers);
    }

    public static function submitAuthorization(string $externalId, bool $approve, string $signerPrivateKey) {
        return BlockchainClient::getInstance()->submitAuthorization($externalId, $approve, $signerPrivateKey);
    }

    public static function getWorkflowAuthorizationState(string $externalId) {
        $result = BlockchainClient::getInstance()->getWorkflowAuthorizationState($externalId);

        foreach ($result['requiredApprovers'] as $i => $address) {
            $user = User::find()->where(['blockchain_address' => $address])->one();
            if ($user) {
                $result['requiredApprovers'][$i] = $address . ' -> ' . $user->name;
            }
        }

        return $result;
    }
}