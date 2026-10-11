<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Plugin;

use Magento\Catalog\Model\Product\Action;
use CommerceGears\ActivityLog\Model\Config;
use CommerceGears\ActivityLog\Model\DiffBuilder;
use CommerceGears\ActivityLog\Model\LogBuffer;
use CommerceGears\ActivityLog\Model\Source\Action as ActionSource;

/**
 * Product grid "Update status" and similar bulk attribute updates bypass model saves; log the new values here.
 */
class ProductActionPlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly LogBuffer $buffer,
        private readonly DiffBuilder $diffBuilder
    ) {
    }

    /**
     * @param mixed[] $productIds
     * @param array<string, mixed> $attrData
     */
    public function afterUpdateAttributes(
        Action $subject,
        mixed $result,
        $productIds,
        $attrData,
        $storeId
    ): mixed {
        if (!$this->config->isActionLogEnabled() || $this->config->isEntityTypeExcluded('product')) {
            return $result;
        }
        $changes = [];
        foreach ((array)$attrData as $code => $value) {
            $normalized = $this->diffBuilder->normalize($value);
            if ($normalized !== null) {
                $changes[(string)$code] = $this->diffBuilder->protect((string)$code, '', $normalized);
            }
        }
        $ids = array_map('strval', (array)$productIds);
        $changes['product_ids'] = ['', implode(', ', array_slice($ids, 0, 500))];
        $this->buffer->add([
            'key' => 'product_attributes|' . spl_object_id($subject) . '|' . md5(implode(',', $ids)),
            'action' => ActionSource::MASS_UPDATE,
            'entity_type' => 'product',
            'entity_id' => count($ids) === 1 ? $ids[0] : null,
            'entity_label' => (string)__('Bulk attribute update of %1 product(s)', count($ids)),
            'scope' => (int)$storeId === 0 ? 'default' : 'store:' . (int)$storeId,
            'changes' => $changes,
        ]);
        return $result;
    }
}
