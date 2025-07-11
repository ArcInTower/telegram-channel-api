<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;

class MessageResource extends BaseResource
{
    /**
     * Transform the resource into an array following JSON:API 1.1 spec.
     */
    public function toArray(Request $request): array
    {
        $data = $this->extractData();

        return [
            'data' => [
                'type' => 'channel-message',
                'id' => $data['channel'] ?? '',
                'attributes' => [
                    'last_message_id' => $data['last_message_id'] ?? null,
                ],
            ],
            'meta' => $this->buildMeta(),
            'jsonapi' => [
                'version' => '1.1',
            ],
        ];
    }
}
