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
use CommerceGears\ActivityLog\Model\Config;
use CommerceGears\ActivityLog\Model\LogBuffer;

/**
 * Logs approvals/rejections made with CommerceGears Customer Approval (event commercegears_customer_approval_status_changed).
 * The status is written without a customer save, so the model observers do not see it. No-op without that module.
 */
class CustomerApprovalChange implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly LogBuffer $buffer
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isActionLogEnabled() || $this->config->isEntityTypeExcluded('customer')) {
            return;
        }
        $event = $observer->getEvent();
        $customer = $event->getData('customer');
        if (!is_object($customer) || !method_exists($customer, 'getId')) {
            return;
        }
        $changes = ['approval_status' => [(string)$event->getData('previous_status'), (string)$event->getData('status')]];
        if ((string)$event->getData('reason') !== '') {
            $changes['rejection_reason'] = ['', mb_substr((string)$event->getData('reason'), 0, 255)];
        }
        $this->buffer->add([
            'key' => 'customer|' . $customer->getId(),
            'action' => 'update',
            'entity_type' => 'customer',
            'entity_id' => (string)$customer->getId(),
            'entity_label' => method_exists($customer, 'getEmail') ? (string)$customer->getEmail() : '',
            'changes' => $changes,
        ]);
    }
}
