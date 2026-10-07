<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Controller\Adminhtml\Session;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Softaware_ActivityLog::sessions';

    public function execute(): Page
    {
        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Softaware_ActivityLog::sessions');
        $page->getConfig()->getTitle()->prepend(__('Active Sessions'));
        return $page;
    }
}
