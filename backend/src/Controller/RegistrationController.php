<?php
namespace App\Controller;

use App\Controller\Traits\ValidationTrait;
use App\Request\RegistrationRequest;
use App\Service\RegistrationException;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    use ValidationTrait;

    public function __construct(
        private RateLimiterFactory $registrationAttemptsLimiter,
    ) {
    }

    public function register(Request $request, RegistrationService $registrationService, ValidatorInterface $validator): JsonResponse
    {
        // Rate limiting - max 3 rejestracje na godzinę z tego samego IP
        $limiter = $this->registrationAttemptsLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Zbyt wiele prób rejestracji. Spróbuj ponownie później.'], 429);
        }
        
        $data = json_decode($request->getContent(), true) ?: [];

        $dto = $this->mapArrayToDto($data, new RegistrationRequest());
        $errors = $validator->validate($dto);
        
        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        // Konwertuj z powrotem do tablicy dla RegistrationService
        $payload = [
            'email' => $dto->email,
            'name' => $dto->name,
            'password' => $dto->password,
            'phoneNumber' => $dto->phoneNumber,
            'addressLine' => $dto->addressLine,
            'city' => $dto->city,
            'postalCode' => $dto->postalCode,
            'membershipGroup' => $dto->membershipGroup,
            'privacyConsent' => $dto->privacyConsent,
        ];

        // newsletterSubscribed powinien być przekazany tylko jeśli został jawnie ustawiony
        if (array_key_exists('newsletterSubscribed', $data)) {
            $payload['newsletterSubscribed'] = $dto->newsletterSubscribed;
        }

        try {
            $token = $registrationService->register($payload);
        } catch (RegistrationException $exception) {
            $status = $exception->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 400;
            }

            return $this->json(['error' => $exception->getMessage()], $status);
        } catch (\Throwable $error) {
            return $this->json(['error' => 'Nie udało się utworzyć konta.'], 500);
        }

        return $this->json([
            'status' => 'pending_verification',
            'userId' => $token->getUser()->getId(),
            'verificationToken' => $token->getToken(),
        ], 201);
    }

    public function verify(string $token, RegistrationService $registrationService): JsonResponse
    {
        try {
            $user = $registrationService->verify($token);
        } catch (RegistrationException $exception) {
            $status = $exception->getCode();
            if ($status < 400 || $status >= 600) {
                $status = 400;
            }

            return $this->json(['error' => $exception->getMessage()], $status);
        } catch (\Throwable $error) {
            return $this->json(['error' => 'Nie udało się zweryfikować konta.'], 500);
        }

        $response = [
            'status' => 'account_verified',
            'userId' => $user->getId(),
            'pendingApproval' => $user->isPendingApproval(),
        ];

        return $this->json($response, 200);
    }
}
