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
    private static $instance = null;

    private $rpcUrl;
    private $chainId;
    private $contractAddress;
    private $privateKey;
    private $signerAddress;

    // secp256k1 curve parameters
    private $P;
    private $N;
    private $Gx;
    private $Gy;

    private function __construct()
    {
        $cfg = Yii::$app->params['blockchain'];
        $this->rpcUrl          = $cfg['rpcUrl'];
        $this->chainId         = (int) $cfg['chainId'];
        $this->contractAddress = strtolower($cfg['contractAddress']);
        $key = $cfg['signerPrivateKey'];
        $this->privateKey    = strncmp($key, '0x', 2) === 0 ? substr($key, 2) : $key;
        $this->signerAddress = strtolower($cfg['signerAddress']);

        $this->P  = \gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', 16);
        $this->N  = \gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);
        $this->Gx = \gmp_init('79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798', 16);
        $this->Gy = \gmp_init('483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8', 16);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public function logConnectionStatusEvent(string $externalId, string $userName, string $reservationName, string $bandwidth, string $status, string $resourceStatus, string $dataplaneStatus, string $authStatus, string $start, string $finish) {
        $data = $this->encodeCall(
            'logConnectionStatusEvent((string,string,string,string,string,string,string,string,string,string))',
            [$externalId, $userName, $reservationName, $bandwidth, $status, $resourceStatus, $dataplaneStatus, $authStatus, $start, $finish]
        );
        $nonce = $this->getNonce($this->signerAddress);
        $gasPrice = $this->getGasPrice();
        $gasLimit = '0x' . dechex(200000);

        $rawTx = $this->buildSignedTransaction(
            $nonce,
            $gasPrice,
            $gasLimit,
            $this->contractAddress,
            '0x0',
            $data
        );

        return $this->jsonRpc('eth_sendRawTransaction', ['0x' . $rawTx]);
    }

    public function logConnectionAuthEvent(string $externalId, string $domain, string $status): string {
        $data = $this->encodeCall('logConnectionAuthEvent(string,string,string)', [$externalId, $domain, $status]);
        $nonce = $this->getNonce($this->signerAddress);
        $gasPrice = $this->getGasPrice();
        $gasLimit = '0x' . dechex(200000);

        $rawTx = $this->buildSignedTransaction(
            $nonce,
            $gasPrice,
            $gasLimit,
            $this->contractAddress,
            '0x0',
            $data
        );

        return $this->jsonRpc('eth_sendRawTransaction', ['0x' . $rawTx]);
    }

    // -------------------------------------------------------------------------
    // ABI encoding
    // -------------------------------------------------------------------------

    private function encodeCall(string $signature, array $args): string
    {
        $selector = substr($this->keccak256(hex2bin(bin2hex($signature))), 0, 4);

        // Detect tuple signature: single argument that is a tuple of all strings
        if (preg_match('/\(\(([^)]+)\)\)$/', $signature)) {
            // Single tuple argument — outer head points to tuple, tuple encodes args as head/tail
            $tupleEncoded = $this->encodeTuple($args);
            $head = str_pad(pack('N', 32), 32, "\x00", STR_PAD_LEFT);
            return bin2hex($selector . $head . $tupleEncoded);
        }

        // Flat string arguments
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

    // -------------------------------------------------------------------------
    // Transaction building & signing
    // -------------------------------------------------------------------------

    private function buildSignedTransaction(
        string $nonce,
        string $gasPrice,
        string $gasLimit,
        string $to,
        string $value,
        string $data
    ): string {
        $nonceInt    = hexdec(ltrim($nonce, '0x'));
        $gasPriceInt = \gmp_init(ltrim($gasPrice, '0x'), 16);
        $gasLimitInt = hexdec(ltrim($gasLimit, '0x'));
        $valueInt    = 0;
        $dataBytes   = hex2bin($data);
        $toBytes     = hex2bin(ltrim($to, '0x'));

        // EIP-155 signing payload: [nonce, gasPrice, gasLimit, to, value, data, chainId, 0, 0]
        $signingList = [
            $this->encodeRlpInt($nonceInt),
            $this->encodeRlpBigInt($gasPriceInt),
            $this->encodeRlpInt($gasLimitInt),
            $this->rlpBytes($toBytes),
            $this->encodeRlpInt($valueInt),
            $this->rlpBytes($dataBytes),
            $this->encodeRlpInt($this->chainId),
            $this->encodeRlpInt(0),
            $this->encodeRlpInt(0),
        ];
        $rlpPayload = $this->rlpList($signingList);
        $msgHash    = $this->keccak256($rlpPayload);

        list($r, $s, $v) = $this->ecSign($msgHash, $this->privateKey, $this->chainId);

        // Final signed transaction: [nonce, gasPrice, gasLimit, to, value, data, v, r, s]
        $signedList = [
            $this->encodeRlpInt($nonceInt),
            $this->encodeRlpBigInt($gasPriceInt),
            $this->encodeRlpInt($gasLimitInt),
            $this->rlpBytes($toBytes),
            $this->encodeRlpInt($valueInt),
            $this->rlpBytes($dataBytes),
            $this->encodeRlpBigInt($v),
            $this->encodeRlpBigInt($r),
            $this->encodeRlpBigInt($s),
        ];

        return bin2hex($this->rlpList($signedList));
    }

    // -------------------------------------------------------------------------
    // ECDSA over secp256k1
    // -------------------------------------------------------------------------

    private function ecSign(string $msgHashBytes, string $privKeyHex, int $chainId): array
    {
        $z    = \gmp_init(bin2hex($msgHashBytes), 16);
        $priv = \gmp_init($privKeyHex, 16);
        $N    = $this->N;

        // Deterministic k via RFC 6979 (simplified: use hash of privkey+hash)
        $k = $this->rfc6979($priv, $z);

        list($rx, $ry) = $this->ecMul($k, array($this->Gx, $this->Gy));
        $r = \gmp_mod($rx, $N);

        $kInv = \gmp_invert($k, $N);
        $s    = \gmp_mod(\gmp_mul($kInv, \gmp_add($z, \gmp_mul($priv, $r))), $N);

        // Normalise s to low-S form (EIP-2)
        if (\gmp_cmp($s, \gmp_div($N, 2)) > 0) {
            $s = \gmp_sub($N, $s);
        }

        // recovery id (0 or 1) + EIP-155 v
        $recId = \gmp_cmp(\gmp_mod($ry, \gmp_init(2)), \gmp_init(1)) === 0 ? 1 : 0;
        $v     = \gmp_init($chainId * 2 + 35 + $recId);

        return [$r, $s, $v];
    }

    /**
     * RFC 6979 deterministic k (HMAC-SHA256 based).
     */
    private function rfc6979(\GMP $privKey, \GMP $z): \GMP
    {
        $N    = $this->N;
        $qLen = 32;

        $privBytes = $this->gmpTo32Bytes($privKey);
        $hBytes    = $this->gmpTo32Bytes($z);

        $V = str_repeat("\x01", $qLen);
        $K = str_repeat("\x00", $qLen);

        $K = hash_hmac('sha256', $V . "\x00" . $privBytes . $hBytes, $K, true);
        $V = hash_hmac('sha256', $V, $K, true);
        $K = hash_hmac('sha256', $V . "\x01" . $privBytes . $hBytes, $K, true);
        $V = hash_hmac('sha256', $V, $K, true);

        while (true) {
            $V = hash_hmac('sha256', $V, $K, true);
            $k = \gmp_init(bin2hex($V), 16);
            if (\gmp_cmp($k, \gmp_init(1)) >= 0 && \gmp_cmp($k, $N) < 0) {
                return $k;
            }
            $K = hash_hmac('sha256', $V . "\x00", $K, true);
            $V = hash_hmac('sha256', $V, $K, true);
        }
    }

    // secp256k1 point multiplication (double-and-add)
    private function ecMul(\GMP $k, array $point): array
    {
        $result = null;
        $addend = $point;
        $bits   = \gmp_strval($k, 2);

        for ($i = strlen($bits) - 1; $i >= 0; $i--) {
            if ($bits[$i] === '1') {
                $result = $result === null ? $addend : $this->ecAdd($result, $addend);
            }
            $addend = $this->ecAdd($addend, $addend);
        }
        return $result;
    }

    private function ecAdd(array $p1, array $p2): array
    {
        $P  = $this->P;
        list($x1, $y1) = $p1;
        list($x2, $y2) = $p2;

        if (\gmp_cmp($x1, $x2) === 0 && \gmp_cmp($y1, $y2) === 0) {
            $m = \gmp_mod(
                \gmp_mul(
                    \gmp_mul(\gmp_init(3), \gmp_mul($x1, $x1)),
                    \gmp_invert(\gmp_mul(\gmp_init(2), $y1), $P)
                ),
                $P
            );
        } else {
            $m = \gmp_mod(
                \gmp_mul(
                    \gmp_sub($y2, $y1),
                    \gmp_invert(\gmp_sub($x2, $x1), $P)
                ),
                $P
            );
        }

        $x3 = \gmp_mod(\gmp_sub(\gmp_sub(\gmp_mul($m, $m), $x1), $x2), $P);
        $y3 = \gmp_mod(\gmp_sub(\gmp_mul($m, \gmp_sub($x1, $x3)), $y1), $P);
        // keep positive
        if (\gmp_cmp($x3, \gmp_init(0)) < 0) $x3 = \gmp_add($x3, $P);
        if (\gmp_cmp($y3, \gmp_init(0)) < 0) $y3 = \gmp_add($y3, $P);

        return [$x3, $y3];
    }

    // -------------------------------------------------------------------------
    // RLP encoding
    // -------------------------------------------------------------------------

    private function rlpList(array $encodedItems): string
    {
        $payload = implode('', $encodedItems);
        return $this->rlpLengthPrefix(strlen($payload), 0xc0) . $payload;
    }

    private function rlpBytes(string $bytes): string
    {
        $len = strlen($bytes);
        if ($len === 1 && ord($bytes[0]) < 0x80) {
            return $bytes;
        }
        return $this->rlpLengthPrefix($len, 0x80) . $bytes;
    }

    private function encodeRlpInt(int $n): string
    {
        if ($n === 0) return "\x80";
        $hex   = dechex($n);
        if (strlen($hex) % 2 !== 0) $hex = '0' . $hex;
        return $this->rlpBytes(hex2bin($hex));
    }

    private function encodeRlpBigInt(\GMP $n): string
    {
        if (\gmp_cmp($n, \gmp_init(0)) === 0) return "\x80";
        $hex = \gmp_strval($n, 16);
        if (strlen($hex) % 2 !== 0) $hex = '0' . $hex;
        $bytes = hex2bin($hex);
        // strip leading zero byte added for sign if present
        if (ord($bytes[0]) === 0x00) $bytes = substr($bytes, 1);
        return $this->rlpBytes($bytes);
    }

    private function rlpLengthPrefix(int $len, int $base): string
    {
        if ($len <= 55) {
            return chr($base + $len);
        }
        $lenBytes = '';
        $tmp = $len;
        while ($tmp > 0) {
            $lenBytes = chr($tmp & 0xff) . $lenBytes;
            $tmp >>= 8;
        }
        return chr($base + 55 + strlen($lenBytes)) . $lenBytes;
    }

    // -------------------------------------------------------------------------
    // Keccak-256 — ported from kornrunner/php-keccak (MIT licence)
    // Uses 32-bit words (two uint32 per lane) so it works on both 32-bit and
    // 64-bit PHP without GMP and avoids PHP signed-integer edge cases.
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // JSON-RPC helpers
    // -------------------------------------------------------------------------

    private function getNonce(string $address): string
    {
        return $this->jsonRpc('eth_getTransactionCount', [$address, 'latest']);
    }

    private function getGasPrice(): string
    {
        return $this->jsonRpc('eth_gasPrice', []);
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

    // -------------------------------------------------------------------------
    // Utilities
    // -------------------------------------------------------------------------

    private function gmpTo32Bytes(\GMP $n): string
    {
        $hex = \gmp_strval($n, 16);
        $hex = str_pad($hex, 64, '0', STR_PAD_LEFT);
        return hex2bin($hex);
    }
}
