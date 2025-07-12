<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestIntegrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:integration 
                            {--filter= : Filter tests by name}
                            {--stop-on-failure : Stop on first failure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run integration tests that may interact with external services';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('⚠️  Running integration tests that may interact with external services...');
        $this->warn('⚠️  This might disconnect your Telegram session or trigger rate limits.');
        
        if (!$this->confirm('Do you want to continue?')) {
            $this->info('Integration tests cancelled.');
            return Command::SUCCESS;
        }

        $this->newLine();
        
        // Build PHPUnit command
        $command = ['./vendor/bin/phpunit', '-c', 'phpunit.integration.xml'];
        
        if ($filter = $this->option('filter')) {
            $command[] = '--filter';
            $command[] = $filter;
        }
        
        if ($this->option('stop-on-failure')) {
            $command[] = '--stop-on-failure';
        }
        
        // Run PHPUnit with integration config
        $process = new Process($command);
        $process->setTty(true);
        $process->setTimeout(null);
        
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
        
        return $process->getExitCode();
    }
}