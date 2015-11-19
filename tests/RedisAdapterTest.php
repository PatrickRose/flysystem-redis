<?php

namespace PatrickRose\Flysystem\Redis;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class RedisAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function getClientInterface(array $methods)
    {
        $mock = $this->getMockBuilder('Predis\ClientInterface')
            ->setMethods(array_keys($methods))
            ->getMockForAbstractClass();

        foreach ($methods as $method => $matchers) {
            if (!array_key_exists('expects', $matchers)) {
                continue;
            }

            call_user_func_array(
                [
                    $mock->expects($matchers['expects'])->method($method),
                    'with',
                ],
                $matchers['with']
            )->willReturn($matchers['willReturn']);
        }

        return $mock;
    }

    public function testItAllowsYouToWriteToTheFile()
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects'    => $this->once(),
                'with'       => [$this->equalTo('foo'), $this->equalTo('bar')],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);
        $this->assertEquals(
            [
                'path'     => 'foo',
                'contents' => 'bar',
            ],
            $adapter->write('foo', 'bar', new Config())
        );
    }

    public function testItReturnsFalseIfItDidntWrite()
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects'    => $this->once(),
                'with'       => [$this->equalTo('foo'), $this->equalTo('bar')],
                'willReturn' => false,
            ],
        ]);

        $adapter = new RedisAdapter($client);
        $this->assertEquals(
            false,
            $adapter->write('foo', 'bar', new Config())
        );
    }

    public function testItCanWriteAStream()
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects'    => $this->once(),
                'with'       => [$this->equalTo('foo'), $this->equalTo('bar')],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);
        $stream = tmpfile();
        fwrite($stream, 'bar');

        $this->assertEquals(
            [
                'path'     => 'foo',
                'contents' => 'bar',
            ],
            $adapter->writeStream('foo', $stream, new Config())
        );
    }

    public function testItAllowsYouToUpdateAFile()
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects'    => $this->once(),
                'with'       => [$this->equalTo('foo'), $this->equalTo('bar')],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);
        $this->assertEquals(
            [
                'path'     => 'foo',
                'contents' => 'bar',
            ],
            $adapter->update('foo', 'bar', new Config())
        );
    }

    public function testItReturnsFalseIfItDidntUpdate()
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects'    => $this->once(),
                'with'       => [$this->equalTo('foo'), $this->equalTo('bar')],
                'willReturn' => false,
            ],
        ]);

        $adapter = new RedisAdapter($client);
        $this->assertEquals(
            false,
            $adapter->update('foo', 'bar', new Config())
        );
    }

    public function testItCanUpdateAStream()
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects'    => $this->once(),
                'with'       => [$this->equalTo('foo'), $this->equalTo('bar')],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);
        $stream = tmpfile();
        fwrite($stream, 'bar');

        $this->assertEquals(
            [
                'path'     => 'foo',
                'contents' => 'bar',
            ],
            $adapter->updateStream('foo', $stream, new Config())
        );
    }

    public function visibilities()
    {
        return [
            'Public'  => [AdapterInterface::VISIBILITY_PUBLIC],
            'Private' => [AdapterInterface::VISIBILITY_PRIVATE],
        ];
    }

    /**
     * @dataProvider visibilities
     * @expectedException LogicException
     *
     * @param string $visibility The visibility
     */
    public function testItDoesntSupportSettingVisibility($visibility)
    {
        $adapter = new RedisAdapter($this->getClientInterface([]));
        $adapter->setVisibility('foo', $visibility);
    }

    /**
     * @expectedException LogicException
     */
    public function testItDoesntSupportGettingVisibility()
    {
        $adapter = new RedisAdapter($this->getClientInterface([]));
        $adapter->getVisibility('foo');
    }

    public function testItAllowsUsToRenameAFile()
    {
        $client = $this->getClientInterface([
            'rename' => [
                'expects'    => $this->once(),
                'with'       => ['foo', 'bar'],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertTrue($adapter->rename('foo', 'bar'));
    }

    public function testItReturnsCorrectlyIfTheRenameFailed()
    {
        $client = $this->getClientInterface([
            'rename' => [
                'expects'    => $this->once(),
                'with'       => ['foo', 'bar'],
                'willReturn' => false,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertFalse($adapter->rename('foo', 'bar'));
    }

    public function testItCanCopyAFile()
    {
        $client = $this->getClientInterface([
            'get' => [
                'expects'    => $this->once(),
                'with'       => ['foo'],
                'willReturn' => 'baz',
            ],
            'set' => [
                'expects'    => $this->once(),
                'with'       => ['bar', 'baz'],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertTrue($adapter->copy('foo', 'bar'));
    }

    public function testItReturnsFalseIfTheGetFailedDuringACopy()
    {
        $client = $this->getClientInterface([
            'get' => [
                'expects'    => $this->once(),
                'with'       => ['foo'],
                'willReturn' => false,
            ],
            'set' => [
                'expects'    => $this->never(),
                'with'       => ['bar', false],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertFalse($adapter->copy('foo', 'bar'));
    }

    public function testItReturnsFalseIfTheSetFailedDuringACopy()
    {
        $client = $this->getClientInterface([
            'get' => [
                'expects'    => $this->once(),
                'with'       => ['foo'],
                'willReturn' => 'baz',
            ],
            'set' => [
                'expects'    => $this->once(),
                'with'       => ['bar', 'baz'],
                'willReturn' => false,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertFalse($adapter->copy('foo', 'bar'));
    }

    public function testItLetsYouDeleteAFile()
    {
        $client = $this->getClientInterface([
            'del' => [
                'expects'    => $this->once(),
                'with'       => [['foo']],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertTrue($adapter->delete('foo'));
    }

    public function testItFailsCorrectlyIfYouCouldntDeleteAFile()
    {
        $client = $this->getClientInterface([
            'del' => [
                'expects'    => $this->once(),
                'with'       => [['foo']],
                'willReturn' => false,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertFalse($adapter->delete('foo'));
    }

    public function testItCanDeleteADirectory()
    {
        $client = $this->getClientInterface([
            'keys' => [
                'expects'    => $this->once(),
                'with'       => ['foo/*'],
                'willReturn' => ['foo/bar', 'foo/baz', 'foo/foo/bar'],
            ],
            'del' => [
                'expects'    => $this->once(),
                'with'       => [['foo/bar', 'foo/baz', 'foo/foo/bar']],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertTrue($adapter->deleteDir('foo'));
    }

    public function testItCanCreateADirectory()
    {
        $client = $this->getClientInterface([]);

        $adapter = new RedisAdapter($client);

        $this->assertEquals(['path' => 'foo'], $adapter->createDir('foo', new Config()));
    }

    public function trueFalse()
    {
        return [
            'true'  => [true],
            'false' => [false],
        ];
    }

    /**
     * @dataProvider trueFalse
     */
    public function testItCanCheckIfAKeyExists($keyExists)
    {
        $client = $this->getClientInterface([
            'exists' => [
                'expects'    => $this->once(),
                'with'       => ['foo'],
                'willReturn' => $keyExists ? 1 : 0,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertSame($keyExists, $adapter->has('foo'));
    }

    public function testItCanReadAKeyvalue()
    {
        $client = $this->getClientInterface([
            'get' => [
                'expects'    => $this->once(),
                'with'       => ['foo'],
                'willReturn' => 'key contents',
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $this->assertEquals(['contents' => 'key contents'], $adapter->read('foo'));
    }

    public function testItCanReadAsAStream()
    {
        $client = $this->getClientInterface([
            'get' => [
                'expects'    => $this->once(),
                'with'       => ['foo'],
                'willReturn' => 'key contents',
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $readStream = $adapter->readStream('foo');
        $this->assertArrayHasKey('stream', $readStream);
        $this->assertInternalType('resource', $readStream['stream']);
        $this->assertEquals('key contents', stream_get_contents($readStream['stream']));
    }

    public function testItCanListTheContentsOfADirectory()
    {
        $client = $this->getClientInterface([
            'keys' => [
                'expects'    => $this->once(),
                'with'       => ['foo/*'],
                'willReturn' => ['foo/bar', 'foo/baz', 'foo/far/faz'],
            ],
            'get' => [],
        ]);

        $client->expects($this->exactly(2))->method('get')
            ->withConsecutive(
                ['foo/bar'],
                ['foo/baz']
            )
            ->willReturnOnConsecutiveCalls(
                'foo/bar',
                'foo/baz'
            );

        $adapter = new RedisAdapter($client);

        $this->assertEquals([
            'foo/bar' => [
                'mimetype' => 'text/plain',
                'type'     => 'file',
            ],
            'foo/baz' => [
                'mimetype' => 'text/plain',
                'type'     => 'file',
            ],
        ], $adapter->listContents('foo'));
    }

    public function testItCanListTheContentsOfADirectoryRecursively()
    {
        $client = $this->getClientInterface([
            'keys' => [
                'expects'    => $this->once(),
                'with'       => ['foo/*'],
                'willReturn' => ['foo/bar', 'foo/baz', 'foo/far/faz'],
            ],
            'get' => [],
        ]);

        $client->expects($this->exactly(3))->method('get')
            ->withConsecutive(
                ['foo/bar'],
                ['foo/baz'],
                ['foo/far/faz']
            )
            ->willReturnOnConsecutiveCalls(
                'foo/bar',
                'foo/baz',
                'foo/far/faz'
            );

        $adapter = new RedisAdapter($client);

        $this->assertEquals(
            [
                'foo/bar' => [
                    'mimetype' => 'text/plain',
                    'type'     => 'file',
                ],
                'foo/baz' => [
                    'mimetype' => 'text/plain',
                    'type'     => 'file',
                ],
                'foo/far/faz' => [
                    'mimetype' => 'text/plain',
                    'type'     => 'file',
                ],
            ],
            $adapter->listContents('foo', true)
        );
    }

    public function testItReturnsCorrectlyForMetadata()
    {
        $client = $this->getClientInterface(['get' => []]);

        $client->expects($this->exactly(2))->method('get')
            ->withConsecutive(
                ['foo'],
                ['bar']
            )
            ->willReturnOnConsecutiveCalls(
                'foo/bar',
                'foo/baz'
            );
        $adapter = new RedisAdapter($client);

        $this->assertEquals(['mimetype' => 'text/plain', 'type' => 'file'], $adapter->getMetadata('foo'));
        $this->assertEquals(['mimetype' => 'text/plain', 'type' => 'file'], $adapter->getMetadata('bar'));
    }

    public function testItReturnsCorrectlyForSize()
    {
        $client = $this->getClientInterface(['get' => [], 'strlen' => []]);

        $client->expects($this->exactly(2))->method('strlen')
            ->withConsecutive(
                ['foo'],
                ['bar']
            )
            ->willReturnOnConsecutiveCalls(
                7,
                7
            );

        $adapter = new RedisAdapter($client);

        $this->assertEquals(['size' => 7], $adapter->getSize('foo'));
        $this->assertEquals(['size' => 7], $adapter->getSize('bar'));
    }

    public function testItReturnsTheMimetype()
    {
        $client = $this->getClientInterface(['get' => []]);

        $client->expects($this->exactly(2))->method('get')
            ->withConsecutive(
                ['foo'],
                ['bar']
            )
            ->willReturnOnConsecutiveCalls(
                'foo/bar',
                'foo/baz'
            );
        $adapter = new RedisAdapter($client);

        $this->assertEquals(['mimetype' => 'text/plain'], $adapter->getMimetype('foo'));
        $this->assertEquals(['mimetype' => 'text/plain'], $adapter->getMimetype('bar'));
    }

    public function testItReturnsNothingForTheTimestamp()
    {
        $adapter = new RedisAdapter($this->getClientInterface([]));

        $this->assertEquals(false, $adapter->getTimestamp('foo'));
        $this->assertEquals(false, $adapter->getTimestamp('bar'));
    }

    public function expirationConfigOptions()
    {
        return [
            'Expire in seconds' => [
                RedisAdapter::EXPIRE_IN_SECONDS,
            ],
            'Expire in milliseconds' => [
                RedisAdapter::EXPIRE_IN_MILLISECONDS,
            ],
        ];
    }

    /**
     * @dataProvider expirationConfigOptions
     */
    public function testItCanSetTheExpirationValue($expirationType)
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects' => $this->once(),
                'with'    => [
                    'foo', 'bar', $expirationType, 100,
                ],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $config = new Config(['expirationType' => $expirationType, 'ttl' => 100]);

        $this->assertNotFalse($adapter->write('foo', 'bar', $config));
    }

    public function testIfTheTTLIsSetTheExpirationTypeIsDefaultedToSeconds()
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects' => $this->once(),
                'with'    => [
                    'foo', 'bar', RedisAdapter::EXPIRE_IN_SECONDS, 100,
                ],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $config = new Config(['ttl' => 100]);

        $this->assertNotFalse($adapter->write('foo', 'bar', $config));
    }

    public function flagsToSet()
    {
        return [
            'set if key exists'     => [RedisAdapter::SET_IF_KEY_EXISTS],
            'set if key not exists' => [RedisAdapter::SET_IF_KEY_NOT_EXISTS],
        ];
    }

    /**
     * @dataProvider flagsToSet
     */
    public function testCanSetTheFlagForSettingTheKey($sFlag)
    {
        $client = $this->getClientInterface([
            'set' => [
                'expects' => $this->once(),
                'with'    => [
                    'foo', 'bar', $sFlag,
                ],
                'willReturn' => true,
            ],
        ]);

        $adapter = new RedisAdapter($client);

        $config = new Config(['setFlag' => $sFlag]);

        $this->assertNotFalse($adapter->write('foo', 'bar', $config));
    }
}
