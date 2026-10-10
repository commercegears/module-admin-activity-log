<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Observer;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Softaware\ActivityLog\Model\Config;
use Softaware\ActivityLog\Model\DiffBuilder;
use Softaware\ActivityLog\Model\LogBuffer;
use Softaware\ActivityLog\Model\Source\Action;

/**
 * Logs admin grid mass actions (massDelete, massStatus, massCancel, ...) with the selection and parameters.
 * Records changed by the mass action are logged individually as well when they are saved through models.
 */
class MassActionPredispatch implements ObserverInterface
{
    /** Non-"mass*" admin actions that change many records at once. */
    private const EXTRA_ACTIONS = [
        'catalog_product_action_attribute_save' => 'product',
    ];

    /** Route prefix => entity type, longest prefix wins. */
    private const ROUTE_TYPES = [
        'catalog_product' => 'product',
        'catalog_category' => 'category',
        'cms_page' => 'cms_page',
        'cms_block' => 'cms_block',
        'customer_index' => 'customer',
        'customer_online' => 'customer',
        'sales_order_invoice' => 'invoice',
        'sales_invoice' => 'invoice',
        'sales_order_shipment' => 'shipment',
        'sales_shipment' => 'shipment',
        'sales_order_creditmemo' => 'creditmemo',
        'sales_creditmemo' => 'creditmemo',
        'sales_order' => 'order',
        'sales_rule_promo_quote' => 'cart_price_rule',
        'catalog_rule_promo_catalog' => 'catalog_price_rule',
        'adminhtml_user' => 'admin_user',
    ];

    private const SKIPPED_PARAMS = [
        'form_key', 'namespace', 'selected', 'excluded', 'filters', 'search', 'isAjax', 'key', 'filters_modifier',
        'paging', 'sorting', 'massaction_prepare_key', 'internal_ids',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly LogBuffer $buffer,
        private readonly DiffBuilder $diffBuilder
    ) {
    }

    public function execute(Observer $observer): void
    {
        $request = $observer->getEvent()->getData('request');
        if (!$request instanceof HttpRequest || !$request->isPost() || !$this->config->isActionLogEnabled()) {
            return;
        }
        $fullAction = (string)$request->getFullActionName();
        $isMass = str_starts_with(strtolower((string)$request->getActionName()), 'mass');
        if (!$isMass && !isset(self::EXTRA_ACTIONS[$fullAction])) {
            return;
        }
        $type = self::EXTRA_ACTIONS[$fullAction] ?? $this->resolveType($fullAction);
        if ($this->config->isEntityTypeExcluded($type)) {
            return;
        }
        $changes = ['selection' => ['', $this->describeSelection($request)]];
        $selected = $request->getParam('selected');
        if (is_array($selected) && $selected !== []) {
            $changes['selected_ids'] = ['', implode(', ', array_slice(array_map('strval', $selected), 0, 500))];
        }
        foreach ($request->getPostValue() as $name => $value) {
            if (in_array($name, self::SKIPPED_PARAMS, true)) {
                continue;
            }
            $normalized = $this->diffBuilder->normalize($value);
            if ($normalized === null) {
                $normalized = (string)json_encode(
                    $this->diffBuilder->maskNested($value),
                    JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
                );
            }
            if ($normalized !== '') {
                $changes[(string)$name] = $this->diffBuilder->protect((string)$name, '', $normalized);
            }
        }
        $this->buffer->add([
            'key' => 'mass|' . $fullAction,
            'action' => isset(self::EXTRA_ACTIONS[$fullAction])
                ? Action::MASS_UPDATE
                : Action::forMassAction((string)$request->getActionName()),
            'entity_type' => $type,
            'entity_id' => null,
            'entity_label' => (string)__('%1 (%2)', $request->getActionName(), $changes['selection'][1]),
            'changes' => $changes,
        ]);
    }

    private function resolveType(string $fullAction): string
    {
        $best = '';
        $type = 'other';
        foreach (self::ROUTE_TYPES as $prefix => $candidate) {
            if (str_starts_with($fullAction, $prefix . '_') && strlen($prefix) > strlen($best)) {
                $best = $prefix;
                $type = $candidate;
            }
        }
        return $type;
    }

    private function describeSelection(HttpRequest $request): string
    {
        $selected = $request->getParam('selected');
        $excluded = $request->getParam('excluded');
        if (is_array($selected)) {
            return (string)__('%1 selected', count($selected));
        }
        if ($excluded === 'false' || $excluded === false) {
            return (string)__('all records matching the grid filter');
        }
        if (is_array($excluded)) {
            return (string)__('all records matching the grid filter except %1', count($excluded));
        }
        $ids = $request->getParam('product') ?? $request->getParam('ids') ?? $request->getParam('id');
        if (is_array($ids)) {
            return (string)__('%1 selected', count($ids));
        }
        return (string)__('selection not specified');
    }
}
