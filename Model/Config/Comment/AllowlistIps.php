<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\Config\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Escaper;
use Softaware\ActivityLog\Model\RequestContext;

/**
 * Comment of the "Allowed IP Addresses" field: format help plus the admin's current IP address, so it can be
 * added before the allowlist is switched on.
 */
class AllowlistIps implements CommentInterface
{
    public function __construct(
        private readonly RequestContext $requestContext,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @param string $elementValue
     */
    public function getCommentText($elementValue): string
    {
        $text = (string)__(
            'One IPv4/IPv6 address or CIDR range per line, for example %1 or %2. Text after %3 is a note.',
            '<code>203.0.113.10</code>',
            '<code>198.51.100.0/24</code>',
            '<code>#</code>'
        );
        $ip = $this->requestContext->getIp();
        if ($ip !== '') {
            $text .= ' ' . (string)__('Your current IP address: %1', '<code>' . $this->escaper->escapeHtml($ip) . '</code>');
        }
        return $text;
    }
}
