<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Who, from where and through which admin action the current request runs.
 */
class RequestContext
{
    private ?string $requestId = null;

    public function __construct(
        private readonly AuthSession $authSession,
        private readonly RemoteAddress $remoteAddress,
        private readonly RequestInterface $request
    ) {
    }

    public function getUserId(): ?int
    {
        $user = $this->authSession->getUser();
        return $user && $user->getId() ? (int)$user->getId() : null;
    }

    public function getUsername(): ?string
    {
        $user = $this->authSession->getUser();
        return $user && $user->getId() ? (string)$user->getUserName() : null;
    }

    public function getIp(): string
    {
        return (string)$this->remoteAddress->getRemoteAddress();
    }

    public function getUserAgent(): string
    {
        $agent = $this->request instanceof HttpRequest ? (string)$this->request->getServer('HTTP_USER_AGENT') : '';
        return mb_substr($agent, 0, 255);
    }

    /**
     * Two-letter country code from Cloudflare's CF-IPCountry header, if the store runs behind Cloudflare.
     */
    public function getCountry(): ?string
    {
        if (!$this->request instanceof HttpRequest) {
            return null;
        }
        $country = strtoupper(trim((string)$this->request->getHeader('CF-IPCountry')));
        return preg_match('/^[A-Z]{2}$/', $country) && $country !== 'XX' ? $country : null;
    }

    public function getActionPath(): string
    {
        if (PHP_SAPI !== 'cli' && $this->request instanceof HttpRequest) {
            return mb_substr((string)$this->request->getFullActionName('/'), 0, 255);
        }
        if (PHP_SAPI === 'cli') {
            // Only the command name: arguments can contain secrets (config:set values).
            $command = (string)($_SERVER['argv'][1] ?? '');
            return 'cli' . ($command !== '' && preg_match('/^[a-z0-9:_-]+$/i', $command) ? ': ' . $command : '');
        }
        return '';
    }

    public function getRequestId(): string
    {
        if ($this->requestId === null) {
            $this->requestId = bin2hex(random_bytes(8));
        }
        return $this->requestId;
    }
}
