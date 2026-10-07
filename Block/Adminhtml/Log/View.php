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
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Locale\ResolverInterface;
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

    /** Number of related entries listed on the page. */
    public const RELATED_LIMIT = 100;

    /** Actions whose entries hold old and new values; the others hold a single value per field. */
    private const DIFF_ACTIONS = ['update', 'move'];

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
        private readonly ResolverInterface $localeResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $toolbar = $this->getLayout()->getBlock('page.actions.toolbar');
        if ($toolbar) {
            $toolbar->addChild('softaware_activitylog_back', Button::class, [
                'label' => __('Back'),
                'class' => 'back',
                'on_click' => sprintf("setLocation('%s')", $this->_escaper->escapeJs($this->getBackUrl())),
            ]);
        }
        return parent::_prepareLayout();
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

    /**
     * Whether the entry is shown as old value / new value pairs (updates and moves) or as a list of values
     * (created records, mass action parameters, import results).
     *
     * @param array<int, array<string, mixed>> $changes
     */
    public function isDiff(Log $entry, array $changes): bool
    {
        if (in_array((string)$entry->getData('action'), self::DIFF_ACTIONS, true)) {
            return true;
        }
        foreach ($changes as $change) {
            if ((string)($change['old'] ?? '') !== '') {
                return true;
            }
        }
        return false;
    }

    public function getChangesTitle(Log $entry, bool $isDiff): string
    {
        if ($isDiff) {
            return (string)__('Changes');
        }
        return (string)match ((string)$entry->getData('action')) {
            'create' => __('Saved Values'),
            'mass_action' => __('Mass Action Parameters'),
            'import' => __('Import Result'),
            default => __('Values'),
        };
    }

    public function formatNumber(int $number): string
    {
        $formatter = new \NumberFormatter($this->localeResolver->getLocale(), \NumberFormatter::DECIMAL);
        return (string)$formatter->format($number);
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
            ->setPageSize(self::RELATED_LIMIT);
        return array_values($collection->getItems());
    }

    public function getRelatedCount(Log $entry): int
    {
        if (!$entry->getData('request_id')) {
            return 0;
        }
        return $this->collectionFactory->create()
            ->addFieldToFilter('request_id', $entry->getData('request_id'))
            ->addFieldToFilter('log_id', ['neq' => $entry->getId()])
            ->getSize();
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
