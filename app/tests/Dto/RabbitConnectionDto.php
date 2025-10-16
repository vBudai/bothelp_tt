<?php

namespace App\Tests\Dto;

readonly class RabbitConnectionDto
{
    public function __construct(
        public string $scheme,
        public string $host,
        public int $port,
        public string $user,
        public string $password,
        public string $path,
    ){
    }
}
