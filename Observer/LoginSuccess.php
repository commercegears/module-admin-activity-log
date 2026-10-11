<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\User\Model\User;
use CommerceGears\ActivityLog\Model\LoginLogger;

class LoginSuccess implements ObserverInterface
{
    public function __construct(
        private readonly LoginLogger $loginLogger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $user = $observer->getEvent()->getData('user');
        if ($user instanceof User) {
            $this->loginLogger->logSuccess($user);
        }
    }
}
