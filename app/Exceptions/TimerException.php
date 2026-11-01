<?php

namespace App\Exceptions;

use DomainException;

/** A timer action that cannot be done in the current state; the message is shown to the user. */
class TimerException extends DomainException
{
    public static function alreadyRunning(): self
    {
        return new self('Таймер уже запущен. Остановите его, чтобы начать новую задачу.');
    }

    public static function notRunning(): self
    {
        return new self('Таймер не запущен.');
    }

    public static function busy(): self
    {
        return new self('Таймер этого сотрудника сейчас меняется. Повторите через секунду.');
    }
}
