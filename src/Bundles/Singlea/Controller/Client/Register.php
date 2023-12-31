<?php
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

namespace SingleA\Bundles\Singlea\Controller\Client;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\Service\Client\RegistrationServiceInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

#[AsController]
final class Register
{
    public function __invoke(
        Request $request,
        RegistrationServiceInterface $registrationService,
        ?LoggerInterface $logger = null,
    ): JsonResponse {
        try {
            $registrationResult = $registrationService->register($request->toArray());
            $clientId = $registrationResult->getClientId();
            $logger?->info('Client registered.', [
                'client_id' => $clientId,
                'client_id_base58' => $clientId->toBase58(),
            ]);

            $data = [
                'client' => [
                    'id' => $clientId->toBase58(),
                    'secret' => $registrationResult->getSecret(),
                ],
            ];
            $data += $registrationResult->getOutput();

            return new JsonResponse($data);
        } catch (JsonException $exception) {
            $logger?->warning('Client registration JSON error: '.$exception->getMessage(), [$request->getContent()]);

            throw new BadRequestHttpException('Invalid JSON.');
        } catch (\DomainException $exception) {
            $logger?->error('Client cannot be registered due to invalid registration data.');

            throw new BadRequestHttpException($exception->getMessage());
        } catch (\Throwable $exception) {
            $logger?->critical('Client cannot be registered due to unexpected error.');

            throw new ServiceUnavailableHttpException(message: $exception->getMessage());
        }
    }
}
