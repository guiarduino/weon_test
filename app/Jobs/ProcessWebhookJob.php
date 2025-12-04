<?php

namespace App\Jobs;

use App\Services\MessageService;

class ProcessWebhookJob extends Job
{
    protected $number;
    protected $data;

    public function __construct($number, $data)
    {
        $this->number = $number;
        $this->data   = $data;
    }

    public function handle()
    {
        $type = $this->data['type'] ?? null;
        $service = app(MessageService::class);

        switch ($type) {
            case 'message':
                $service->storeIncoming($this->number, $this->data);
                break;
            
            case 'message-event':
                $service->updateStatus($this->number, $this->data);
            
            default:
                # code...
                break;
        }
    }
}
