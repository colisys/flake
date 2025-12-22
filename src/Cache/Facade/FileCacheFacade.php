<?php

namespace Flake\Cache\Facade;

use Flake\Attributes\Component;
use Flake\Cache\Exception\CacheFolderNotWritableException;
use Flake\Cache\Exception\CacheHashMismatchException;
use Flake\Cache\Exception\CacheTrunkCorruptionException;
use Flake\Cache\Exception\CacheVersionException;

enum Compression: int
{
    case NONE = 0;
    case GZIP = 1;
}

enum Serializer: int
{
    case PHP = 0;
    case JSON = 1;
}

enum Trunk: int
{
    case NONE = 0;
    case Trunk = 1;
}

#[Component(singleton: false)]
class FileCacheFacade implements AbstractFacade
{
    protected string $basePath = "";

    const MAGIC = 'FLAKECACHE';
    protected int $version = 1;
    protected Serializer $serializer = Serializer::JSON;
    protected Compression $compression = Compression::GZIP;
    protected Trunk $trunk = Trunk::NONE;
    protected int $trunkSize = 10240;
    protected int $headerLength = 0;

    /**
     * @param array{"save_path":?string,"serializer":string|int,"compression":string|int,"trunk":string|int,"trunk_size":int} $options
     */
    public function __construct(
        array $options = []
    ) {
        $this->basePath = $options['save_path'] ?? BASE_DIR . '/cache';
        @mkdir($this->basePath, 0755, true);

        $this->serializer = Serializer::tryFrom($options['serializer'] ?? 0) ?? Serializer::PHP;
        $this->compression = Compression::tryFrom($options['compression'] ?? 0) ?? Compression::NONE;
        $this->trunk = Trunk::tryFrom($options['chunk'] ?? 0) ?? Trunk::NONE;
        $this->trunkSize = $options['chunk_size'] ?? 10240;

        $this->headerLength = strlen(self::MAGIC
            . pack("CCPqC", 0, 0, 0, 0, 0)
            . hash("crc32b", ""));
    }

    public function set($key, $value, $ttl = -1)
    {
        if ($this->has($key))
            $this->delete($key);

        $key = hash("crc32b", $key);
        $subdir_name = substr($key, 0, 2);
        @mkdir($this->basePath . '/' . $subdir_name, 0755, true);

        $time = time();
        $expires = $ttl < 0 ?  "-1" : ($time + $ttl);
        $tag = 0;
        $cache_name = "flake_{$key}_{$expires}.cache";

        ($this->serializer === Serializer::PHP) && ($value = serialize($value));
        ($this->serializer === Serializer::JSON) && (($tag |= (0x01 << 0)) && ($value = json_encode($value)));
        ($this->trunk === Trunk::Trunk) && ($tag |= (0x01 << 2));
        ($this->compression === Compression::GZIP) && ($tag |= (0x01 << 1)) && ($value = gzencode($value));

        if ($this->trunk) {
            $index = 0;
            $len = 0;
            while (strlen($chunk = substr($value, $len, max(1024, $this->trunkSize) - $this->headerLength))) {
                $data = $this->buildBlock($tag, $time, $expires, $chunk, $index);
                if (file_put_contents($this->basePath . '/' . $subdir_name . '/' . $cache_name . '.' . $index, $data, LOCK_EX) === false) {
                    throw new CacheFolderNotWritableException("Cache folder \"{$this->basePath}\" is not writable");
                }
                $len += strlen($chunk);
                $index++;
            }
        } else {
            $data = $this->buildBlock($tag, $time, $expires, $value);
            return file_put_contents($this->basePath . '/' . $subdir_name . '/' . $cache_name, $data, LOCK_EX) !== false;
        }

        return true;
    }

    protected function buildBlock(int $tag, int $time, int $expires, $value, int $chunk_index = 0)
    {
        $hash = hash("crc32b", $value);
        return self::MAGIC
            . pack("C", $this->version)
            . pack("C", $tag)
            . pack("P", $time)
            . pack("q", $expires)
            . pack("C", $chunk_index)
            . $hash
            . $value;
    }

    public function get($key, $default = null)
    {
        if (!$this->has($key))
            return $default;

        $hkey = hash("crc32b", $key);
        $subdir_name = substr($hkey, 0, 2);
        $files = glob($this->basePath . "/{$subdir_name}/flake_{$hkey}_*.cache*");

        $file = array_shift($files);

        list($value, $chidx, $chunk, $compression, $serializer) = $this->read($file, $key, $default);

        if ($chunk) {
            foreach ($files as $index => $file) {
                list($_value, $_chidx) = $this->read($file, $key, $default);
                if ($_chidx - $chidx - 1 != $index)
                    throw new CacheTrunkCorruptionException("Cache trunk is corrupted");

                $value .= $_value;
            }
        }

        ($compression === Compression::GZIP) && ($value = gzdecode($value));
        ($serializer === Serializer::JSON) && ($value = json_decode($value));
        ($serializer === Serializer::PHP) && ($value = unserialize($value));

        return $value;
    }

    protected function read(string $file, string $key, $default = null)
    {
        $data = file_get_contents($file);
        $magic = substr($data, 0, strlen(self::MAGIC));
        if ($magic !== self::MAGIC) {
            return [$default, 0, false, false, false];
        }
        $data = unpack("Cver/Ctag/Ptime/qexpires/Cchidx/a8hash/a*value", substr($data, strlen(self::MAGIC)));

        if ($data['ver'] > $this->version)
            throw new CacheVersionException("Cache version is higher than supported version");

        $valueHash = hash('crc32b', $data['value']);
        if ($valueHash != $data['hash'])
            throw new CacheHashMismatchException("Cache hash mismatch");

        if ($data['expires'] != -1 && $data['expires'] < time()) {
            $this->delete($key);
            return [$default, 0, false, false, false];
        }

        return [
            $data['value'],
            $data['chidx'],
            Trunk::from($data['tag'] & (0x01 << 2) >> 2),
            Compression::from($data['tag'] & (0x01 << 1) >> 1),
            Serializer::from($data['tag'] & (0x01 << 0))
        ];
    }

    public function has($key)
    {
        $key = hash("crc32b", $key);
        $subdir_name = substr($key, 0, 2);
        foreach (glob($this->basePath . "/{$subdir_name}/flake_{$key}_*.cache*") as $file) {
            return true;
        }
        return false;
    }

    public function delete($key)
    {
        $key = hash("crc32b", $key);
        $subdir_name = substr($key, 0, 2);
        foreach (glob($this->basePath . "/{$subdir_name}/flake_{$key}_*.cache*") as $file) {
            unlink($file);
        }
    }

    public function __destruct() {}
}
