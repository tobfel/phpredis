<?php

// Run with php -n and the DLL extracted from the Windows release package.
if (!extension_loaded('redis')) {
    throw new RuntimeException('Redis did not load');
}

foreach (['Redis', 'RedisArray', 'RedisCluster', 'RedisSentinel'] as $class) {
    if (!class_exists($class, false)) {
        throw new RuntimeException("Missing class $class");
    }
}

$redis = new Redis();
$value = ['message' => 'Windows PIE package', 'values' => [1, true, null]];
if (!$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP) ||
    $redis->_unserialize($redis->_serialize($value)) !== $value) {
    throw new RuntimeException('PHP serializer round trip failed');
}

foreach (['redis', 'rediscluster'] as $handler) {
    if (ini_set('session.save_handler', $handler) === false ||
        ini_get('session.save_handler') !== $handler) {
        throw new RuntimeException("Session handler $handler is unavailable");
    }
}

$expectIgbinary = in_array('--igbinary', $argv, true);
if ($expectIgbinary) {
    if (!extension_loaded('igbinary') || !defined('Redis::SERIALIZER_IGBINARY')) {
        throw new RuntimeException('Igbinary extension or Redis serializer support is missing');
    }
    if (!$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY) ||
        $redis->_unserialize($redis->_serialize($value)) !== $value) {
        throw new RuntimeException('Igbinary serializer round trip failed');
    }
} elseif (defined('Redis::SERIALIZER_IGBINARY')) {
    throw new RuntimeException('Unexpected optional feature SERIALIZER_IGBINARY');
}

foreach (['SERIALIZER_MSGPACK', 'COMPRESSION_LZF',
          'COMPRESSION_LZ4', 'COMPRESSION_ZSTD'] as $constant) {
    if (defined("Redis::$constant")) {
        throw new RuntimeException("Unexpected optional feature $constant");
    }
}

echo 'Redis ', phpversion('redis'), " Windows package smoke test passed\n";
