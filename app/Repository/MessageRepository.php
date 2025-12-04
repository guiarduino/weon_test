<?php

namespace App\Repository;

use App\Models\Message;
use BcMath\Number;
use Illuminate\Support\Facades\DB;

class MessageRepository
{
    private Message $model;

    public function __construct(Message $model)
    {
        $this->model = $model;
    }

    public function store($number, array $data): Message
    {
        try {
            DB::beginTransaction();

            $message = $this->model->create([
                'provider_id' => $data['id'],
                'source'      => $data['source'],
                'direction'   => 'inbound',
                'from'        => $data['sender']['phone'] ?? null,
                'type'        => $data['type'],
                'body'        => $data['payload']['text'] ?? null,
                'status'      => 'sent',
                'to'          => $number,
            ]);

            DB::commit();

            return $message;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function updateStatus(string $id, array $data): bool
    {
        $message = $this->model->where('provider_id', $id)->first();

        if (!$message) {
            return false;
        }

        return $message->update($data);

    }
}