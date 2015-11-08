<?php

namespace spec\PatrickRose\Flysystem\Redis;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RedisAdapterSpec extends ObjectBehavior
{
    /**
     * @param \Predis\ClientInterface $redis
     */
    function let($redis)
    {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PatrickRose\Flysystem\Redis\RedisAdapter');
        $this->shouldHaveType(AdapterInterface::class);
    }
}
