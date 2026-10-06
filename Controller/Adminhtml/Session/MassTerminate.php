<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Controller\Adminhtml\Session;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Softaware\ActivityLog\Model\ResourceModel\Session\Grid\CollectionFactory;
use Softaware\ActivityLog\Model\SessionTerminator;

class MassTerminate extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Softaware_ActivityLog::sessions';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly SessionTerminator $terminator
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $done = 0;
        $skipped = 0;
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            foreach ($collection->getAllIds() as $id) {
                try {
                    $this->terminator->terminate((int)$id);
                    $done++;
                } catch (LocalizedException) {
                    $skipped++;
                }
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        if ($done) {
            $this->messageManager->addSuccessMessage(__('%1 session(s) terminated.', $done));
        }
        if ($skipped) {
            $this->messageManager->addNoticeMessage(__('%1 session(s) skipped (your own session or already ended).', $skipped));
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
