<?php
namespace App\Application\Command\Author;

class CreateAuthorCommand
{
    public function __construct(
        public readonly string $name
    ) {
    }
}
