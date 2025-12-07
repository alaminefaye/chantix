<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearAllCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vider tous les caches Laravel (cache, config, route, view, event)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Vidage de tous les caches...');
        
        $this->call('cache:clear');
        $this->info('✓ Cache vidé');
        
        $this->call('config:clear');
        $this->info('✓ Configuration vidée');
        
        $this->call('route:clear');
        $this->info('✓ Routes vidées');
        
        $this->call('view:clear');
        $this->info('✓ Vues vidées');
        
        $this->call('event:clear');
        $this->info('✓ Événements vidés');
        
        $this->newLine();
        $this->info('✅ Tous les caches ont été vidés avec succès!');
        
        return 0;
    }
}
