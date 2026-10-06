<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Softaware\ActivityLog\Model\LogFactory;
use Softaware\ActivityLog\Model\ResourceModel\Log as LogResource;

class View extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Softaware_ActivityLog::actions';
    public const REGISTRY_KEY = 'softaware_activitylog_entry';

    public function __construct(
        Context $context,
        private readonly LogFactory $logFactory,
        private readonly LogResource $logResource,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $log = $this->logFactory->create();
        $this->logResource->load($log, (int)$this->getRequest()->getParam('id'));
        if (!$log->getId()) {
            $this->messageManager->addErrorMessage(__('This log entry no longer exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
        $this->registry->register(self::REGISTRY_KEY, $log);
        /** @var \Magento\Framework\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Softaware_ActivityLog::actions');
        $page->getConfig()->getTitle()->prepend(__('Admin Action #%1', $log->getId()));
        return $page;
    }
}
