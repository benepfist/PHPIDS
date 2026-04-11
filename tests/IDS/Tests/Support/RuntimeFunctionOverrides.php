<?php

namespace IDS\Tests\Support {
    final class RuntimeFunctionOverrides
    {
        public static bool $forceCachingSerializeFailure = false;

        /**
         * @var array<string, bool>
         */
        public static array $cachingFopenFailures = [];

        /**
         * @var array<string, mixed>
         */
        public static array $apcStorage = [];

        /**
         * @var array<string, bool>
         */
        public static array $providerExtensionOverrides = [];

        /**
         * @var array<string, string|false>
         */
        public static array $providerFileContentsOverrides = [];

        public static function reset(): void
        {
            self::$forceCachingSerializeFailure = false;
            self::$cachingFopenFailures = [];
            self::$apcStorage = [];
            self::$providerExtensionOverrides = [];
            self::$providerFileContentsOverrides = [];
            if (\class_exists('\Memcache') && \method_exists('\Memcache', 'resetStorage')) {
                \Memcache::resetStorage();
            }
        }
    }
}

namespace {
    if (!class_exists('Memcache')) {
        class Memcache
        {
            /**
             * @var array<string, mixed>
             */
            private static array $storage = [];

            public static function resetStorage(): void
            {
                self::$storage = [];
            }

            public function pconnect(string $host, int $port): bool
            {
                return $host !== '' && $port > 0;
            }

            public function set(string $key, mixed $value, int $flags, mixed $ttl): bool
            {
                self::$storage[$key] = $value;

                return true;
            }

            public function get(string $key): mixed
            {
                return self::$storage[$key] ?? false;
            }
        }
    }
}

namespace IDS\Caching {
    use IDS\Tests\Support\RuntimeFunctionOverrides;

    function serialize(mixed $value): string|false
    {
        if (RuntimeFunctionOverrides::$forceCachingSerializeFailure) {
            return false;
        }

        return \serialize($value);
    }

    function fopen(string $filename, string $mode)
    {
        if (array_key_exists($filename, RuntimeFunctionOverrides::$cachingFopenFailures)) {
            return false;
        }

        return \fopen($filename, $mode);
    }

    function apc_store(string $key, mixed $value, mixed $ttl = 0): bool
    {
        RuntimeFunctionOverrides::$apcStorage[$key] = $value;

        return true;
    }

    function apc_fetch(string $key): mixed
    {
        return RuntimeFunctionOverrides::$apcStorage[$key] ?? false;
    }
}

namespace IDS\Filter\Provider {
    use IDS\Tests\Support\RuntimeFunctionOverrides;

    function extension_loaded(string $name): bool
    {
        $key = strtolower($name);
        if (array_key_exists($key, RuntimeFunctionOverrides::$providerExtensionOverrides)) {
            return RuntimeFunctionOverrides::$providerExtensionOverrides[$key];
        }

        return \extension_loaded($name);
    }

    function file_get_contents(string $filename): string|false
    {
        if (array_key_exists($filename, RuntimeFunctionOverrides::$providerFileContentsOverrides)) {
            return RuntimeFunctionOverrides::$providerFileContentsOverrides[$filename];
        }

        return \file_get_contents($filename);
    }
}
