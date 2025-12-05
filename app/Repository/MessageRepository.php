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
     * Lista mensagens com filtros opcionais
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Message::query();

        // Aplica filtros
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['to'])) {
            $query->where('to', $filters['to']);
        }

        if (!empty($filters['from'])) {
            $query->where('from', $filters['from']);
        }

        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        // Filtro por data
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Busca uma mensagem por ID ou provider_id
     */
    public function findByIdOrProviderId(string $id): ?Message
    {
        return Message::where('id', $id)
            ->orWhere('provider_id', $id)
            ->first();
    }

    /**
     * Busca mensagem por provider_id
     */
    public function findByProviderId(string $providerId): ?Message
    {
        return Message::where('provider_id', $providerId)->first();
    }

    /**
     * Busca mensagem por ID
     */
    public function findById(int $id): ?Message
    {
        return Message::find($id);
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
     * Busca todas as mensagens por status
     */
    public function findByStatus(string $status): Collection
    {
        return Message::where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca mensagens por destinatário
     */
    public function findByDestination(string $destination): Collection
    {
        return Message::where('to', $destination)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca mensagens por remetente
     */
    public function findBySender(string $sender): Collection
    {
        return Message::where('from', $sender)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Conta mensagens por status
     */
    public function countByStatus(string $status): int
    {
        return Message::where('status', $status)->count();
    }

    /**
     * Busca mensagens dentro de um período
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): Collection
    {
        return Message::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca mensagens com erro
     */
    public function findFailed(): Collection
    {
        return Message::where('status', 'failed')
            ->whereNotNull('error_code')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca mensagens por erro específico
     */
    public function findByErrorCode(string $errorCode): Collection
    {
        return Message::where('error_code', $errorCode)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete uma mensagem (soft delete)
     */
    public function delete(Message $message): bool
    {
        $deleted = $message->delete();

        Log::info('Message deleted', [
            'id' => $message->id,
            'provider_id' => $message->provider_id
        ]);

        return $deleted;
    }

    /**
     * Restaura uma mensagem deletada
     */
    public function restore(int $id): bool
    {
        $message = Message::withTrashed()->find($id);
        
        if (!$message) {
            return false;
        }

        $restored = $message->restore();

        Log::info('Message restored', [
            'id' => $message->id,
            'provider_id' => $message->provider_id
        ]);

        return $restored;
    }

    /**
     * Busca mensagens de uma conversa (baseado em dois números)
     */
    public function findConversation(string $number1, string $number2): Collection
    {
        return Message::where(function ($query) use ($number1, $number2) {
            $query->where('from', $number1)->where('to', $number2);
        })
        ->orWhere(function ($query) use ($number1, $number2) {
            $query->where('from', $number2)->where('to', $number1);
        })
        ->orderBy('created_at', 'asc')
        ->get();
    }

    /**
     * Estatísticas de mensagens
     */
    public function getStatistics(): array
    {
        return [
            'total' => Message::count(),
            'pending' => Message::where('status', 'pending')->count(),
            'enqueued' => Message::where('status', 'enqueued')->count(),
            'sent' => Message::where('status', 'sent')->count(),
            'delivered' => Message::where('status', 'delivered')->count(),
            'read' => Message::where('status', 'read')->count(),
            'failed' => Message::where('status', 'failed')->count(),
            'today' => Message::whereDate('created_at', Carbon::today())->count(),
            'this_week' => Message::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'this_month' => Message::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count(),
        ];
    }
}