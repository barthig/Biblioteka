<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Trait for adding HATEOAS links to DTOs.
 *
 * HATEOAS (Hypermedia as the Engine of Application State) allows
 * clients to discover available actions through hypermedia links.
 */
trait HateoasTrait
{
    private array $links = [];

    /**
     * Add a hypermedia link to the DTO.
     *
     * @param string $rel The relation type (self, related, collection, etc.)
     * @param string $href The URL for this link
     * @param string|null $method Optional HTTP method (GET, POST, PUT, DELETE)
     */
    public function addLink(string $rel, string $href, ?string $method = null): void
    {
        $link = ['href' => $href];
        
        if ($method) {
            $link['method'] = $method;
        }
        
        $this->links[$rel] = $link;
    }

    /**
     * Get all hypermedia links.
     *
     * @return array<string, array<string, string>>
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Check if a link exists.
     */
    public function hasLink(string $rel): bool
    {
        return isset($this->links[$rel]);
    }
}
