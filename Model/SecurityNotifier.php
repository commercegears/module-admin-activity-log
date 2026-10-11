<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model;

use Magento\Backend\Model\UrlInterface as BackendUrl;
use Magento\Framework\App\Area;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Translate\Inline\StateInterface as InlineTranslation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\User;
use Psr\Log\LoggerInterface;
use CommerceGears\ActivityLog\Model\ResourceModel\Login as LoginResource;

/**
 * Security alerts: repeated failed sign-ins from one IP, account lockouts and sign-ins from a new IP or country.
 * Alerts go to the configured recipients by email and, optionally, to the admin notification inbox.
 */
class SecurityNotifier
{
    private const CACHE_PREFIX = 'commercegears_activitylog_alert_';

    public function __construct(
        private readonly Config $config,
        private readonly LoginResource $loginResource,
        private readonly CacheInterface $cache,
        private readonly NotifierInterface $notifier,
        private readonly TransportBuilder $transportBuilder,
        private readonly InlineTranslation $inlineTranslation,
        private readonly BackendUrl $backendUrl,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function checkFailures(string $ip, string $username, bool $locked): void
    {
        if (!$this->config->isNotificationEnabled()) {
            return;
        }
        if ($locked && $this->acquire('lock_' . $username, 3600)) {
            $title = (string)__('Admin account "%1" was locked after failed sign-in attempts', $username);
            $this->alert($title, (string)__('The last attempt came from IP %1.', $ip), $username, $ip, 0);
        }
        $window = $this->config->getFailedWindowMinutes();
        $since = gmdate('Y-m-d H:i:s', time() - $window * 60);
        $count = $this->loginResource->countFailuresFromIp($ip, $since);
        if ($count < $this->config->getFailedThreshold() || !$this->acquire('ip_' . $ip, $window * 60)) {
            return;
        }
        $title = (string)__('%1 failed admin sign-in attempts from IP %2', $count, $ip);
        $description = (string)__(
            '%1 failed sign-in attempts from IP %2 in the last %3 minutes. Last username tried: "%4".',
            $count,
            $ip,
            $window,
            $username
        );
        $this->alert($title, $description, $username, $ip, $count);
    }

    public function checkNewLocation(User $user, int $loginId, string $ip, ?string $country): void
    {
        if (!$this->config->isNotificationEnabled()) {
            return;
        }
        $userId = (int)$user->getId();
        if ($this->loginResource->countPreviousSuccess($userId, $loginId) === 0) {
            // First recorded sign-in: nothing to compare with yet.
            return;
        }
        $reasons = [];
        if ($this->config->isNewIpNotificationEnabled()
            && $this->loginResource->countPreviousSuccess($userId, $loginId, $ip) === 0
        ) {
            $reasons[] = (string)__('new IP address %1', $ip);
        }
        if ($country !== null
            && $this->config->isNewCountryNotificationEnabled()
            && $this->loginResource->hasCountryHistory($userId, $loginId)
            && $this->loginResource->countPreviousSuccess($userId, $loginId, null, $country) === 0
        ) {
            $reasons[] = (string)__('new country %1', $country);
        }
        if ($reasons === []) {
            return;
        }
        $username = (string)$user->getUserName();
        $title = (string)__('Admin "%1" signed in from a %2', $username, implode(' / ', $reasons));
        $description = (string)__(
            'Sign-in by %1 from IP %2%3. If this was not expected, terminate the session under '
            . 'CommerceGears > Admin Activity Log > Active Sessions and change the password.',
            $username,
            $ip,
            $country ? ' (' . $country . ')' : ''
        );
        $extra = $this->config->isNotifyUserEnabled() && $user->getEmail() ? [(string)$user->getEmail()] : [];
        if ($this->config->isAdminNoticeEnabled()) {
            $this->addNotice($title, $description, 'commercegears_activitylog/login/index');
        }
        $this->sendEmail($this->config->getNewIpTemplate(), $title, $description, $username, $ip, $extra);
    }

    private function alert(string $title, string $description, string $username, string $ip, int $count): void
    {
        if ($this->config->isAdminNoticeEnabled()) {
            $this->addNotice($title, $description, 'commercegears_activitylog/login/index');
        }
        $this->sendEmail($this->config->getFailedLoginTemplate(), $title, $description, $username, $ip, [], $count);
    }

    private function addNotice(string $title, string $description, string $route): void
    {
        try {
            $url = $this->getUrlWithoutSecretKey($route);
            $this->notifier->addMajor($title, $description, $url);
        } catch (\Throwable $e) {
            $this->logger->error('CommerceGears_ActivityLog: could not add admin notice: ' . $e->getMessage());
        }
    }

    /**
     * Admin URL without the secret key (it is only valid in the current session), for notices and emails.
     *
     * '_nosecret' switches the shared backend URL model to "no secret key" for the rest of the request, so a later
     * getUrl() without parameters (for example a redirect after sign-in) would lack the key and Magento would send
     * the admin to the dashboard. The previous state is restored here. Keys are switched off explicitly first:
     * Magento caches built URLs per route and parameters, and on a cache hit '_nosecret' alone is not applied.
     */
    private function getUrlWithoutSecretKey(string $route): string
    {
        $usedSecretKey = $this->backendUrl->useSecretKey();
        try {
            $this->backendUrl->turnOffSecretKey();
            return $this->backendUrl->getUrl($route, ['_nosecret' => true]);
        } finally {
            if ($usedSecretKey) {
                $this->backendUrl->turnOnSecretKey();
            }
        }
    }

    /**
     * @param string[] $extraRecipients
     */
    private function sendEmail(
        string $templateId,
        string $title,
        string $description,
        string $username,
        string $ip,
        array $extraRecipients = [],
        int $count = 0
    ): void {
        $recipients = array_values(array_unique(array_merge($this->config->getRecipients(), $extraRecipients)));
        if ($recipients === [] || $templateId === '') {
            return;
        }
        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => (int)$this->storeManager->getDefaultStoreView()?->getId(),
                ])
                ->setTemplateVars([
                    'title' => $title,
                    'description' => $description,
                    'username' => $username,
                    'ip' => $ip,
                    'count' => $count,
                    'time' => gmdate('Y-m-d H:i:s') . ' UTC',
                    'log_url' => $this->getUrlWithoutSecretKey('commercegears_activitylog/login/index'),
                ])
                ->setFromByScope($this->config->getSenderIdentity())
                ->addTo($recipients)
                ->getTransport();
            $transport->sendMessage();
        } catch (\Throwable $e) {
            $this->logger->error('CommerceGears_ActivityLog: could not send security email: ' . $e->getMessage());
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    /**
     * Returns true once per key and period, so alerts are not repeated for every further attempt.
     */
    private function acquire(string $key, int $ttl): bool
    {
        $cacheKey = self::CACHE_PREFIX . md5($key);
        if ($this->cache->load($cacheKey)) {
            return false;
        }
        $this->cache->save('1', $cacheKey, [], max(60, $ttl));
        return true;
    }
}
