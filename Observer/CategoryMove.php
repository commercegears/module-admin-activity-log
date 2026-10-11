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
use CommerceGears\ActivityLog\Model\Config;
use CommerceGears\ActivityLog\Model\LogBuffer;

/**
 * Category drag & drop in the category tree does not save the model, so the move is logged separately.
 */
class CategoryMove implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly LogBuffer $buffer
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->isActionLogEnabled() || $this->config->isEntityTypeExcluded('category')) {
            return;
        }
        $event = $observer->getEvent();
        $category = $event->getData('category');
        $this->buffer->add([
            'key' => 'category_move|' . $event->getData('category_id'),
            'action' => 'move',
            'entity_type' => 'category',
            'entity_id' => (string)$event->getData('category_id'),
            'entity_label' => $category ? (string)$category->getName() : '',
            'changes' => [
                'parent_id' => [(string)$event->getData('prev_parent_id'), (string)$event->getData('parent_id')],
            ],
        ]);
    }
}
