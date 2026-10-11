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
use Magento\Framework\Model\AbstractModel;
use CommerceGears\ActivityLog\Model\ChangeTracker;

/**
 * Admin area only (etc/adminhtml/events.xml). Cheap: a class lookup, the diff is built only for tracked entities.
 */
class ModelCommitAfter implements ObserverInterface
{
    public function __construct(
        private readonly ChangeTracker $tracker
    ) {
    }

    public function execute(Observer $observer): void
    {
        $object = $observer->getEvent()->getData('object');
        if ($object instanceof AbstractModel) {
            $this->tracker->afterCommit($object);
        }
    }
}
