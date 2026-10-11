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

    /**
     * Cloudflare's published edge ranges (https://www.cloudflare.com/ips/). CF-Connecting-IP and CF-IPCountry are
     * only trusted from these addresses when the IP source is "Cloudflare".
     */
    public const CLOUDFLARE_RANGES = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    public function __construct(
        private readonly AuthSession $authSession,
        private readonly RemoteAddress $remoteAddress,
        private readonly RequestInterface $request,
        private readonly Config $config,
        private readonly IpMatcher $ipMatcher
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

    /**
     * Client IP. With the IP source "Cloudflare", the CF-Connecting-IP header is used when the request comes
     * from a Cloudflare edge address; otherwise (and by default) the web server's REMOTE_ADDR.
     *
     * @param string|null $source IP source to use instead of the configured one (for checks before saving it)
     */
    public function getIp(?string $source = null): string
    {
        $remote = (string)$this->remoteAddress->getRemoteAddress();
        if (($source ?? $this->config->getIpSource()) !== Config::IP_SOURCE_CLOUDFLARE || !$this->isFromCloudflare()) {
            return $remote;
        }
        $header = trim((string)$this->request->getHeader('CF-Connecting-IP'));
        return filter_var($header, FILTER_VALIDATE_IP) ? $header : $remote;
    }

    /**
     * Whether the connection comes straight from a Cloudflare edge server.
     */
    public function isFromCloudflare(): bool
    {
        if (!$this->request instanceof HttpRequest) {
            return false;
        }
        $remote = (string)$this->remoteAddress->getRemoteAddress();
        return $remote !== '' && $this->ipMatcher->matches($remote, self::CLOUDFLARE_RANGES);
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
        if ($this->config->getIpSource() === Config::IP_SOURCE_CLOUDFLARE && !$this->isFromCloudflare()) {
            // The request did not pass through Cloudflare, so the header could have been set by the client.
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
            return self::describeCommandLine((array)($_SERVER['argv'] ?? []));
        }
        return '';
    }

    /**
     * "cli: <command>" for bin/magento (only the command name: arguments can contain secrets, such as
     * config:set values), "cli: script <file name>" for other PHP scripts, "cli" when nothing is known.
     *
     * @param array<int, mixed> $argv
     */
    public static function describeCommandLine(array $argv): string
    {
        $script = basename((string)($argv[0] ?? ''));
        if ($script === 'magento' || $script === 'magento.php') {
            foreach (array_slice($argv, 1) as $arg) {
                $arg = (string)$arg;
                if ($arg === '' || $arg[0] === '-') {
                    continue;
                }
                return preg_match('/^[a-z0-9:_-]+$/i', $arg) ? 'cli: ' . $arg : 'cli';
            }
            return 'cli';
        }
        $script = (string)preg_replace('/[^\w.-]/', '', $script);
        return $script !== '' ? 'cli: script ' . mb_substr($script, 0, 200) : 'cli';
    }

    /**
     * Whether a stored request path belongs to a command-line run (bin/magento, a PHP script or cron).
     */
    public static function isCommandLine(string $requestPath): bool
    {
        return $requestPath === 'cli' || str_starts_with($requestPath, 'cli:');
    }

    public function getRequestId(): string
    {
        if ($this->requestId === null) {
            $this->requestId = bin2hex(random_bytes(8));
        }
        return $this->requestId;
    }
}
