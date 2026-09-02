<?php

namespace meican\blockchain;

use meican\aaa\models\User;

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
        $wordSize = 32;

        // Word 0: offset to address[] (dynamic)
        // Word 1: approver address (static)
        // Word 2: status enum as uint8 (static)
        $approverRaw = substr($bytes, $wordSize, $wordSize);
        $approver    = '0x' . bin2hex(substr($approverRaw, 12, 20));

        $statusRaw = substr($bytes, 2 * $wordSize, $wordSize);
        $statusInt = unpack('N', substr($statusRaw, 28, 4))[1];
        $statusMap = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected'];
        $status    = $statusMap[$statusInt] ?? 'Unknown';

        // Word 0 holds the byte offset to the address[] data (relative to start of return data)
        $arrayOffsetRaw = substr($bytes, 0, $wordSize);
        $arrayOffset    = unpack('N', substr($arrayOffsetRaw, 28, 4))[1];

        // At arrayOffset: one word for array length, then one 32-byte word per address
        $arrayLen     = unpack('N', substr($bytes, $arrayOffset + 28, 4))[1];
        $approvers    = [];
        $elementStart = $arrayOffset + $wordSize;
        for ($i = 0; $i < $arrayLen; $i++) {
            $word        = substr($bytes, $elementStart + $i * $wordSize, $wordSize);
            $approvers[] = '0x' . bin2hex(substr($word, 12, 20));
        }

        for ($i = 0; $i < count($approvers); $i++) {
            $user = User::find()->where(["blockchain_address" => $approvers[$i]])->one();
            if (isset($user)) {
                $approvers[$i] = $approvers[$i] . " -> " . $user->name;
            }
        }

        return [
            'requiredApprovers' => $approvers,
            'approver'          => $approver,
            'status'            => $status,
        ];
    }
}