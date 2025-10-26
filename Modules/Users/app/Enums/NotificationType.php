<?php

namespace Modules\Users\Enums;

enum NotificationType: string
{
    case Announcement = 'announcement';
    case Alert = 'alert';
    case Reminder = 'reminder';
    case ActionRequired = 'action';

    public static function labels(): array
    {
        return [
            self::Announcement->value => 'Announcement',
            self::Alert->value => 'Alert',
            self::Reminder->value => 'Reminder',
            self::ActionRequired->value => 'Action Required',
        ];
    }

    public static function values(): array
    {
        return array_keys(self::labels());
    }
}

