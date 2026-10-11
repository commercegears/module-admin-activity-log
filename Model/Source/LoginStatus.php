<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CommerceGears\ActivityLog\Model\Login;

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
