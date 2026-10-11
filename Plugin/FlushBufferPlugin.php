<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use CommerceGears\ActivityLog\Model\LogBuffer;

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
