<?php
// app/Dto/User/UserResponseDto.php

namespace App\Dto\User;

class UserResponseDto
{
    public function __construct(
        private readonly array $data
    ) {}

    public function toArray(): array
    {
        return $this->data;
    }
}