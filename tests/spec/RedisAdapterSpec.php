<?php

namespace spec\PatrickRose\Flysystem\Redis;

use League\Flysystem\AdapterInterface;
use PhpSpec\ObjectBehavior;

class RedisAdapterSpec extends ObjectBehavior
{
    /**
     * @param \Predis\ClientInterface $redis
     */
    public function let($redis)
    {
        $this->beConstructedWith($redis);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('PatrickRose\Flysystem\Redis\RedisAdapter');
        $this->shouldHaveType(AdapterInterface::class);
    }
}
