<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Softaware\ActivityLog\Model\Login;

class LoginStatus implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Login::STATUS_SUCCESS, 'label' => __('Signed in')],
            ['value' => Login::STATUS_FAILURE, 'label' => __('Failed')],
            ['value' => Login::STATUS_LOCKED, 'label' => __('Locked out')],
            ['value' => Login::STATUS_BLOCKED, 'label' => __('Blocked (IP)')],
            ['value' => Login::STATUS_LOGOUT, 'label' => __('Signed out')],
        ];
    }
}
