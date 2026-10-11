<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model\ResourceModel\Login;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use CommerceGears\ActivityLog\Model\Login;
use CommerceGears\ActivityLog\Model\ResourceModel\Login as LoginResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'login_id';

    protected function _construct(): void
    {
        $this->_init(Login::class, LoginResource::class);
    }
}
