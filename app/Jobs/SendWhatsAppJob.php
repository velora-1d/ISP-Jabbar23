<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
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
    public function handle(WhatsAppService $whatsAppService): void
    {
        Log::info("Job Processing: Sending WA to {$this->target}");
        
        try {
            $success = $whatsAppService->send($this->target, $this->message);
            
            if (!$success) {
                Log::warning("WhatsAppService returned false status for {$this->target}");
                // Optional: throw exception to force retry?
                // throw new \Exception("Failed to send WhatsApp");
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
        return [10, 30, 60]; // Retry after 10s, 30s, 60s
    }
}
