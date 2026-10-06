<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\ResourceModel\Login;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Softaware\ActivityLog\Model\Login;
use Softaware\ActivityLog\Model\ResourceModel\Login as LoginResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'login_id';

    protected function _construct(): void
    {
        $this->_init(Login::class, LoginResource::class);
    }
}
