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

// The first Windows package has no optional serializer/compression dependencies.
// Allow packaging variants to opt in to including optional features by setting
// the environment variable ALLOW_OPTIONAL_FEATURES=1 in CI.
$allowOptional = getenv('ALLOW_OPTIONAL_FEATURES') === '1';

$found = [];
foreach (['SERIALIZER_IGBINARY', 'SERIALIZER_MSGPACK', 'SERIALIZER_PHPSER',
          'COMPRESSION_LZF', 'COMPRESSION_LZ4', 'COMPRESSION_ZSTD'] as $constant) {
    if (defined("Redis::$constant")) {
        $found[] = $constant;
    }
}

if (!empty($found)) {
    if (!$allowOptional) {
        throw new RuntimeException('Unexpected optional feature(s): ' . implode(', ', $found));
    } else {
        // Non-fatal: print a warning so packagers can see what's included.
        fwrite(STDERR, 'Warning: Optional features present: ' . implode(', ', $found) . PHP_EOL);
    }
}

echo 'Redis ', phpversion('redis'), " Windows package smoke test passed\n";
?>
