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

/**
 * Login log entry types, named as in the Adobe Commerce action log (Login, Logout); the stored codes are unchanged.
 */
class LoginStatus implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Login::STATUS_SUCCESS, 'label' => __('Login')],
            ['value' => Login::STATUS_FAILURE, 'label' => __('Login Failed')],
            ['value' => Login::STATUS_LOCKED, 'label' => __('Account Locked')],
            ['value' => Login::STATUS_BLOCKED, 'label' => __('Login Blocked (IP)')],
            ['value' => Login::STATUS_LOGOUT, 'label' => __('Logout')],
        ];
    }
}
