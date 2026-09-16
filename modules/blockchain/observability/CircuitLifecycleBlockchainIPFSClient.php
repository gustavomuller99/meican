<?php

namespace meican\blockchain\observability;

use CURLFile;
use meican\blockchain\BlockchainClient;
use Yii;

class CircuitLifecycleBlockchainIPFSClient extends CircuitLifecycleClient {

    private $KEYS_DIR = '/etc/meican/keys/';

    private $ipfsUrl;

    public function __construct() {
        $cfg = Yii::$app->params['blockchain'];
        $this->ipfsUrl = $cfg['ipfsUrl'];
    }

    public function setConnectionStatus(
        string $externalId, string $userName, string $reservationName, string $bandwidth,
        string $status, string $resourcesStatus, string $dataplaneStatus,
        string $authStatus, string $start, string $finish
    ) {
        $ipfs_filename = $externalId . "_connection_status.json";
        $ipfs_file = json_encode([
            "externalId" => $externalId,
            "userName" => $userName,
            "reservationName" => $reservationName,
            "bandwidth" => $bandwidth,
            "status" => $status,
            "resourcesStatus" => $resourcesStatus,
            "dataplaneStatus" => $dataplaneStatus,
            "authStatus" => $authStatus,
            "start" => $start,
            "finish" => $finish
        ]);

        $cid = $this->addToIpfs($ipfs_file, $ipfs_filename);

        return BlockchainClient::getInstance()->setConnectionStatusIPFS($externalId, $cid);
    }

    public function setConnectionAuth(string $externalId, string $domain, string $status) {
        $ipfs_filename = $externalId . "_connection_auth.json";
        $ipfs_file = json_encode([
            "externalId" => $externalId,
            "domain" => $domain,
            "status" => $status
        ]);
        
        $cid = $this->addToIpfs($ipfs_file, $ipfs_filename);

        return BlockchainClient::getInstance()->setConnectionAuthIPFS($externalId, $cid);
    }

    public function setConnectionCircuit(string $externalId, string $eventType, string $status) {
        $ipfs_filename = $externalId . "_connection_circuit.json";
        $ipfs_file = json_encode([
            "externalId" => $externalId,
            "eventType" => $eventType,
            "status" => $status
        ]);
        
        $cid = $this->addToIpfs($ipfs_file, $ipfs_filename);

        return BlockchainClient::getInstance()->setConnectionCircuitIPFS($externalId, $cid);
    }

    public function getCircuitState(string $externalId) {
        $response = BlockchainClient::getInstance()->getCircuitStateIPFS($externalId);
        $statusCid = $response["statusCid"];
        $authCid = $response["authCid"];
        $circuitCid = $response["circuitCid"];

        return [
            'connectionStatus' => $statusCid ? json_decode($this->getFromIpfs($statusCid), true) : [],
            'connectionAuth' => $authCid ? json_decode($this->getFromIpfs($authCid), true) : [],
            'connectionCircuit' => $circuitCid ? json_decode($this->getFromIpfs($circuitCid), true) : [],
        ];
    }

    private function addToIpfs(string $content, string $filename) {
        $tempFile = tempnam(sys_get_temp_dir(), 'ipfs_');
        file_put_contents($tempFile, $this->encrypt($content));

        $ch = curl_init($this->ipfsUrl . '/api/v0/add');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'file' => new CURLFile($tempFile, 'application/octet-stream', $filename)
            ],
            CURLOPT_HTTPHEADER     => ['Content-Type: multipart/form-data'],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($curlErr || $httpCode != 200) {
            throw new \RuntimeException("IPFS add error: $curlErr");
        }

        $jsonResponse = json_decode($response, true);
        return $jsonResponse["Hash"];
    }

    private function getFromIpfs(string $cid) {
        $ch = curl_init($this->ipfsUrl . '/api/v0/cat?arg=' . $cid);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErr || $httpCode !== 200) {
            throw new \RuntimeException("IPFS fetch error (CID $cid): $curlErr");
        }

        return $this->decrypt($response);
    }

    private function encrypt(string $plaintext): string {
        $aesKey = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(16);

        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);

        $pubKey = openssl_pkey_get_public('file://' . $this->KEYS_DIR . 'meican_rsa_public.pem');
        if ($pubKey === false) {
            throw new \RuntimeException("Failed to load RSA public key");
        }
        openssl_public_encrypt($aesKey, $encryptedKey, $pubKey, OPENSSL_PKCS1_OAEP_PADDING);

        return json_encode([
            'iv' => base64_encode($iv),
            'key' => base64_encode($encryptedKey),
            'data' => base64_encode($ciphertext),
        ]);
    }

    private function decrypt(string $envelope): string {
        $parts = json_decode($envelope, true);

        $privKey = openssl_pkey_get_private('file://' . $this->KEYS_DIR . 'meican_rsa_private.pem');
        if ($privKey === false) {
            throw new \RuntimeException("Failed to load RSA private key");
        }
        $encryptedKey = base64_decode($parts['key']);
        openssl_private_decrypt($encryptedKey, $aesKey, $privKey, OPENSSL_PKCS1_OAEP_PADDING);

        $iv = base64_decode($parts['iv']);
        $ciphertext = base64_decode($parts['data']);

        return openssl_decrypt($ciphertext, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
    }
}
