<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CommerceGears\ActivityLog\Model\Config;

class IpSource implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::IP_SOURCE_SERVER, 'label' => __('Any request')],
            ['value' => Config::IP_SOURCE_CLOUDFLARE, 'label' => __("Cloudflare's addresses only")],
        ];
    }
}
