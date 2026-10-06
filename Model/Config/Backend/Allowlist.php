<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Softaware\ActivityLog\Model\IpMatcher;

/**
 * Validates the allowlist and refuses to enable it when the current admin's own IP is not on it.
 */
class Allowlist extends Value
{
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly IpMatcher $ipMatcher,
        private readonly RemoteAddress $remoteAddress,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $rules = [];
        foreach (preg_split('/\R/', (string)$this->getValue()) ?: [] as $line) {
            $rule = trim(preg_replace('/#.*$/', '', $line) ?? '');
            if ($rule === '') {
                continue;
            }
            if (!$this->ipMatcher->isValidRule($rule)) {
                throw new LocalizedException(__('"%1" is not a valid IP address or CIDR range.', $rule));
            }
            $rules[] = $rule;
        }
        $enabled = (bool)$this->getFieldsetDataValue('enabled');
        $ip = (string)$this->remoteAddress->getRemoteAddress();
        if ($enabled && $rules !== [] && $ip !== '' && !$this->ipMatcher->matches($ip, $rules)) {
            throw new LocalizedException(
                __('Your current IP address %1 is not on the allowlist. Add it first so you do not lock yourself out.', $ip)
            );
        }
        return parent::beforeSave();
    }
}
