<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * One admin login event (success, failure, lockout, blocked IP or logout).
 */
class Login extends AbstractModel
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_LOCKED = 'locked';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_LOGOUT = 'logout';

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Login::class);
    }
}
