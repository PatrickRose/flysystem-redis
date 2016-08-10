# flysystem-redis

[![Build Status](https://travis-ci.org/PatrickRose/flysystem-redis.svg?branch=master)](https://travis-ci.org/PatrickRose/flysystem-redis)
[![Latest Stable Version](https://poser.pugx.org/patrickrose/flysystem-redis/v/stable)](https://packagist.org/packages/patrickrose/flysystem-redis)
[![Total Downloads](https://poser.pugx.org/patrickrose/flysystem-redis/downloads)](https://packagist.org/packages/patrickrose/flysystem-redis)
[![Latest Unstable Version](https://poser.pugx.org/patrickrose/flysystem-redis/v/unstable)](https://packagist.org/packages/patrickrose/flysystem-redis)
[![License](https://poser.pugx.org/patrickrose/flysystem-redis/license)](https://packagist.org/packages/patrickrose/flysystem-redis)

A flysystem adapter for Redis

# Installation

With composer of course:

```json
"require": {
    "patrickrose/flysystem-redis": "~1"
}
```
# Usage

```php
$client = new Predis\Client();
$adapter = new PatrickRose\Flysystem\Redis\RedisAdapter($client);

$filesystem = new League\Flysystem\Filesystem($adapter);
```

# Config options

`update`, `updateStream`, `write`, `writeStream` accept 3 config options:

| Config option  | Description                                                                                 | Valid values                                                              |
|----------------|---------------------------------------------------------------------------------------------|---------------------------------------------------------------------------|
| expirationType | The expiration resolution to use (either EX or PX). Defaults to null                        | `RedisAdapter::EXPIRE_IN_SECONDS`, `RedisAdapter::EXPIRE_IN_MILLISECONDS` |
| ttl            | How long this key should live for. Defaults to forever. If set, expirationType is set to EX | Any integer                                                               |
| setFlag        | How the key should be set (NX or XX)                                                        | `RedisAdapter::SET_IF_KEY_EXISTS`, `RedisAdapter::SET_IF_KEY_NOT_EXISTS`  |

