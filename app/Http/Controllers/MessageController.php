<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Repository\MessageRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class MessageController extends Controller
{

    private Message $model;
    private MessageRepository $repository;

    public function __construct(Message $messageModel, MessageRepository $messageRepository)
    {
        $this->model = $messageModel;
        $this->repository = $messageRepository;
    }

    /**
     * Busca mensagens com filtros opcionais
     */
    public function find(Request $request)
    {
        try {

            // Valida a estrutura básica do payload
            $validator = Validator::make($request->all(), [
                'provider_id'   => 'sometimes|string',
                'direction'     => 'sometimes|string|in:inbound,outbound',
                'from'          => 'sometimes|string',
                'to'            => 'sometimes|string',
                'type'          => 'sometimes|string',
                'status'        => 'sometimes|string',
                'error_code'    => 'sometimes|string',
                'error_reason'  => 'sometimes|string',
                'created_at'    => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            $result = $this->repository->list($validator->validated());
            
            $return = new MessageResource($result);

            return response()->json($return, 200);
        } catch (\Exception $ex) {
            return response()->json(['message' => $ex->getMessage() ?: 'Algo deu errado'], $ex->getCode() ?: 400);
        }
    }

    /**
     * Busca mensagem por ID
     */
    public function show(int $id)
    {
        try {
            $message = $this->model->findOrFail($id);
            $return = new MessageResource($message);

            return response()->json($return, 200);
        } catch (ModelNotFoundException $ex) {
            return response()->json([
                'message' => 'Registro não encontrado',
                'error' => 'Not Found'
            ], 404);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Ocorreu um erro, contate o suporte',
                'error' => 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Busca mensagem por provider_id
     */
    public function findByProviderId(string $provider_id)
    {
        try {
            $message = $this->repository->findByProviderId($provider_id);

            if (!$message) {
                return response()->json([
                    'message' => 'Registro não encontrado',
                    'error' => 'Not Found'
                ], 404);
            }

            $return = new MessageResource($message);

            return response()->json($return, 200);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => 'Ocorreu um erro, contate o suporte',
                'error' => 'Internal Server Error'
            ], 500);
        }
    }
}
