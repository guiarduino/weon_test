<?php

namespace App\Services;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Repository\MessageRepository;

class MessageService
{

    protected MessageRepository $repository;

    public function __construct(MessageRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function storeIncoming($number, array  $data)
    {
        try {
            $validated = StoreMessageRequest::validate($data);
            if (!$validated || !isset($validated['payload'])) {
                throw new \Exception('Dados inválidos para salvar mensagem');
            }
            if (!is_numeric($number)) {
                throw new \Exception('O número informado não é válido');
            }
            $payload = $validated['payload'];
            $response = $this->repository->store($number, $payload);
            return $response;
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Ocorreu um erro, contate o suporte',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function updateStatus($number, array $data)
    {
        try {
            $validated = StoreMessageRequest::validate($data);
            if (!$validated || !isset($validated['payload'])) {
                throw new \Exception('Dados inválidos para salvar mensagem');
            }
            if (!is_numeric($number)) {
                throw new \Exception('O número informado não é válido');
            }
            $payload = $validated['payload'];
            $response = $this->repository->updateStatus($number, $payload);
            return $response;
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Ocorreu um erro, contate o suporte',
                'error' => $ex->getMessage()
            ], 500);
        }
    }
}
