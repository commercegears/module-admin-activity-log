<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use Softaware\ActivityLog\Model\LogBuffer;

/**
 * Writes the collected log entries once, after the admin controller has finished.
 */
class FlushBufferPlugin
{
    public function __construct(
        private readonly LogBuffer $buffer
    ) {
    }

    public function afterDispatch(FrontControllerInterface $subject, mixed $result): mixed
    {
        $this->buffer->flush();
        return $result;
    }
}
