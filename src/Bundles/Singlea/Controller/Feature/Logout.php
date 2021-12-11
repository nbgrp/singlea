<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Controller\Feature;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

#[AsController]
final class Logout
{
    public function __invoke(): Response
    {
        throw new ServiceUnavailableHttpException(message: 'Invalid security settings.');
    }
}
