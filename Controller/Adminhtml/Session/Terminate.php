<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Controller\Adminhtml\Session;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use CommerceGears\ActivityLog\Model\SessionTerminator;

class Terminate extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_ActivityLog::sessions';

    public function __construct(
        Context $context,
        private readonly SessionTerminator $terminator
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        try {
            $this->terminator->terminate((int)$this->getRequest()->getParam('id'));
            $this->messageManager->addSuccessMessage(__('The session was terminated. The admin is signed out on their next click.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
