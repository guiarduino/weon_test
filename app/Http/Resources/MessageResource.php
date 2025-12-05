<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="KanbanReportCardCount",
 *     type="object",
 *     title="Kanban Report Card Count",
 *     description="Kanban report card count model",
 *     @OA\Property(property="column_name", type="string", description="Name of the column"),
 *     @OA\Property(property="total", type="integer", description="Total number of cards in the column")
 * )
 */
class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
