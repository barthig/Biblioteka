<?php

namespace App\Dto;

/**
 * Unified API response DTO
 * Wraps both success and error responses in a consistent envelope
 */
class ApiResponse
{
    /**
     * Response data (for success responses)
     * @var mixed
     */
    public mixed $data = null;

    /**
     * Error information (for error responses)
     */
    public ?ApiError $error = null;

    /**
     * Optional metadata
     * @var array
     */
    public array $meta = [];

    public function __construct(
        mixed $data = null,
        ?ApiError $error = null,
        array $meta = []
    ) {
        $this->data = $data;
        $this->error = $error;
        $this->meta = $meta;
    }

    /**
     * Success response constructor
     */
    public static function success(mixed $data = null, array $meta = []): self
    {
        return new self(data: $data, error: null, meta: $meta);
    }

    /**
     * Error response constructor
     */
    public static function error(ApiError $error, array $meta = []): self
    {
        return new self(data: null, error: $error, meta: $meta);
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        $result = [];

        if ($this->error !== null) {
            $result['error'] = $this->error->toArray();
        } else {
            if ($this->data !== null) {
                $result['data'] = $this->data;
            }
        }

        if (!empty($this->meta)) {
            $result['meta'] = $this->meta;
        }

        return $result;
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->error?->statusCode ?? 200;
    }
}
