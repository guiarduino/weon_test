<?php

namespace App\Repository;

use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class MessageRepository
{
    /**
     * Cria ou atualiza uma mensagem baseado no provider_id
     */
    public function createOrUpdate(array $data): Message
    {
        $providerId = $data['provider_id'] ?? null;

        if (!$providerId) {
            throw new \Exception('provider_id é obrigatório');
        }

        $message = Message::updateOrCreate(
            ['provider_id' => $providerId],
            $data
        );

        Log::info('Message created or updated', [
            'provider_id' => $providerId,
            'id' => $message->id,
            'status' => $message->status
        ]);

        return $message;
    }

    /**
     * Busca mensagem por provider_id
     */
    public function findByProviderId(string $providerId): ?Message
    {
        return Message::where('provider_id', $providerId)->first();
    }

    /**
     * Atualiza uma mensagem existente
     */
    public function update(Message $message, array $data): bool
    {
        $updated = $message->update($data);

        Log::info('Message updated', [
            'id' => $message->id,
            'provider_id' => $message->provider_id,
            'updated_fields' => array_keys($data)
        ]);

        return $updated;
    }

    /**
     * Lista mensagens com filtros opcionais
     */
    public function list(array $filters = [])
    {
        $query = Message::query();

        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }
        if (isset($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        if (isset($filters['from'])) {
            $query->where('from', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->where('to', $filters['to']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['error_code'])) {
            $query->where('error_code', $filters['error_code']);
        }
        if (isset($filters['error_reason'])) {
            $query->where('error_reason', $filters['error_reason']);
        }
        if (isset($filters['created_at'])) {
            $query->whereDate('created_at', $filters['created_at']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}