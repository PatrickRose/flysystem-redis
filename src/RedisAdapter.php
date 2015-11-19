<?php

namespace PatrickRose\Flysystem\Redis;

use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use Predis\ClientInterface;

class RedisAdapter implements AdapterInterface
{
    use NotSupportingVisibilityTrait, StreamedTrait;

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
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        if ($config->has('ttl') && !$config->has('expirationType')) {
            $config->set('expirationType', self::EXPIRE_IN_SECONDS);
        }

        $args = array_filter([
            $path,
            $contents,
            $config->get('expirationType'),
            $config->get('ttl'),
            $config->get('setFlag')
        ]);


        if (!call_user_func_array([$this->client, 'set'], $args)) {
            return false;
        }

        return compact('path', 'contents');
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
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
        return (bool)$this->client->del([$path]);
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
        return (bool)$this->client->del($this->client->keys($dirname . '/*'));
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
        $keys = $this->client->keys($directory . '/*');

        $values = [];

        foreach ($keys as $key) {
            if (!$recursive && preg_match("|$directory/.+/|", $key) !== 0) {
                continue;
            }

            $stream = tmpfile();
            fwrite($stream, $this->client->get($key));
            rewind($stream);
            $values[$key] = [
                'mimetype' => Util::guessMimeType(stream_get_meta_data($stream)['uri'], stream_get_contents($stream)),
                'type' => 'file'
            ];
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
        $stream = tmpfile();
        fwrite($stream, $this->client->get($path));
        rewind($stream);

        return [
            'mimetype' => Util::guessMimeType(stream_get_meta_data($stream)['uri'], stream_get_contents($stream)),
            'type' => 'file'
        ];
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
        return ['size' => $this->client->strlen($path)];
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
        $stream = tmpfile();
        fwrite($stream, $this->client->get($path));
        rewind($stream);

        return ['mimetype' => Util::guessMimeType(stream_get_meta_data($stream)['uri'], stream_get_contents($stream))];
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
        return false;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
