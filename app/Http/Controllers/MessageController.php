<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\MessageService;

class MessageController extends Controller
{

    private Message $model;
    private MessageService $service;

    public function __construct(Message $model, private MessageService $messageService)
    {
        $this->model = $model;
        $this->service = $messageService;
    }

     public function store(array $data)
    {
        return $this->service->storeIncoming($data);
    }

    public function updateEvent(array $data)
    {
        return $this->service->updateStatus($data);
    }
}
