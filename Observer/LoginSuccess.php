<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\User\Model\User;
use Softaware\ActivityLog\Model\LoginLogger;

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
