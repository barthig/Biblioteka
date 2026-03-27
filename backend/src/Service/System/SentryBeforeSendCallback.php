<?php

declare(strict_types=1);

namespace App\Service\System;

final class SentryBeforeSendCallback
{
    public static function beforeSend(object $event, ?object $hint): object
    {
        return $event;
    }
}

