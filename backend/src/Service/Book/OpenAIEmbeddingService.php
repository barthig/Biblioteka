<?php
declare(strict_types=1);
namespace App\Service\Book;

use App\Exception\ExternalServiceException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIEmbeddingService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(OPENAI_API_KEY)%')] private readonly string $apiKey
    ) {}

    /**
     * @return float[]
     */
    public function getVector(string $text): array
    {
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

