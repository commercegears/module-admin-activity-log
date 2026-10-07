<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Softaware\ActivityLog\Model\Config;

class IpSource implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::IP_SOURCE_SERVER, 'label' => __('Web server (REMOTE_ADDR)')],
            ['value' => Config::IP_SOURCE_CLOUDFLARE, 'label' => __('Cloudflare (CF-Connecting-IP)')],
        ];
    }
}
