<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Block\Adminhtml\Log;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Softaware\ActivityLog\Controller\Adminhtml\Log\View as ViewController;
use Softaware\ActivityLog\Model\EntityTypeResolver;
use Softaware\ActivityLog\Model\Log;
use Softaware\ActivityLog\Model\ResourceModel\Log\CollectionFactory;
use Softaware\ActivityLog\Model\Source\Action as ActionSource;

/**
 * Detail page of one admin action: who, when, where, and the field-level diff.
 */
class View extends Template
{
    protected $_template = 'Softaware_ActivityLog::log/view.phtml';

    /** Admin edit routes per entity type (ID parameter name in brackets). */
    private const EDIT_ROUTES = [
        'product' => ['catalog/product/edit', 'id'],
        'category' => ['catalog/category/edit', 'id'],
        'cms_page' => ['cms/page/edit', 'page_id'],
        'cms_block' => ['cms/block/edit', 'block_id'],
        'customer' => ['customer/index/edit', 'id'],
        'order' => ['sales/order/view', 'order_id'],
        'invoice' => ['sales/invoice/view', 'invoice_id'],
        'shipment' => ['adminhtml/order_shipment/view', 'shipment_id'],
        'creditmemo' => ['sales/creditmemo/view', 'creditmemo_id'],
        'cart_price_rule' => ['sales_rule/promo_quote/edit', 'id'],
        'catalog_price_rule' => ['catalog_rule/promo_catalog/edit', 'id'],
        'admin_user' => ['adminhtml/user/edit', 'user_id'],
        'admin_role' => ['adminhtml/user_role/editrole', 'rid'],
    ];

    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly EntityTypeResolver $typeResolver,
        private readonly ActionSource $actionSource,
        private readonly CollectionFactory $collectionFactory,
        private readonly TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getEntry(): ?Log
    {
        $entry = $this->registry->registry(ViewController::REGISTRY_KEY);
        return $entry instanceof Log ? $entry : null;
    }

    public function getTypeLabel(string $type): string
    {
        return $this->typeResolver->getTypeLabel($type);
    }

    public function getActionLabel(string $action): string
    {
        foreach ($this->actionSource->toOptionArray() as $option) {
            if ($option['value'] === $action) {
                return (string)$option['label'];
            }
        }
        return $action;
    }

    public function formatUtcTime(string $utc): string
    {
        return $this->timezone->formatDateTime(
            new \DateTime($utc, new \DateTimeZone('UTC')),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM
        );
    }

    public function getEntityUrl(Log $entry): ?string
    {
        $route = self::EDIT_ROUTES[(string)$entry->getData('entity_type')] ?? null;
        $id = (string)$entry->getData('entity_id');
        if ($route === null || $id === '' || !ctype_digit($id) || $entry->getData('action') === 'delete') {
            return null;
        }
        return $this->getUrl($route[0], [$route[1] => $id]);
    }

    public function getConfigUrl(Log $entry): ?string
    {
        if ($entry->getData('entity_type') !== 'config' || !$entry->getData('entity_id')) {
            return null;
        }
        return $this->getUrl('adminhtml/system_config/edit', ['section' => $entry->getData('entity_id')]);
    }

    /**
     * Other entries written by the same admin request (for example the records changed by a mass action).
     *
     * @return Log[]
     */
    public function getRelatedEntries(Log $entry): array
    {
        if (!$entry->getData('request_id')) {
            return [];
        }
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('request_id', $entry->getData('request_id'))
            ->addFieldToFilter('log_id', ['neq' => $entry->getId()])
            ->setOrder('log_id', 'ASC')
            ->setPageSize(100);
        return array_values($collection->getItems());
    }

    public function getViewUrl(int $id): string
    {
        return $this->getUrl('*/*/view', ['id' => $id]);
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/index');
    }
}
