<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupPuzzleWords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'puzzle:cleanup-words';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup puzzle words: remove comma+number and deactivate words with English/number characters';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting puzzle words cleanup...');
        
        try {
            // 1. 쉼표와 숫자가 포함된 단어 정리
            $this->cleanupCommaNumberWords();
            
            // 2. 영문이나 숫자가 포함된 단어 비활성화
            $this->deactivateEnglishNumberWords();
            
            $this->info('Puzzle words cleanup completed successfully!');
            Log::info('Puzzle words cleanup completed successfully');
            
        } catch (\Exception $e) {
            $this->error('Error during puzzle words cleanup: ' . $e->getMessage());
            Log::error('Puzzle words cleanup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * 쉼표와 숫자가 포함된 단어 정리
     */
    private function cleanupCommaNumberWords()
    {
        $this->info('Cleaning up words with comma and numbers...');
        
        $affectedRows = DB::table('pz_words')
            ->where('word', 'LIKE', '%,%')
            ->update([
                'word' => DB::raw("SUBSTRING(word FROM 1 FOR POSITION(',' IN word) - 1)"),
                'length' => DB::raw("CHAR_LENGTH(SUBSTRING(word FROM 1 FOR POSITION(',' IN word) - 1))")
            ]);
            
        $this->info("Updated {$affectedRows} words with comma and numbers");
        Log::info("Updated {$affectedRows} words with comma and numbers");
    }
    
    /**
     * 영문이나 숫자가 포함된 단어 비활성화
     */
    private function deactivateEnglishNumberWords()
    {
        $this->info('Deactivating words with English letters or numbers...');
        
        $affectedRows = DB::table('pz_words')
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('word', '~', '[a-zA-Z]')  // 영문 포함
                      ->orWhere('word', '~', '[0-9]');  // 숫자 포함
            })
            ->update(['is_active' => false]);
            
        $this->info("Deactivated {$affectedRows} words with English letters or numbers");
        Log::info("Deactivated {$affectedRows} words with English letters or numbers");
    }
}
