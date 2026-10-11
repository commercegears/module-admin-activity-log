<?php
/**
 * CommerceGears Admin Activity Log
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Action codes stored in commercegears_activity_log.action and their labels, named as in the Adobe Commerce action log
 * (Save, Delete, Mass Update, ...). The codes stay stable; only the labels follow Adobe Commerce.
 *
 * As in Adobe Commerce, created and changed records are both "Save": "create" and "update" share the label. The grid
 * filter lists "Save" once (duplicate labels are dropped in the filter) and selecting it matches both codes
 * (see \CommerceGears\ActivityLog\Ui\DataProvider\LogDataProvider).
 */
class Action implements OptionSourceInterface
{
    public const CREATE = 'create';
    public const SAVE = 'update';
    public const DELETE = 'delete';
    public const MOVE = 'move';
    public const MASS_UPDATE = 'mass_update';
    public const MASS_DELETE = 'mass_delete';
    public const IMPORT = 'import';
    public const CANCEL = 'cancel';
    public const HOLD = 'hold';
    public const UNHOLD = 'unhold';

    /** Code written by versions before 1.4.0; the 1.4.0 data patch converts it to mass_update / mass_delete. */
    public const LEGACY_MASS_ACTION = 'mass_action';

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::SAVE, 'label' => __('Save')],
            ['value' => self::CREATE, 'label' => __('Save')],
            ['value' => self::DELETE, 'label' => __('Delete')],
            ['value' => self::MOVE, 'label' => __('Move')],
            ['value' => self::MASS_UPDATE, 'label' => __('Mass Update')],
            ['value' => self::MASS_DELETE, 'label' => __('Mass Delete')],
            ['value' => self::IMPORT, 'label' => __('Import')],
            ['value' => self::CANCEL, 'label' => __('Cancel')],
            ['value' => self::HOLD, 'label' => __('Hold')],
            ['value' => self::UNHOLD, 'label' => __('Unhold')],
        ];
    }

    /**
     * Stored codes a filter on the given code must match ("Save" covers created and changed records).
     *
     * @return string[]
     */
    public static function getFilterCodes(string $code): array
    {
        return $code === self::SAVE || $code === self::CREATE ? [self::SAVE, self::CREATE] : [$code];
    }

    public function getLabel(string $code): string
    {
        if ($code === self::LEGACY_MASS_ACTION) {
            return (string)__('Mass Update');
        }
        foreach ($this->toOptionArray() as $option) {
            if ($option['value'] === $code) {
                return (string)$option['label'];
            }
        }
        return $code;
    }

    /**
     * Mass action code for an admin controller action name (massDelete, massRemove => Mass Delete).
     */
    public static function forMassAction(string $actionName): string
    {
        return in_array(strtolower($actionName), ['massdelete', 'massremove'], true)
            ? self::MASS_DELETE
            : self::MASS_UPDATE;
    }

    /**
     * Order status actions (Cancel, Hold, Unhold) for a saved order, from the admin action that saved it.
     *
     * @param string $requestPath route/controller/action, as stored in request_path
     */
    public static function forOrderRequest(string $requestPath): ?string
    {
        $parts = explode('/', $requestPath);
        if (count($parts) !== 3) {
            return null;
        }
        return match (strtolower($parts[2])) {
            'cancel', 'masscancel' => self::CANCEL,
            'hold', 'masshold' => self::HOLD,
            'unhold', 'massunhold' => self::UNHOLD,
            default => null,
        };
    }
}
