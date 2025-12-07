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
        $this->info('✓ Cache Laravel vidé');
        
        $this->call('config:clear');
        $this->info('✓ Configuration vidée');
        
        $this->call('route:clear');
        $this->info('✓ Routes vidées');
        
        $this->call('view:clear');
        $this->info('✓ Vues vidées');
        
        $this->call('event:clear');
        $this->info('✓ Événements vidés');
        
        // Vider OPcache si disponible
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $this->info('✓ OPcache vidé');
        }
        
        // Vider APCu si disponible
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
            $this->info('✓ APCu vidé');
        }
        
        // Nettoyer les fichiers compilés
        $cacheFiles = glob(storage_path('framework/cache/data/*'));
        $viewFiles = glob(storage_path('framework/views/*.php'));
        $bootstrapFiles = glob(bootstrap_path('cache/*.php'));
        
        $deleted = 0;
        foreach (array_merge($cacheFiles, $viewFiles, $bootstrapFiles) as $file) {
            if (is_file($file)) {
                @unlink($file);
                $deleted++;
            }
        }
        
        if ($deleted > 0) {
            $this->info("✓ {$deleted} fichier(s) compilé(s) supprimé(s)");
        }
        
        $this->newLine();
        $this->info('✅ Tous les caches ont été vidés avec succès!');
        
        return 0;
    }
}
