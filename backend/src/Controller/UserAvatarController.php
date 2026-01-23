<?php
namespace App\Controller;

use App\Dto\ApiError;
use App\Repository\UserRepository;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'User')]
class UserAvatarController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em
    ) {
    }

    #[OA\Post(
        path: '/api/me/avatar',
        summary: 'Upload current user avatar (base64 JSON)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'filename', type: 'string', nullable: true),
                    new OA\Property(property: 'mimeType', type: 'string', nullable: true),
                    new OA\Property(property: 'content', type: 'string', description: 'Base64 file content'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 400, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function upload(Request $request, SecurityService $security): JsonResponse
    {
        $userId = $security->getCurrentUserId($request);
        if (!$userId) {
            return $this->json(ApiError::unauthorized(), 401);
        }

        $user = $this->users->find($userId);
        if (!$user) {
            return $this->json(ApiError::notFound('User not found'), 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $content = isset($data['content']) && is_string($data['content']) ? $data['content'] : null;
        $mimeType = isset($data['mimeType']) && is_string($data['mimeType']) ? trim($data['mimeType']) : 'image/jpeg';

        if ($content === null) {
            return $this->json(ApiError::badRequest('Missing content payload (base64)'));
        }

        $decoded = base64_decode($content, true);
        if ($decoded === false) {
            return $this->json(ApiError::badRequest('Invalid base64 payload'));
        }

        if (!str_starts_with(strtolower($mimeType), 'image/')) {
            return $this->json(ApiError::badRequest('Avatar must be an image'));
        }

        $ext = self::extensionFromMime($mimeType) ?? 'bin';
        $storage = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = $this->avatarDirectory();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir . DIRECTORY_SEPARATOR . $storage;
        $bytes = @file_put_contents($path, $decoded);
        if ($bytes === false || $bytes === 0) {
            return $this->json(ApiError::internalError('Failed to store avatar'));
        }

        $user->setAvatarStorageName($storage);
        $user->setAvatarMimeType($mimeType);
        $user->setAvatarUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->json([
            'avatarUrl' => '/api/users/' . $user->getId() . '/avatar',
        ]);
    }

    #[OA\Get(
        path: '/api/users/{id}/avatar',
        summary: 'Get user avatar (public)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Image stream', content: new OA\MediaType(mediaType: 'image/*')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function getAvatar(int $id): BinaryFileResponse|JsonResponse
    {
        $user = $this->users->find($id);
        if (!$user || $user->getAvatarStorageName() === null) {
            return $this->json(ApiError::notFound('Avatar not found'), 404);
        }

        $path = $this->avatarDirectory() . DIRECTORY_SEPARATOR . $user->getAvatarStorageName();
        if (!is_file($path)) {
            return $this->json(ApiError::notFound('Avatar not found'), 404);
        }

        $response = new BinaryFileResponse($path);
        $mime = $user->getAvatarMimeType() ?: 'image/jpeg';
        $response->headers->set('Content-Type', $mime);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'avatar');
        $response->setMaxAge(3600);
        $response->setPublic();
        return $response;
    }

    private function avatarDirectory(): string
    {
        return $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'digital-assets' . DIRECTORY_SEPARATOR . 'avatars';
    }

    private static function extensionFromMime(string $mime): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/svg+xml' => 'svg',
        ];
        $key = strtolower(trim($mime));
        return $map[$key] ?? null;
    }
}
