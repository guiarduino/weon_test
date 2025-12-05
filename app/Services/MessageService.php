<?php

namespace App\Services;

use App\Repository\MessageRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class MessageService
{
    protected MessageRepository $repository;

    public function __construct(MessageRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Armazena mensagem recebida via webhook
     */
    public function storeIncoming(string $number, array $data): array
    {
        try {
            if (!is_numeric($number)) {
                throw new \Exception('O número informado não é válido');
            }

            // Valida a estrutura básica do payload
            $validator = Validator::make($data, [
                'type' => 'required|string|in:message,message-event',
                'payload' => 'required|array',
                'payload.id' => 'required|string',
                'payload.type' => 'required|string',
                'payload.source' => 'required_if:type,message|string',
                'payload.payload' => 'required|array',
                'payload.destination' => 'required_if:type,message-event|string',
                'payload.gsId' => 'nullable|string',
                'timestamp' => 'nullable|integer',
                'app' => 'nullable|string',
                'version' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                Log::warning('Webhook validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'payload' => $data,
                    'number' => $number
                ]);
                
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            $validated = $validator->validated();
            $mainType = $validated['type'];
            $payload = $validated['payload'];

            // Validação adicional: gsId é obrigatório para eventos 'read' e 'failed'
            if ($mainType === 'message-event' && in_array($payload['type'] ?? null, ['read', 'failed'])) {
                if (!isset($payload['gsId']) || empty($payload['gsId'])) {
                    Log::warning('gsId missing for event type', [
                        'event_type' => $payload['type'],
                        'number' => $number
                    ]);
                    throw ValidationException::withMessages([
                        'payload.gsId' => ["gsId é requerido para evento {$payload['type']}"]
                    ]);
                }
            }

            if ($mainType === 'message') {
                // Mensagem recebida (inbound)
                $result = $this->handleIncomingMessage($payload, $data, $number);
            } else {
                // Evento de status (message-event)
                $eventType = $payload['type'];
                $result = $this->processEventType($eventType, $payload, $data, $number);
            }

            Log::info('Message webhook processed successfully', [
                'main_type' => $mainType,
                'event_type' => $payload['type'] ?? 'unknown',
                'message_id' => $payload['id'] ?? $payload['gsId'] ?? 'unknown',
                'number' => $number
            ]);

            return [
                'success' => true,
                'type' => $mainType,
                'event_type' => $payload['type'] ?? null,
                'data' => $result
            ];

        } catch (ValidationException $e) {
            Log::error('Validation error in storeIncoming', [
                'errors' => $e->errors(),
                'number' => $number,
                'data' => $data
            ]);
            
            throw $e;

        } catch (\Exception $e) {
            Log::error('Error in storeIncoming', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'number' => $number,
                'data' => $data
            ]);
            
            throw new \Exception('Ocorreu um erro ao processar a mensagem: ' . $e->getMessage());
        }
    }

    /**
     * Manipula mensagem recebida (inbound) - type: "message"
     */
    private function handleIncomingMessage(array $payload, array $fullData, string $number): array
    {
        $messageId = $payload['id'];
        $source = $payload['source'];
        $messageType = $payload['type'];
        $messagePayload = $payload['payload'];
        $sender = $payload['sender'] ?? [];

        $bodyContent = $this->extractMessageContent($messageType, $messagePayload);

        $message = $this->repository->createOrUpdate([
            'provider_id' => $messageId,
            'direction' => 'inbound',
            'from' => $source,
            'to' => $number,
            'type' => $messageType,
            'status' => 'received',
            'body' => json_encode([
                'content' => $bodyContent,
                'sender' => $sender,
                'timestamp' => $fullData['timestamp'] ?? Carbon::now()->timestamp * 1000,
                'app' => $fullData['app'] ?? null,
                'raw_payload' => $messagePayload,
            ]),
        ]);

        Log::info('Incoming message saved', [
            'message_id' => $messageId,
            'source' => $source,
            'type' => $messageType,
            'database_id' => $message->id
        ]);

        return [
            'action' => 'incoming_message',
            'message_id' => $messageId,
            'source' => $source,
            'message_type' => $messageType,
            'database_id' => $message->id,
            'sender_name' => $sender['name'] ?? null,
        ];
    }

    /**
     * Extrai o conteúdo da mensagem baseado no tipo
     */
    private function extractMessageContent(string $type, array $payload): mixed
    {
        switch ($type) {
            case 'text':
                return $payload['text'] ?? null;
            
            case 'image':
                return [
                    'url' => $payload['url'] ?? null,
                    'caption' => $payload['caption'] ?? null,
                    'mime_type' => $payload['mime_type'] ?? null,
                ];
            
            case 'audio':
            case 'video':
            case 'document':
                return [
                    'url' => $payload['url'] ?? null,
                    'mime_type' => $payload['mime_type'] ?? null,
                    'filename' => $payload['filename' ] ?? null,
                ];
            
            case 'location':
                return [
                    'latitude' => $payload['latitude'] ?? null,
                    'longitude' => $payload['longitude'] ?? null,
                    'name' => $payload['name' ] ?? null,
                    'address' => $payload['address'] ?? null,
                ];
            
            default:
                return $payload;
        }
    }

    /**
     * Processa o evento baseado no tipo
     */
    private function processEventType(string $eventType, array $payload, array $fullData, string $number): array
    {
        switch ($eventType) {
            case 'enqueued':
                return $this->handleEnqueued($payload, $fullData, $number);
                
            case 'read':
                return $this->handleRead($payload, $fullData, $number);
                
            case 'failed':
                return $this->handleFailed($payload, $fullData, $number);
                
            default:
                throw new \Exception("Tipo de evento desconhecido: {$eventType}");
        }
    }

    /**
     * Manipula evento de enfileiramento (enqueued) - message-event
     */
    private function handleEnqueued(array $payload, array $fullData, string $number): array
    {
        $messageId = $payload['id'];
        $destination = $payload['destination'];
        $whatsappMessageId = $payload['payload']['whatsappMessageId'] ?? null;
        $messageType = $payload['payload']['type'] ?? 'session';

        $message = $this->repository->createOrUpdate([
            'provider_id' => $messageId,
            'direction' => 'outbound',
            'from' => $number,
            'to' => $destination,
            'type' => $messageType,
            'status' => 'enqueued',
            'body' => json_encode([
                'whatsappMessageId' => $whatsappMessageId,
                'timestamp' => $fullData['timestamp'] ?? Carbon::now()->timestamp * 1000,
                'app' => $fullData['app'] ?? null,
            ]),
        ]);

        Log::info('Message enqueued', [
            'message_id' => $messageId,
            'destination' => $destination,
            'database_id' => $message->id
        ]);

        return [
            'action' => 'enqueued',
            'message_id' => $messageId,
            'destination' => $destination,
            'database_id' => $message->id
        ];
    }

    /**
     * Manipula evento de leitura (read)
     */
    private function handleRead(array $payload, array $fullData, string $number): array
    {
        $gsId = $payload['gsId'];
        $readTimestamp = $payload['payload']['ts'] ?? null;
        
        $message = $this->repository->findByProviderId($gsId);
        
        if (!$message) {
            throw new \Exception("Mensagem com provider_id {$gsId} não encontrada");
        }

        $bodyData = json_decode($message->body, true) ?? [];
        $bodyData['read_at'] = $readTimestamp;
        
        $this->repository->update($message, [
            'status' => 'read',
            'body' => json_encode($bodyData)
        ]);

        return [
            'action' => 'read',
            'gs_id' => $gsId,
            'read_at' => $readTimestamp,
            'database_id' => $message->id
        ];
    }

    /**
     * Manipula evento de falha (failed)
     */
    private function handleFailed(array $payload, array $fullData, string $number): array
    {
        $gsId = $payload['gsId'];
        $errorCode = $payload['payload']['code'] ?? null;
        $errorReason = $payload['payload']['reason'] ?? null;
        
        $message = $this->repository->findByProviderId($gsId);
        
        if (!$message) {
            throw new \Exception("Mensagem com provider_id {$gsId} não encontrada");
        }

        $this->repository->update($message, [
            'status' => 'failed',
            'error_code' => (string) $errorCode,
            'error_reason' => $errorReason
        ]);

        return [
            'action' => 'failed',
            'gs_id' => $gsId,
            'error_code' => $errorCode,
            'error_reason' => $errorReason,
            'database_id' => $message->id
        ];
    }
}