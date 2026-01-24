<?php

namespace App\Jobs;

use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $target;
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $target, string $message)
    {
        $this->target = $target;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(FonnteService $fonnteService): void
    {
        Log::info("Job Processing: Sending WA to {$this->target}");
        
        try {
            $result = $fonnteService->sendMessage($this->target, $this->message);
            
            if (isset($result['status']) && $result['status'] === false) {
                // If Fonnte returns false status, you might want to fail the job
                // based on the response structure
                Log::warning("Fonnte API returned false status for {$this->target}");
            }
        } catch (\Exception $e) {
            Log::error("Job Failed: Error sending WA to {$this->target}: " . $e->getMessage());
            // Rethrow exception to trigger automatic retry by Laravel Queue
            throw $e;
        }
    }
    
    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10]; // Retry after 1s, 5s, 10s
    }
}
