<?php declare(strict_types=1);
// SPDX-License-Identifier: BSD-3-Clause

namespace SingleA\Bundles\Singlea\Security;

use Psr\Log\LoggerInterface;
use SingleA\Bundles\Singlea\EventListener\ClientListener;
use SingleA\Bundles\Singlea\EventListener\TicketListener;
use SingleA\Bundles\Singlea\FeatureConfig\ConfigRetrieverInterface;
use SingleA\Bundles\Singlea\FeatureConfig\Signature\SignatureConfigInterface;
use SingleA\Bundles\Singlea\Service\Client\RegistrationTicketManagerInterface;
use SingleA\Bundles\Singlea\Service\Signature\SignatureServiceInterface;
use SingleA\Bundles\Singlea\Utility\StringUtility;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class GrantedVoter extends Voter
{
    public const SINGLEA_SIGNATURE = 'SINGLEA_SIGNATURE';
    public const SINGLEA_TICKET = 'SINGLEA_TICKET';
    public const CLIENT_IP = 'CLIENT_IP';
    public const REGISTRATION_IP = 'REGISTRATION_IP';
    public const REGISTRATION_TICKET = 'REGISTRATION_TICKET';

    private const ALL_ONES_NETWORK_MASK_V4 = 32;
    private const ALL_ONES_NETWORK_MASK_V6 = 128;

    private ?array $trustedClients = null;
    private ?array $trustedRegistrars = null;

    public function __construct(
        ?string $trustedClients,
        ?string $trustedRegistrars,
        private string $registrationTicketHeader,
        private ConfigRetrieverInterface $configRetriever,
        private SignatureServiceInterface $signatureService,
        private ?RegistrationTicketManagerInterface $registrationTicketManager = null,
        private ?LoggerInterface $logger = null,
    ) {
        $trustedClients = array_filter(array_map('trim', explode(',', $trustedClients ?? '')));
        if ($trustedClients && !\in_array('REMOTE_ADDR', $trustedClients, true)) {
            $this->trustedClients = array_map([self::class, 'validateIpOrNetmask'], $trustedClients);
        }

        $trustedRegistrars = array_filter(array_map('trim', explode(',', $trustedRegistrars ?? '')));
        if ($trustedRegistrars && !\in_array('REMOTE_ADDR', $trustedRegistrars, true)) {
            $this->trustedRegistrars = array_map([self::class, 'validateIpOrNetmask'], $trustedRegistrars);
        }
    }

    public static function validateIpOrNetmask(string $value): string
    {
        [$allOnesNetworkMask, $filterFlag] = str_contains($value, ':')
            ? [self::ALL_ONES_NETWORK_MASK_V6, \FILTER_FLAG_IPV6]
            : [self::ALL_ONES_NETWORK_MASK_V4, \FILTER_FLAG_IPV4];

        [$address, $netmask] = str_contains($value, '/')
            ? explode('/', $value, 2)
            : [$value, (string) $allOnesNetworkMask];

        if ((int) $netmask < 1 || (int) $netmask > $allOnesNetworkMask) {
            /** @psalm-suppress MixedArgument */
            throw new \InvalidArgumentException(sprintf('Invalid network mask "%s" for value "%s".', $netmask, $value));
        }

        if (filter_var($address, \FILTER_VALIDATE_IP, $filterFlag) === false) {
            throw new \InvalidArgumentException(sprintf('Invalid IP address "%s" for value "%s".', $address, $value));
        }

        return $value;
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, [
            self::SINGLEA_SIGNATURE,
            self::SINGLEA_TICKET,
            self::CLIENT_IP,
            self::REGISTRATION_IP,
            self::REGISTRATION_TICKET,
        ], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === Request::class;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->supportsAttribute($attribute)) {
            return false;
        }

        if (!($subject instanceof Request)) {
            return false;
        }

        if ($attribute === self::SINGLEA_SIGNATURE) {
            if (!$subject->attributes->has(ClientListener::CLIENT_ID_ATTRIBUTE)) {
                throw new BadRequestHttpException('Request does not contain client_id.');
            }

            if (!$subject->attributes->has(ClientListener::SECRET_ATTRIBUTE)) {
                throw new BadRequestHttpException('Request does not contain client secret.');
            }

            /** @var string $clientId */
            $clientId = $subject->attributes->get(ClientListener::CLIENT_ID_ATTRIBUTE);

            return $this->configRetriever->exists(SignatureConfigInterface::class, $clientId);
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!($subject instanceof Request)) {
            throw new \InvalidArgumentException('Subject must be an instance of '.Request::class.', "'.get_debug_type($subject).'" passed.');
        }

        return match ($attribute) {
            self::SINGLEA_SIGNATURE => $this->isValidSingleaSignature($subject),
            self::SINGLEA_TICKET => self::isValidSingleaTicket($subject),
            self::CLIENT_IP => $this->isValidClientIp($subject),
            self::REGISTRATION_IP => $this->isValidRegistrationIp($subject),
            self::REGISTRATION_TICKET => $this->isValidRegistrationTicket($subject),
            default => throw new \InvalidArgumentException('Unsupported attribute "'.$attribute.'" passed.'),
        };
    }

    private static function isValidSingleaTicket(Request $request): bool
    {
        return $request->attributes->has(TicketListener::TICKET_ATTRIBUTE);
    }

    private function isValidSingleaSignature(Request $request): bool
    {
        /** @var string $clientId */
        $clientId = $request->attributes->get(ClientListener::CLIENT_ID_ATTRIBUTE);
        /** @var string $secret */
        $secret = $request->attributes->get(ClientListener::SECRET_ATTRIBUTE);

        $config = $this->configRetriever->find(SignatureConfigInterface::class, $clientId, $secret);
        if (!($config instanceof SignatureConfigInterface)) {
            throw new \UnexpectedValueException('Client does not support request signing.');
        }

        try {
            $this->signatureService->check($request, $config);
        } catch (\Throwable $exception) {
            $this->logger?->notice('Signature check failed: '.$exception->getMessage());

            return false;
        }

        return true;
    }

    private function isValidClientIp(Request $request): bool
    {
        if ($this->trustedClients === null) {
            return true;
        }

        return IpUtils::checkIp(
            $request->getClientIp() ?? throw new \RuntimeException('Undefined client IP.'),
            $this->trustedClients,
        );
    }

    private function isValidRegistrationIp(Request $request): bool
    {
        if ($this->trustedRegistrars === null) {
            return true;
        }

        return IpUtils::checkIp(
            $request->getClientIp() ?? throw new \RuntimeException('Undefined client IP.'),
            $this->trustedRegistrars,
        );
    }

    private function isValidRegistrationTicket(Request $request): bool
    {
        if (!$this->registrationTicketManager) {
            return false;
        }

        $registrationTicket = trim((string) $request->headers->get($this->registrationTicketHeader));
        if (!$registrationTicket) {
            return false;
        }

        return $this->registrationTicketManager->isValid($registrationTicket);
    }
}
