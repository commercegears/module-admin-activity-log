<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Softaware\ActivityLog\Model\EntityTypeResolver;

class EntityType implements OptionSourceInterface
{
    public function __construct(
        private readonly EntityTypeResolver $resolver
    ) {
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->resolver->getTypeOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $options;
    }
}
