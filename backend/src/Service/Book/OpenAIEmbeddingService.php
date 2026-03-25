<?php
declare(strict_types=1);
namespace App\Service\Book;

use App\Exception\ExternalServiceException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIEmbeddingService
{
    private const PLACEHOLDER_KEYS = [
        '',
        'change_me_openai_key',
        'sk-your-openai-key-here',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(OPENAI_API_KEY)%')] private readonly string $apiKey
    ) {}

    public function isConfigured(): bool
    {
        return !in_array(trim($this->apiKey), self::PLACEHOLDER_KEYS, true);
    }

    /**
     * @return float[]
     */
    public function getVector(string $text): array
    {
        if (!$this->isConfigured()) {
            throw new ExternalServiceException('OpenAI API key is not configured.');
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ],
        ]);

        $data = $response->toArray(false);
        if (!isset($data['data'][0]['embedding']) || !is_array($data['data'][0]['embedding'])) {
            throw new ExternalServiceException('OpenAI embedding response is missing embedding data.');
        }

        return array_map('floatval', $data['data'][0]['embedding']);
    }
}
