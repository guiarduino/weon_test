<?php

namespace App\Jobs;

use App\Services\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected string $number;
    protected array $data;

    /**
     * Número de tentativas do job
     */
    public int $tries = 3;

    /**
     * Timeout do job em segundos
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(string $number, array $data)
    {
        $this->number = $number;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(MessageService $service): void
    {
        try {
            $type = $this->data['type'] ?? null;

            if (!$type) {
                Log::warning('Webhook sem tipo definido', [
                    'number' => $this->number,
                    'data' => $this->data
                ]);
                return;
            }

            Log::info('Processing webhook job', [
                'type' => $type,
                'number' => $this->number,
                'payload_id' => $this->data['payload']['id'] ?? 'unknown'
            ]);

            switch ($type) {
                case 'message':
                    $service->storeIncoming($this->number, $this->data);
                    Log::info('Incoming message processed', [
                        'number' => $this->number,
                        'message_id' => $this->data['payload']['id'] ?? 'unknown'
                    ]);
                    break;
                
                case 'message-event':
                    $service->storeIncoming($this->number, $this->data);
                    Log::info('Message event processed', [
                        'number' => $this->number,
                        'event_type' => $this->data['payload']['type'] ?? 'unknown',
                        'message_id' => $this->data['payload']['gsId'] ?? $this->data['payload']['id'] ?? 'unknown'
                    ]);
                    break;

                default:
                    Log::warning('Tipo de webhook desconhecido', [
                        'type' => $type,
                        'number' => $this->number,
                        'data' => $this->data
                    ]);
                    break;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Erro de validação ao processar webhook job', [
                'errors' => $e->errors(),
                'number' => $this->number,
                'data' => $this->data,
                'attempt' => $this->attempts()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'number' => $this->number,
                'data' => $this->data,
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job failed after all attempts', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'number' => $this->number,
            'data' => $this->data,
            'attempts' => $this->attempts(),
            'type' => $this->data['type'] ?? 'unknown'
        ]);
    }
}