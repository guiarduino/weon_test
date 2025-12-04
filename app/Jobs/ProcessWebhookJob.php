<?php

namespace App\Jobs;

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

        if ($type === 'message') {
            // mensagem nova
        }

        if ($type === 'message-event') {
            // atualização
        }
    }
}
