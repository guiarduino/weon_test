<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator;

class StoreMessageRequest
{
    public static function validate(array $data)
    {
        $validator = Validator::make($data, [
            'payload.id'               => 'required|string',
            'payload.source'           => 'required|string',
            'payload.type'             => 'required|string',
            'payload.payload.text'     => 'nullable|string',
            'payload.sender.phone'     => 'required|string',
        ]);

        if ($validator->fails()) {
            abort(422, json_encode($validator->errors()->toArray()));
        }

        return $validator->validated();
    }
}
