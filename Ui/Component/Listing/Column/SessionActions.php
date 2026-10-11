<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Ui\Component\Listing\Column;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class SessionActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly AuthSession $authSession,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        $current = (int)$this->authSession->getAdminSessionInfoId();
        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            $id = (int)($item['id'] ?? 0);
            if (!$id) {
                continue;
            }
            if ($id === $current) {
                $dataSource['data']['items'][$i]['username'] = ($item['username'] ?? '') . ' ' . __('(this session)');
                continue;
            }
            $dataSource['data']['items'][$i][$this->getData('name')] = [
                'terminate' => [
                    'href' => $this->urlBuilder->getUrl('commercegears_activitylog/session/terminate', ['id' => $id]),
                    'label' => __('Terminate'),
                    'post' => true,
                    'confirm' => [
                        'title' => __('Terminate Session'),
                        'message' => __(
                            'Sign out %1 (IP %2)? They are signed out on their next click.',
                            htmlspecialchars((string)($item['username'] ?? ''), ENT_QUOTES),
                            htmlspecialchars((string)($item['ip'] ?? ''), ENT_QUOTES)
                        ),
                    ],
                ],
            ];
        }
        return $dataSource;
    }
}
