<?php

namespace App;

use App\Message\DailyReminderMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Planificateur Symfony — déclenche DailyReminderMessage tous les jours à 8h (CRON "0 8 * * *").
 * stateful() + processOnlyLastMissedRun() évitent les envois multiples si le worker a été arrêté.
 */
#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->add(
                RecurringMessage::cron('* * * * *', new DailyReminderMessage())
            );
    }
}
