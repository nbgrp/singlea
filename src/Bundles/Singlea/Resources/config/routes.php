<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace Symfony\Component\Routing\Loader\Configurator;

use SingleA\Bundles\Singlea\Controller;

return static function (RoutingConfigurator $routes): void {
    $routes->add('singlea_client_register', '/client/register')
        ->controller(Controller\Client\Register::class)
        ->methods(['POST'])
    ;

    $routes->add('singlea_login', '/login')
        ->controller(Controller\Feature\Login::class)
        ->methods(['GET'])
    ;

    $routes->add('singlea_logout', '/logout')
        ->controller(Controller\Feature\Logout::class)
        ->methods(['GET'])
    ;

    $routes->add('singlea_validate', '/validate')
        ->controller(Controller\Feature\Validate::class)
        ->methods(['GET'])
    ;

    $routes->add('singlea_token', '/token')
        ->controller(Controller\Feature\Token::class)
        ->methods(['GET'])
    ;
};
