<?php
declare(strict_types=1);
namespace App\Application\Command\Author;

class CreateAuthorCommand
{
    public function __construct(
        public readonly string $name
    ) {
    }
}
