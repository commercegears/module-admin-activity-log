<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use CommerceGears\ActivityLog\Model\Source\Action;

/**
 * Admin Actions grid data: the "Save" action filter matches created and changed records (stored as create / update),
 * as Adobe Commerce lists both as Save.
 */
class LogDataProvider extends DataProvider
{
    public function addFilter(Filter $filter): void
    {
        if ($filter->getField() === 'action' && is_string($filter->getValue())) {
            $codes = Action::getFilterCodes($filter->getValue());
            if (count($codes) > 1) {
                $filter->setConditionType('in');
                $filter->setValue($codes);
            }
        }
        parent::addFilter($filter);
    }
}
