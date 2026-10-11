<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;

/**
 * Returns the stored ("before") data of a model that is about to be saved.
 *
 * Most admin controllers load the entity before changing it, so getOrigData() already holds the old values and
 * nothing is queried. Repositories that build a fresh model from a data object (customers, for example) leave
 * getOrigData() empty; for those a fresh copy is loaded once. Acts as a small factory, hence the object manager.
 */
class SnapshotLoader
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOriginalData(AbstractModel $object): array
    {
        $orig = $object->getOrigData();
        if (!is_array($orig) || $orig === []) {
            $orig = $this->loadFresh($object);
        }
        if ($object instanceof Product && $object->getId()) {
            $orig = $this->addProductRelations($object, $orig);
        }
        return $orig;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadFresh(AbstractModel $object): array
    {
        $id = $object->getId();
        if (!$id) {
            return [];
        }
        try {
            /** @var AbstractModel $fresh */
            $fresh = $this->objectManager->create(get_class($object));
            $object->getResource()->load($fresh, $id);
            return $fresh->getId() ? $fresh->getData() : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Category and website assignments are not part of the loaded data, read them while the DB still has them.
     *
     * @param array<string, mixed> $orig
     * @return array<string, mixed>
     */
    private function addProductRelations(Product $product, array $orig): array
    {
        $resource = $product->getResource();
        if (!$resource instanceof ProductResource) {
            return $orig;
        }
        try {
            if ($product->hasData('category_ids') && !array_key_exists('category_ids', $orig)) {
                $orig['category_ids'] = $resource->getCategoryIds($product);
            }
            if ($product->hasData('website_ids') && !array_key_exists('website_ids', $orig)) {
                $orig['website_ids'] = $resource->getWebsiteIds($product);
            }
        } catch (\Throwable) {
            return $orig;
        }
        return $orig;
    }
}
