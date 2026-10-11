<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use CommerceGears\ActivityLog\Model\LoginLogger;

class LoginFailed implements ObserverInterface
{
    public function __construct(
        private readonly LoginLogger $loginLogger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $exception = $event->getData('exception');
        $this->loginLogger->logFailure(
            (string)$event->getData('user_name'),
            $exception instanceof \Throwable ? $exception : null
        );
    }
}
