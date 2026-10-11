<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Shows an option column as a Magento grid severity badge (grid-severity-notice/minor/major/critical).
 * The "severities" data argument maps option values to a severity; unmapped values are shown as plain text.
 * Filters and exports keep using the column's options and the raw value.
 */
class SeverityBadge extends Column
{
    private const SEVERITIES = ['notice', 'minor', 'major', 'critical'];

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly Escaper $escaper,
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
        $name = (string)$this->getData('name');
        $labels = $this->getLabels();
        $severities = (array)$this->getData('severities');
        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            $value = (string)($item[$name] ?? '');
            $label = $this->escaper->escapeHtml($labels[$value] ?? $value);
            $severity = (string)($severities[$value] ?? '');
            $dataSource['data']['items'][$i][$name] = in_array($severity, self::SEVERITIES, true)
                ? sprintf('<span class="grid-severity-%s"><span>%s</span></span>', $severity, $label)
                : $label;
        }
        return $dataSource;
    }

    /**
     * @return array<string, string>
     */
    private function getLabels(): array
    {
        $options = $this->getData('options');
        if ($options instanceof OptionSourceInterface) {
            $options = $options->toOptionArray();
        }
        $labels = [];
        foreach ((array)$options as $option) {
            if (isset($option['value']) && !is_array($option['value'])) {
                $labels[(string)$option['value']] = (string)$option['label'];
            }
        }
        return $labels;
    }
}
