<?php

namespace PatrickRose\Flysystem\Redis;

use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use Predis\ClientInterface;

class RedisAdapter implements AdapterInterface
{
    use NotSupportingVisibilityTrait;

    const EXPIRE_IN_SECONDS = 'EX';
    const EXPIRE_IN_MILLISECONDS = 'PX';

    const SET_IF_KEY_NOT_EXISTS = 'NX';
    const SET_IF_KEY_EXISTS = 'XX';

    /**
     * @var Client
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        if ($config->has('ttl') && !$config->has('expirationType'))
        {
            $config->set('expirationType', self::EXPIRE_IN_SECONDS);
        }

        if (!$this->client->set($path, $contents, $config->get('expirationType'), $config->get('ttl'), $config->get('setFlag'))) {
            return false;
        }

        return compact('path', 'contents');
    }

    /**
     * Write a new file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        if (!rewind($resource)) {
            return false;
        }

        return $this->write($path, stream_get_contents($resource), $config);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        return $this->client->rename($path, $newpath);
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $keyValue = $this->client->get($path);

        if ($keyValue === false) {
            return false;
        }

        return $this->client->set($newpath, $keyValue);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        return $this->client->del([$path]);
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        return $this->client->del($this->client->keys($dirname.'/*'));
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        return [
            'path' => $dirname,
        ];
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        return $this->client->exists($path) === 1;
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        return ['contents' => $this->client->get($path)];
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        $stream = tmpfile();
        fwrite($stream, $this->client->get($path));
        rewind($stream);

        return ['stream' => $stream];
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $keys = $this->client->keys($directory.'/*');

        $values = [];

        foreach ($keys as $key) {
            if (!$recursive && preg_match("|$directory/.+/|", $key) !== 0) {
                continue;
            }

            $stream = tmpfile();
            fwrite($stream, $this->client->get($key));
            rewind($stream);
            $values[$key] = ['type' => Util::guessMimeType(stream_get_meta_data($stream)['uri'], stream_get_contents($stream))];
        }

        return Util::emulateDirectories($values);
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        return [];
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return [];
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return [];
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return [];
    }
}
