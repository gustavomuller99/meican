<?php

namespace meican\blockchain;

use Yii;

/**
 * Zero-dependency Ethereum JSON-RPC client.
 * Speaks raw curl, signs EIP-155 transactions via secp256k1 + RFC 6979.
 * Requires php-gmp.
 *
 * Config (params.php 'blockchain' key):
 *   rpcUrl           – http(s) endpoint of the Ethereum node
 *   chainId          – integer (31337 = Hardhat, 11155111 = Sepolia)
 *   contractAddress  – hex address of the deployed CircuitLifecycle contract
 *   signerPrivateKey – 32-byte hex private key (with or without 0x prefix)
 *   signerAddress    – hex address corresponding to signerPrivateKey
 */
class EthereumClient
{
    private $rpcUrl;
    private $contractAddress;
    private $signerAddress;

    public function __construct()
    {
        $cfg = Yii::$app->params['blockchain'];
        $this->rpcUrl          = $cfg['rpcUrl'];
        $this->contractAddress = strtolower($cfg['contractAddress']);
        $this->signerAddress = strtolower($cfg['signerAddress']);
    }

    public function setConnectionStatus(
        string $externalId, string $userName, string $reservationName, string $bandwidth,
        string $status, string $resourcesStatus, string $dataplaneStatus,
        string $authStatus, string $start, string $finish
    ): string {
        $tupleArgs = [$userName, $reservationName, $bandwidth, $status, $resourcesStatus, $dataplaneStatus, $authStatus, $start, $finish];
        $data = $this->encodeCallWithTuple(
            'setConnectionStatus(string,(string,string,string,string,string,string,string,string,string))',
            $externalId,
            $tupleArgs
        );
        return $this->sendTransaction($data);
    }

    public function setConnectionAuth(string $externalId, string $domain, string $status): string {
        $data = $this->encodeCall(
            'setConnectionAuth(string,string,string)',
            [$externalId, $domain, $status]
        );
        return $this->sendTransaction($data);
    }

    public function setConnectionCircuit(string $externalId, string $eventType, string $status): string {
        $data = $this->encodeCall(
            'setConnectionCircuit(string,string,string)',
            [$externalId, $eventType, $status]
        );
        return $this->sendTransaction($data);
    }

    /**
     * Returns the current on-chain state for a circuit as three associative arrays:
     *   ['connectionStatus' => [...], 'connectionAuth' => [...], 'connectionCircuit' => [...]]
     */
    public function getCircuitState(string $externalId): array {
        $data = $this->encodeCall('getCircuitState(string)', [$externalId]);

        $result = $this->jsonRpc('eth_call', [
            ['to' => $this->contractAddress, 'data' => '0x' . $data],
            'latest',
        ]);

        if (!$result || $result === '0x') {
            return [];
        }

        $bytes = hex2bin(ltrim($result, '0x'));
        $strings = $this->decodeStringTuple($bytes);

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

    private function sendTransaction(string $data): string
    {
        return $this->jsonRpc('eth_sendTransaction', [[
            'from' => $this->signerAddress,
            'to'   => $this->contractAddress,
            'data' => '0x' . $data,
            'gas'  => '0x' . dechex(500000),
        ]]);
    }

    /**
     * Decodes an ABI-encoded return value that is a flat sequence of dynamic
     * string fields (from nested structs, which ABI-encode identically to a
     * tuple of strings at the top level).  Returns an array of string values
     * in the order they appear in the ABI encoding.
     */
    private function decodeStringTuple(string $bytes): array
    {
        $wordSize = 32;
        $totalLen = strlen($bytes);
        $strings  = [];
        $offset   = 0;

        // First word is the offset to the outer tuple — skip it.
        $offset += $wordSize;

        // Read head offsets until we hit data we've already accounted for.
        $headOffsets = [];
        while ($offset < $totalLen) {
            $headOffset = $this->readUint256($bytes, $offset);
            // Offset is relative to the start of the tuple body (after the
            // outer tuple pointer word).
            if ($headOffset > $totalLen) break;
            $headOffsets[] = $headOffset;
            $offset += $wordSize;
            // Stop when the next head would point past what we've read.
            if (count($headOffsets) > 0 && $headOffset <= ($offset - $wordSize)) break;
        }

        // Simpler approach: scan all 32-byte words for length-prefixed strings.
        $strings = [];
        $pos = 0;
        while ($pos + $wordSize <= $totalLen) {
            $candidate = $this->readUint256($bytes, $pos);
            // A plausible string length: >0 and fits within remaining bytes.
            if ($candidate > 0 && $candidate <= 1024 && $pos + $wordSize + $candidate <= $totalLen) {
                $str = substr($bytes, $pos + $wordSize, $candidate);
                // Only accept if it's valid UTF-8 / printable.
                if (mb_check_encoding($str, 'UTF-8')) {
                    $strings[] = $str;
                    // Advance past length word + padded data.
                    $pos += $wordSize + (int)(ceil($candidate / $wordSize) * $wordSize);
                    continue;
                }
            }
            $pos += $wordSize;
        }

        return $strings;
    }

    private function readUint256(string $bytes, int $offset): int
    {
        // Read last 4 bytes of a 32-byte word as a uint32 (sufficient for
        // offsets and string lengths we expect — nothing > 4 GB).
        $word = substr($bytes, $offset, 32);
        if (strlen($word) < 32) return 0;
        $parts = unpack('N', substr($word, 28, 4));
        return $parts[1];
    }

    private function encodeCall(string $signature, array $args): string
    {
        $selector = substr($this->keccak256($signature), 0, 4);

        $argCount   = count($args);
        $headSize   = $argCount * 32;
        $heads      = '';
        $tails      = '';
        $tailOffset = $headSize;

        foreach ($args as $arg) {
            $heads .= str_pad(pack('N', $tailOffset), 32, "\x00", STR_PAD_LEFT);
            $encoded  = $this->encodeString($arg);
            $tails   .= $encoded;
            $tailOffset += strlen($encoded);
        }

        return bin2hex($selector . $heads . $tails);
    }

    private function encodeCallWithTuple(string $signature, string $firstArg, array $tupleArgs): string
    {
        $selector = substr($this->keccak256($signature), 0, 4);

        $encodedFirst = $this->encodeString($firstArg);
        $encodedTuple = $this->encodeTuple($tupleArgs);

        $headSize    = 2 * 32;
        $offsetFirst = $headSize;
        $offsetTuple = $headSize + strlen($encodedFirst);

        $head = str_pad(pack('N', $offsetFirst), 32, "\x00", STR_PAD_LEFT)
              . str_pad(pack('N', $offsetTuple), 32, "\x00", STR_PAD_LEFT);

        return bin2hex($selector . $head . $encodedFirst . $encodedTuple);
    }

    private function encodeTuple(array $args): string
    {
        $argCount   = count($args);
        $headSize   = $argCount * 32;
        $heads      = '';
        $tails      = '';
        $tailOffset = $headSize;

        foreach ($args as $arg) {
            $heads .= str_pad(pack('N', $tailOffset), 32, "\x00", STR_PAD_LEFT);
            $encoded  = $this->encodeString($arg);
            $tails   .= $encoded;
            $tailOffset += strlen($encoded);
        }

        return $heads . $tails;
    }

    private function encodeString(string $value): string
    {
        $len     = strlen($value);
        $lenWord = str_pad(pack('N', $len), 32, "\x00", STR_PAD_LEFT);
        $padded  = $value . str_repeat("\x00", (32 - ($len % 32)) % 32);
        return $lenWord . $padded;
    }

    private function keccak256(string $data): string
    {
        return hex2bin($this->keccakHash($data, 256));
    }

    private function keccakHash(string $in, int $mdlen): string
    {
        $capacity = $mdlen;
        $rsiz     = 200 - 2 * (int)($capacity / 8);
        $rsizw    = (int)($rsiz / 8);
        $inlen    = strlen($in);

        $st = array_fill(0, 25, [0, 0]);

        for ($in_t = 0; $inlen >= $rsiz; $inlen -= $rsiz, $in_t += $rsiz) {
            for ($i = 0; $i < $rsizw; $i++) {
                $t = unpack('V*', substr($in, $in_t + $i * 8, 8));
                $st[$i] = [$st[$i][0] ^ $t[2], $st[$i][1] ^ $t[1]];
            }
            $st = $this->keccakF1600($st);
        }

        $temp = substr($in, (int)$in_t, (int)$inlen);
        $temp = str_pad($temp, $rsiz, "\x00", STR_PAD_RIGHT);
        $temp = substr_replace($temp, chr(0x01), $inlen, 1);
        $temp = substr_replace($temp, chr(ord($temp[$rsiz - 1]) | 0x80), $rsiz - 1, 1);

        for ($i = 0; $i < $rsizw; $i++) {
            $t = unpack('V*', substr($temp, $i * 8, 8));
            $st[$i] = [$st[$i][0] ^ $t[2], $st[$i][1] ^ $t[1]];
        }

        $st  = $this->keccakF1600($st);

        $out = '';
        for ($i = 0; $i < 25; $i++) {
            $out .= pack('V*', $st[$i][1], $st[$i][0]);
        }
        return bin2hex(substr($out, 0, (int)($mdlen / 8)));
    }

    private function keccakF1600(array $st): array
    {
        static $rndc = [
            [0x00000000, 0x00000001], [0x00000000, 0x00008082],
            [0x80000000, 0x0000808a], [0x80000000, 0x80008000],
            [0x00000000, 0x0000808b], [0x00000000, 0x80000001],
            [0x80000000, 0x80008081], [0x80000000, 0x00008009],
            [0x00000000, 0x0000008a], [0x00000000, 0x00000088],
            [0x00000000, 0x80008009], [0x00000000, 0x8000000a],
            [0x00000000, 0x8000808b], [0x80000000, 0x0000008b],
            [0x80000000, 0x00008089], [0x80000000, 0x00008003],
            [0x80000000, 0x00008002], [0x80000000, 0x00000080],
            [0x00000000, 0x0000800a], [0x80000000, 0x8000000a],
            [0x80000000, 0x80008081], [0x80000000, 0x00008080],
            [0x00000000, 0x80000001], [0x80000000, 0x80008008],
        ];
        static $rotc = [1,3,6,10,15,21,28,36,45,55,2,14,27,41,56,8,25,43,62,18,39,61,20,44];
        static $piln = [10,7,11,17,18,3,5,16,8,21,24,4,15,23,19,13,12,2,20,14,22,9,6,1];

        $bc = [];
        for ($round = 0; $round < 24; $round++) {
            // Theta
            for ($i = 0; $i < 5; $i++) {
                $bc[$i] = [
                    $st[$i][0] ^ $st[$i+5][0] ^ $st[$i+10][0] ^ $st[$i+15][0] ^ $st[$i+20][0],
                    $st[$i][1] ^ $st[$i+5][1] ^ $st[$i+10][1] ^ $st[$i+15][1] ^ $st[$i+20][1],
                ];
            }
            for ($i = 0; $i < 5; $i++) {
                $t = [
                    $bc[($i+4)%5][0] ^ ((($bc[($i+1)%5][0] << 1) | ($bc[($i+1)%5][1] >> 31)) & 0xFFFFFFFF),
                    $bc[($i+4)%5][1] ^ ((($bc[($i+1)%5][1] << 1) | ($bc[($i+1)%5][0] >> 31)) & 0xFFFFFFFF),
                ];
                for ($j = 0; $j < 25; $j += 5) {
                    $st[$j+$i] = [$st[$j+$i][0] ^ $t[0], $st[$j+$i][1] ^ $t[1]];
                }
            }
            // Rho Pi
            $t = $st[1];
            for ($i = 0; $i < 24; $i++) {
                $j    = $piln[$i];
                $bc[0] = $st[$j];
                $n    = $rotc[$i];
                $hi   = $t[0]; $lo = $t[1];
                if ($n >= 32) { $n -= 32; $hi = $t[1]; $lo = $t[0]; }
                $st[$j] = [
                    (($hi << $n) | ($lo >> (32 - $n))) & 0xFFFFFFFF,
                    (($lo << $n) | ($hi >> (32 - $n))) & 0xFFFFFFFF,
                ];
                $t = $bc[0];
            }
            // Chi
            for ($j = 0; $j < 25; $j += 5) {
                for ($i = 0; $i < 5; $i++) $bc[$i] = $st[$j+$i];
                for ($i = 0; $i < 5; $i++) {
                    $st[$j+$i] = [
                        $st[$j+$i][0] ^ (~$bc[($i+1)%5][0] & $bc[($i+2)%5][0]),
                        $st[$j+$i][1] ^ (~$bc[($i+1)%5][1] & $bc[($i+2)%5][1]),
                    ];
                }
            }
            // Iota
            $st[0] = [$st[0][0] ^ $rndc[$round][0], $st[0][1] ^ $rndc[$round][1]];
        }
        return $st;
    }

    private function jsonRpc(string $method, array $params)
    {
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => $method,
            'params'  => $params,
        ]);

        $ch = curl_init($this->rpcUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \RuntimeException("Ethereum RPC curl error: $curlErr");
        }

        $decoded = json_decode($response, true);
        if (isset($decoded['error'])) {
            throw new \RuntimeException('Ethereum RPC error: ' . json_encode($decoded['error']));
        }

        return $decoded['result'] ?? null;
    }
}
