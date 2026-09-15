<?php

namespace meican\blockchain;

use Yii;

class BlockchainClient
{
    private $baseUrl;

    public static function getInstance(): self {
        return new self();
    }

    private function __construct() {
        $this->baseUrl = Yii::$app->params['blockchain']['sidecarUrl'];
    }

    // ── circuit observability (on-chain) ─────────────────────────────────────

    public function setConnectionStatus(
        string $externalId,
        string $userName, string $reservationName, string $bandwidth,
        string $status, string $resourcesStatus, string $dataplaneStatus,
        string $authStatus, string $start, string $finish
    ) {
        return $this->post('/circuit/status', compact(
            'externalId', 'userName', 'reservationName', 'bandwidth',
            'status', 'resourcesStatus', 'dataplaneStatus',
            'authStatus', 'start', 'finish'
        ));
    }

    public function setConnectionAuth(string $externalId, string $domain, string $status) {
        return $this->post('/circuit/auth', compact('externalId', 'domain', 'status'));
    }

    public function setConnectionCircuit(string $externalId, string $eventType, string $status) {
        return $this->post('/circuit/event', compact('externalId', 'eventType', 'status'));
    }

    public function getCircuitState(string $externalId) {
        return $this->get('/circuit/state/' . urlencode($externalId));
    }

    // ── circuit observability (IPFS CID pointers) ────────────────────────────

    public function setConnectionStatusIPFS(string $externalId, string $cid) {
        return $this->post('/circuit/status-ipfs', compact('externalId', 'cid'));
    }

    public function setConnectionAuthIPFS(string $externalId, string $cid) {
        return $this->post('/circuit/auth-ipfs', compact('externalId', 'cid'));
    }

    public function setConnectionCircuitIPFS(string $externalId, string $cid) {
        return $this->post('/circuit/event-ipfs', compact('externalId', 'cid'));
    }

    public function getCircuitStateIPFS(string $externalId) {
        return $this->get('/circuit/state-ipfs/' . urlencode($externalId));
    }

    // ── workflow authorization ────────────────────────────────────────────────

    public function requestAuthorization(string $externalId, array $requiredApprovers) {
        return $this->post('/workflow/request', compact('externalId', 'requiredApprovers'));
    }

    public function submitAuthorization(string $externalId, bool $approve, string $signerPrivateKey) {
        return $this->post('/workflow/submit', compact('externalId', 'approve', 'signerPrivateKey'));
    }

    public function getWorkflowAuthorizationState(string $externalId) {
        return $this->get('/workflow/state/' . urlencode($externalId));
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private function post(string $path, array $body) {
        return $this->request('POST', $path, $body);
    }

    private function get(string $path) {
        return $this->request('GET', $path);
    }

    private function request(string $method, string $path, array $body = null) {
        $ch = curl_init($this->baseUrl . $path);
        $headers = ['Accept: application/json'];

        if ($method === 'POST') {
            $payload = json_encode($body);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \RuntimeException("Blockchain sidecar curl error: $curlErr");
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $decoded['error'] ?? $response;
            throw new \RuntimeException("Blockchain sidecar error ($httpCode): $msg");
        }

        return $decoded ?? [];
    }
}
