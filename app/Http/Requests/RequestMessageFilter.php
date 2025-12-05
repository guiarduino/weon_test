<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator;

class RequestMessageFilter
{
    public static function validate(array $data)
    {
        // Se nenhum filtro foi enviado, retorna vazio
        if (empty($data)) {
            return [];
        }

        $validator = Validator::make($data, [
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
            abort(422, json_encode($validator->errors()->toArray()));
        }

        return $validator->validated();
    }
}
