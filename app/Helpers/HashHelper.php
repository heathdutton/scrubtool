<?php

namespace App\Helpers;

class HashHelper
{

    /**
     * All hash algorithms supported by PHP 7.3
     *  - Keyed by the length of the hex hash.
     *  - Ordered by popularity.
     *  - Official name.
     *  - Result of using the hash on an empty string.
     *  - Priority (Google Trends).
     *
     * @var array
     */
    const ALGOS = [
        8   => [
            'fnv132'  => [
                'id'         => 'fnv132',
                'name'       => 'FNV-132',
                'empty'      => '811c9dc5',
                'popularity' => 9570000,
            ],
            'crc32'   => [
                'id'         => 'crc32',
                'name'       => 'CRC-32',
                'empty'      => '00000000',
                'popularity' => 3970000,
            ],
            'crc32b'  => [
                'id'         => 'crc32b',
                'name'       => 'CRC-32b',
                'empty'      => '00000000',
                'popularity' => 1160000,
            ],
            'adler32' => [
                'id'         => 'adler32',
                'name'       => 'Adler-32',
                'empty'      => '00000001',
                'popularity' => 1110000,
            ],
            'joaat'   => [
                'id'         => 'joaat',
                'name'       => 'Joaat',
                'empty'      => '00000000',
                'popularity' => 661000,
            ],
            'fnv1a32' => [
                'id'         => 'fnv1a32',
                'name'       => 'FNV-1a32',
                'empty'      => '811c9dc5',
                'popularity' => 100,
            ],
        ],
        16  => [
            'fnv164'  => [
                'id'         => 'fnv164',
                'name'       => 'FNV-164',
                'empty'      => 'cbf29ce484222325',
                'popularity' => 9610000,
            ],
            'fnv1a64' => [
                'id'         => 'fnv1a64',
                'name'       => 'FNV-1a64',
                'empty'      => 'cbf29ce484222325',
                'popularity' => 9590000,
            ],
        ],
        32  => [
            'md5'        => [
                'id'         => 'md5',
                'name'       => 'MD5',
                'empty'      => 'd41d8cd98f00b204e9800998ecf8427e',
                'popularity' => 72600000,
            ],
            'md2'        => [
                'id'         => 'md2',
                'name'       => 'MD2',
                'empty'      => '8350e5a3e24c153df2275c9f80692773',
                'popularity' => 16500000,
            ],
            'md4'        => [
                'id'         => 'md4',
                'name'       => 'MD4',
                'empty'      => '31d6cfe0d16ae931b73c59d7e0c089c0',
                'popularity' => 6620000,
            ],
            'ripemd128'  => [
                'id'         => 'ripemd128',
                'name'       => 'RIPEMD-128',
                'empty'      => 'cdf26213a150dc3ecb610f18f6b38b46',
                'popularity' => 1220000,
            ],
            'tiger128,3' => [
                'id'         => 'tiger128,3',
                'name'       => 'Tiger-128 (3 passes)',
                'empty'      => '3293ac630c13f0245f92bbb1766e1616',
                'popularity' => 1080000,
            ],
            'tiger128,4' => [
                'id'         => 'tiger128,4',
                'name'       => 'Tiger-128 (4 passes)',
                'empty'      => '24cc78a7f6ff3546e7984e59695ca13d',
                'popularity' => 1080000,
            ],
            'haval128,3' => [
                'id'         => 'haval128,3',
                'name'       => 'HAVAL-128 (3 passes)',
                'empty'      => 'c68f39913f901f3ddf44c707357a7d70',
                'popularity' => 1070000,
            ],
            'haval128,4' => [
                'id'         => 'haval128,4',
                'name'       => 'HAVAL-128 (4 passes)',
                'empty'      => 'ee6bbf4d6a46a679b3a856c88538bb98',
                'popularity' => 1070000,
            ],
            'haval128,5' => [
                'id'         => 'haval128,5',
                'name'       => 'HAVAL-128 (5 passes)',
                'empty'      => '184b8482a0c050dca54b59c7f05bf5dd',
                'popularity' => 1070000,
            ],
        ],
        40  => [
            'sha1'       => [
                'id'         => 'sha1',
                'name'       => 'SHA-1',
                'empty'      => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
                'popularity' => 29800000,
            ],
            'ripemd160'  => [
                'id'         => 'ripemd160',
                'name'       => 'RIPEMD-160',
                'empty'      => '9c1185a5c5e9fc54612808977ee8f548b2258d31',
                'popularity' => 1170000,
            ],
            'tiger160,3' => [
                'id'         => 'tiger160,3',
                'name'       => 'Tiger-160 (3 passes)',
                'empty'      => '3293ac630c13f0245f92bbb1766e16167a4e5849',
                'popularity' => 1300000,
            ],
            'tiger160,4' => [
                'id'         => 'tiger160,4',
                'name'       => 'Tiger-160 (4 passes)',
                'empty'      => '24cc78a7f6ff3546e7984e59695ca13d804e0b68',
                'popularity' => 1300000,
            ],
            'haval160,3' => [
                'id'         => 'haval160,3',
                'name'       => 'HAVAL-160 (3 passes)',
                'empty'      => 'd353c3ae22a25401d257643836d7231a9a95f953',
                'popularity' => 94,
            ],
            'haval160,4' => [
                'id'         => 'haval160,4',
                'name'       => 'HAVAL-160 (4 passes)',
                'empty'      => '1d33aae1be4146dbaaca0b6e70d7a11f10801525',
                'popularity' => 94,
            ],
            'haval160,5' => [
                'id'         => 'haval160,5',
                'name'       => 'HAVAL 160 (5 passes)',
                'empty'      => '255158cfc1eed1a7be7c55ddd64d9790415b933b',
                'popularity' => 94,
            ],
        ],
        48  => [
            'tiger192,3' => [
                'id'         => 'tiger192,3',
                'name'       => 'Tiger-192 (3 passes)',
                'empty'      => '3293ac630c13f0245f92bbb1766e16167a4e58492dde73f3',
                'popularity' => 1120000,
            ],
            'tiger192,4' => [
                'id'         => 'tiger192,4',
                'name'       => 'Tiger-192 (4 passes)',
                'empty'      => '24cc78a7f6ff3546e7984e59695ca13d804e0b686e255194',
                'popularity' => 1120000,
            ],
            'haval192,3' => [
                'id'         => 'haval192,3',
                'name'       => 'HAVAL-192 (3 passes)',
                'empty'      => 'e9c48d7903eaf2a91c5b350151efcb175c0fc82de2289a4e',
                'popularity' => 99,
            ],
            'haval192,4' => [
                'id'         => 'haval192,4',
                'name'       => 'HAVAL-192 (4 passes)',
                'empty'      => '4a8372945afa55c7dead800311272523ca19d42ea47b72da',
                'popularity' => 99,
            ],
            'haval192,5' => [
                'id'         => 'haval192,5',
                'name'       => 'HAVAL-192 (5 passes)',
                'empty'      => '4839d0626f95935e17ee2fc4509387bbe2cc46cb382ffe85',
                'popularity' => 99,
            ],
        ],
        56  => [
            'haval224,3' => [
                'id'         => 'haval224,3',
                'name'       => 'HAVAL-224 (3 passes)',
                'empty'      => 'c5aae9d47bffcaaf84a8c6e7ccacd60a0dd1932be7b1a192b9214b6d',
                'popularity' => 10700000,
            ],
            'haval224,4' => [
                'id'         => 'haval224,4',
                'name'       => 'HAVAL-224 (4 passes)',
                'empty'      => '3e56243275b3b81561750550e36fcd676ad2f5dd9e15f2e89e6ed78e',
                'popularity' => 10700000,
            ],
            'haval224,5' => [
                'id'         => 'haval224,5',
                'name'       => 'HAVAL-224 (5 passes)',
                'empty'      => '4a0513c032754f5582a758d35917ac9adf3854219b39e3ac77d1837e',
                'popularity' => 10700000,
            ],
            'sha512/224' => [
                'id'         => 'sha512/224',
                'name'       => 'SHA-512/224',
                'empty'      => '6ed0dd02806fa89e25de060c19d3ac86cabb87d6a0ddd05c333b84f4',
                'popularity' => 1270000,
            ],
            'sha224'     => [
                'id'         => 'sha224',
                'name'       => 'SHA-224',
                'empty'      => 'd14a028c2a3a2bc9476102bb288234c415a2b01f828ea62ac5b3e42f',
                'popularity' => 981000,
            ],
            'sha3-224'   => [
                'id'         => 'sha3-224',
                'name'       => 'SHA-3-224',
                'empty'      => '6b4e03423667dbb73b6e15454f0eb1abd4597f9a1b078e3f5b5a6bc7',
                'popularity' => 117000,
            ],
        ],
        64  => [
            'sha256'      => [
                'id'         => 'sha256',
                'name'       => 'SHA-256',
                'empty'      => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                'popularity' => 29300000,
            ],
            'gost'        => [
                'id'         => 'gost',
                'name'       => 'Gost',
                'empty'      => 'ce85b99cc46752fffee35cab9a7b0278abb4c2d2055cff685af4912c49490f8d',
                'popularity' => 1660000,
            ],
            'gost-crypto' => [
                'id'         => 'gost-crypto',
                'name'       => 'Gost-Crypto',
                'empty'      => '981e5f3ca30c841487830f84fb433e13ac1101569b9c13584ac483234cd656c0',
                'popularity' => 753000,
            ],
            'ripemd256'   => [
                'id'         => 'ripemd256',
                'name'       => 'RIPEMD-256',
                'empty'      => '02ba4c4e5f8ecd1877fc52d64d30e37a2d9774fb1e5d026380ae0168e3c5522d',
                'popularity' => 1230000,
            ],
            'snefru256'   => [
                'id'         => 'snefru256',
                'name'       => 'Snefru-256',
                'empty'      => '8617f366566a011837f4fb4ba5bedea2b892f3ed8b894023d16ae344b2be5881',
                'popularity' => 9640000,
            ],
            'snefru'      => [
                'id'         => 'snefru',
                'name'       => 'Snefru',
                'empty'      => '8617f366566a011837f4fb4ba5bedea2b892f3ed8b894023d16ae344b2be5881',
                'popularity' => 3440000,
            ],
            'sha512/256'  => [
                'id'         => 'sha512/256',
                'name'       => 'SHA-512/256',
                'empty'      => 'c672b8d1ef56ed28ab87c3622c5114069bdd3ad7b8f9737498d0c01ecef0967a',
                'popularity' => 2190000,
            ],
            'sha3-256'    => [
                'id'         => 'sha3-256',
                'name'       => 'SHA-3-256',
                'empty'      => 'a7ffc6f8bf1ed76651c14756a061d662f580ff4de43b49fa82d80a4b80f8434a',
                'popularity' => 273000,
            ],
            'haval256,3'  => [
                'id'         => 'haval256,3',
                'name'       => 'HAVAL-256 (3 passes)',
                'empty'      => '4f6938531f0bc8991f62da7bbd6f7de3fad44562b8c6f4ebf146d5b4e46f7c17',
                'popularity' => 97,
            ],
            'haval256,4'  => [
                'id'         => 'haval256,4',
                'name'       => 'HAVAL-256 (4 passes)',
                'empty'      => 'c92b2e23091e80e375dadce26982482d197b1a2521be82da819f8ca2c579b99b',
                'popularity' => 97,
            ],
            'haval256,5'  => [
                'id'         => 'haval256,5',
                'name'       => 'HAVAL-256 (5 passes)',
                'empty'      => 'be417bb4dd5cfb76c7126f4f8eeb1553a449039307b1a3cd451dbfdc0fbbe330',
                'popularity' => 97,
            ],
        ],
        80  => [
            'ripemd320' => [
                'id'         => 'ripemd320',
                'name'       => 'RIPEMD-320',
                'empty'      => '22d65d5661536cdc75c1fdf5c6de7b41b9f27325ebc61e8557177d705a0ec880151c3a32a00899b8',
                'popularity' => 10600000,
            ],
        ],
        96  => [
            'sha384'   => [
                'id'         => 'sha384',
                'name'       => 'SHA-384',
                'empty'      => '38b060a751ac96384cd9327eb1b1e36a21fdb71114be07434c0cc7bf63f6e1da274edebfe76f65fbd51ad2f14898b95b',
                'popularity' => 2060000,
            ],
            'sha3-384' => [
                'id'         => 'sha3-384',
                'name'       => 'SHA-3-384',
                'empty'      => '0c63a75b845e4f7d01107d852e4c2485c51a50aaaa94fc61995e71bbee983a2ac3713831264adb47fb6bd1e058d5f004',
                'popularity' => 113000,
            ],
        ],
        128 => [
            'sha512'    => [
                'id'         => 'sha512',
                'name'       => 'SHA-512',
                'empty'      => 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e',
                'popularity' => 4380000,
            ],
            'whirlpool' => [
                'id'         => 'whirlpool',
                'name'       => 'Whirlpool',
                'empty'      => '19fa61d75522a4669b44e39c1d2e1726c530232130d407f89afee0964997f7a73e83be698b288febcf88e3e03c4f0757ea8964e59b63d93708b138cc42a66eb3',
                'popularity' => 2310000,
            ],
            'sha3-512'  => [
                'id'         => 'sha3-512',
                'name'       => 'SHA-3-512',
                'empty'      => 'a69f73cca23a9ac5c8b567dc185a756e97c982164fe25859e0d1dcc1475c80a615b2123af1f5f94c11e3e9402c3ac558f500199d95b6d3e301758586281dcd26',
                'popularity' => 178000,
            ],
        ],
    ];

    /** @var array */
    protected $simpleAgos = [];

    public function list($regenerate = false, $simple = false)
    {
        if ($regenerate) {
            $lengths = [];
            foreach (hash_algos() as $algo) {
                $result = hash($algo, '', false);
                $length = strlen($result);
                if (!isset($algos[$length])) {
                    $lengths[$length] = [];
                }
                $lengths[$length][$algo] = [
                    'id'         => $algo,
                    'name'       => ucwords($algo),
                    'empty'      => $result,
                    'popularity' => 0,
                ];
            }
            ksort($lengths);
            foreach ($lengths as $length => &$algs) {
                uksort($algs, 'strnatcmp');
            }
            $result = $lengths;
        } else {
            $result = self::ALGOS;
        }
        if ($simple) {
            if (!$this->simpleAgos) {
                $simpleAlgos = [];
                foreach ($result as $length => $algos) {
                    foreach ($algos as $algo) {
                        $simpleAlgos[strtolower(preg_replace('/[^a-z]/i', '', $algo['id']))] = true;
                    }
                }
                $this->simpleAgos = array_keys($simpleAlgos);
            }
            $result = $this->simpleAgos;
        }

        return $result;
    }

    /**
     * Detects simple hash algorithms.
     *
     * Does not support salted hashes or higher encryption patterns for performance.
     *
     * @param $string
     * @param  string  $hashName  Possible hash name from column definition.
     *
     * @return mixed|null
     */
    public function detectHash($string, $hashName = '')
    {
        $selection = null;
        $string    = strtolower(trim($string));
        $length    = strlen($string);
        // Remove Octal identifiers.
        if (in_array(substr($string, 0, 2), ['0x', '0h'])) {
            $string = substr($string, 2);
            $length -= 2;
        } elseif (
            !isset(self::ALGOS[$length])
            && isset(self::ALGOS[$length - 1])
            && '0' === substr($string, 0, 1)
        ) {
            $string = substr($string, 1);
            $length--;
        }
        if (
            isset(self::ALGOS[$length])
            && 1 === preg_match('/^[0-9a-f]+$/', $string)
        ) {
            $alternatives    = [];
            $totalPopularity = 0;
            $certainty       = 0;
            foreach (self::ALGOS[$length] as $algo => $attributes) {
                $totalPopularity += $attributes['popularity'];
                if (!$selection) {
                    $selection = $attributes;
                } else {
                    $alternatives[$algo] = $attributes;
                }
                if (
                    $string === $attributes['empty']
                    || $hashName == $attributes['name']
                    || $hashName == $attributes['id']
                ) {
                    $selection    = $attributes;
                    $certainty    = 100;
                    $alternatives = [];
                    break;
                }
            }
            if (!$certainty) {
                $certainty = 100 / $totalPopularity * $selection['popularity'];
            }
            $selection['hash']         = $string;
            $selection['certainty']    = $certainty;
            $selection['alternatives'] = $alternatives;
        }

        return $selection;
    }
}
