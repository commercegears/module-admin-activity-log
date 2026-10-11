<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CommerceGears\ActivityLog\Model\EntityTypeResolver;

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
