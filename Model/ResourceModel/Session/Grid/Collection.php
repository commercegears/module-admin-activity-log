<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\ResourceModel\Session\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Security\Model\AdminSessionInfo;
use Magento\Security\Model\ConfigInterface as SecurityConfig;
use Psr\Log\LoggerInterface as Logger;
use Softaware\ActivityLog\Model\ResourceModel\Login;

/**
 * Active admin sessions: Magento's admin_user_session records that are signed in and not expired,
 * with user details and the browser/country from the login log.
 */
class Collection extends SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        private readonly SecurityConfig $securityConfig,
        $mainTable = 'admin_user_session',
        $resourceModel = \Magento\Security\Model\ResourceModel\AdminSessionInfo::class,
        $identifierName = 'id',
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    protected function _initSelect(): static
    {
        parent::_initSelect();
        $lifetime = max(60, (int)$this->securityConfig->getAdminSessionLifetime());
        $this->getSelect()
            ->joinLeft(
                ['admin_user' => $this->getTable('admin_user')],
                'admin_user.user_id = main_table.user_id',
                ['username', 'firstname', 'lastname', 'email']
            )
            ->joinLeft(
                ['login' => $this->getTable(Login::TABLE)],
                "login.session_info_id = main_table.id AND login.status = 'success'",
                ['user_agent', 'country']
            )
            ->where('main_table.status = ?', AdminSessionInfo::LOGGED_IN)
            ->where('main_table.updated_at >= ?', gmdate('Y-m-d H:i:s', time() - $lifetime));
        foreach (['id', 'ip', 'created_at', 'updated_at', 'user_id', 'status'] as $field) {
            $this->addFilterToMap($field, 'main_table.' . $field);
        }
        foreach (['username', 'firstname', 'lastname', 'email'] as $field) {
            $this->addFilterToMap($field, 'admin_user.' . $field);
        }
        $this->addFilterToMap('user_agent', 'login.user_agent');
        $this->addFilterToMap('country', 'login.country');
        return $this;
    }
}
