<?php

namespace App\Console\Commands;

use App\Services\Waha\WahaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Attribute\AsCommand;
#[AsCommand(name: 'waha:webhook:sync')]
class SyncWahaWebhook extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'waha:webhook:sync {--url=}';

    /**
     * The console command description.
     */
    protected $description = 'Register the WAHA webhook callback URL to receive incoming WhatsApp messages.';

    public function handle(WahaClient $client): int
    {
        $callbackUrl = $this->option('url')
            ?? config('services.waha.webhook_url')
            ?? rtrim(config('app.url'), '/').'/api/webhooks/waha';

        if (! $callbackUrl) {
            $this->error('No webhook URL provided or configured.');

            return self::FAILURE;
        }

        $this->info("Configuring WAHA webhook for {$callbackUrl}");

        $success = $client->registerWebhook($callbackUrl);

        if (! $success) {
            $this->error('Failed to register WAHA webhook. Check logs for details.');

            return self::FAILURE;
        }

        $this->info('WAHA webhook registered successfully.');

        Log::info('WAHA webhook sync complete', ['callback_url' => $callbackUrl]);

        return self::SUCCESS;
    }
}
