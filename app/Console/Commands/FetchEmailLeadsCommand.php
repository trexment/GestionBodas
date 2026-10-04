<?php

namespace App\Console\Commands;

use App\Services\ImapLeadFetcherService;
use Illuminate\Console\Command;

class FetchEmailLeadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:fetch-leads {--brand= : Specific brand to scan (nunez_and_son, javnx, mago_leugim)} {--test : Only test connections without creating leads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan IMAP mailboxes for incoming email leads and create events categorized by brand';

    /**
     * Execute the console command.
     */
    public function handle(ImapLeadFetcherService $service): int
    {
        $brand = $this->option('brand');
        $isTest = $this->option('test');

        $this->info('🚀 Iniciando escaneo de correos entrantes IMAP...');

        if ($isTest) {
            $brands = $brand ? [$brand] : ['nunez_and_son', 'javnx', 'mago_leugim'];
            foreach ($brands as $b) {
                $this->line("Probando conexión para {$b}...");
                $test = $service->testConnection($b);
                if ($test['success']) {
                    $this->info("✅ {$test['message']}");
                } else {
                    $this->warn("⚠️ {$test['message']}");
                }
            }
            return Command::SUCCESS;
        }

        $results = $service->fetchAllActiveMailboxes($brand);

        $this->info("✅ Escaneo completado:");
        $this->line("  - Correos no leídos analizados: {$results['total_scanned']}");
        $this->line("  - Nuevos eventos/leads creados: {$results['total_leads_created']}");

        foreach ($results['details'] as $bKey => $detail) {
            $this->line("  📁 [{$bKey}] ({$detail['email']}): {$detail['scanned']} analizados, {$detail['created']} leads creados");
            if (!empty($detail['errors'])) {
                foreach ($detail['errors'] as $err) {
                    $this->error("     ❌ Error: {$err}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
