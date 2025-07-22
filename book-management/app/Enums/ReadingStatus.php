<?php

namespace App\Enums;

enum ReadingStatus: string
{
    case UNREAD = 'unread';
    case READING = 'reading';
    case READ = 'read';

    public function label(): string
    {
        return match($this) {
            self::UNREAD => '未読',
            self::READING => '読書中',
            self::READ => '既読',
        };
    }

    public static function options(): array
    {
        return [
            self::UNREAD->value => self::UNREAD->label(),
            self::READING->value => self::READING->label(),
            self::read->value => self::READ->label(),
        ];
    }
}
