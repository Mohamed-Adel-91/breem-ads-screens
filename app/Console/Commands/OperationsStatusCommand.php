<?php

namespace App\Console\Commands;

use App\Models\PlaybackLog;
use App\Models\Report;
use App\Models\ScreenLog;
use App\Support\OperationsRecipients;
use App\Support\Retention;
use App\Support\ScreenHealth;
use Illuminate\Console\Command;

/**
 * Read-only readiness check for the operational layer.
 *
 * Exists because two things that matter in production are invisible from the
 * dashboard: whether operational alerts have anywhere to go, and whether retention is
 * actually pruning. Both are pure configuration, so neither shows up until the moment
 * it is needed — an unset recipient is only discovered when a screen has already
 * dropped offline and nobody was told.
 *
 * Reports, never mutates. It prints no webhook URL and no bot token, only whether
 * they are set. Exit code 1 when operational alerting has no recipient, so a
 * deployment pipeline can treat that as a failed readiness gate.
 */
class OperationsStatusCommand extends Command
{
    protected $signature = 'ops:status';

    protected $description = 'Report operational notification and data-retention configuration';

    public function handle(): int
    {
        $this->components->info('Operational notifications');

        $recipients = OperationsRecipients::describe();

        $this->components->twoColumnDetail(
            'Operations email',
            $recipients['email'] ?? '<fg=yellow>not configured</>'
        );
        $this->components->twoColumnDetail(
            'Slack',
            $recipients['slack_configured']
                ? '<fg=green>configured</> ('.$recipients['slack_route_kind'].')'
                : 'not configured'
        );
        $this->components->twoColumnDetail(
            'Queue connection',
            (string) config('queue.default')
        );
        $this->components->twoColumnDetail(
            'Mailer',
            (string) config('mail.default')
        );

        $this->newLine();
        $this->components->info('Offline detection');
        $this->components->twoColumnDetail('Heartbeat interval', ScreenHealth::heartbeatInterval().'s');
        $this->components->twoColumnDetail('Offline after', ScreenHealth::offlineAfter().'s');

        $this->newLine();
        $this->components->info('Data retention (config/retention.php)');

        $counts = [
            Retention::SCREEN_LOGS => ScreenLog::count(),
            Retention::PLAYBACK_LOGS => PlaybackLog::count(),
            Retention::REPORTS => Report::count(),
        ];

        foreach (Retention::describe() as $policy => $state) {
            $this->components->twoColumnDetail(
                $policy.'  <fg=gray>('.$counts[$policy].' rows)</>',
                $state['enabled']
                    ? '<fg=green>'.$state['days'].' days</> — prunes before '.$state['cutoff']
                    : '<fg=yellow>disabled</> (keeps everything)'
            );
        }

        $this->newLine();

        if (! $recipients['configured']) {
            $this->components->warn(
                'No operational recipient is configured. Offline detection still runs and still '
                .'records transitions, but nobody is notified. Set OPS_NOTIFICATION_EMAIL.'
            );

            return self::FAILURE;
        }

        if (! collect(Retention::describe())->contains(fn (array $state) => $state['enabled'])) {
            // Not a failure: shipping with retention off is the deliberate default.
            $this->components->warn(
                'All retention policies are disabled, so operational tables grow without bound. '
                .'Set SCREEN_LOG_RETENTION_DAYS / PLAYBACK_LOG_RETENTION_DAYS before launch. '
                .'Preview with `php artisan model:prune --pretend`.'
            );
        }

        $this->components->info('Operational configuration looks deliverable.');

        return self::SUCCESS;
    }
}
