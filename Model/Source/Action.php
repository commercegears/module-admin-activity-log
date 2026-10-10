<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Action codes stored in softaware_activity_log.action and their labels, named as in the Adobe Commerce action log
 * (Save, Delete, Mass Update, ...). The codes stay stable; only the labels follow Adobe Commerce.
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
            ['value' => self::CREATE, 'label' => __('New')],
            ['value' => self::SAVE, 'label' => __('Save')],
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
