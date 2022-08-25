<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class OrganizationResource
 * @package App\Http\Resources
 * @OA\Schema(
 * )
 */
class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    /**
     * @OA\Property(format="string", title="name", default="Oas36ty", description="Organization name", property="name"),
     * @OA\Property(format="string", title="subdomain", default="oas36ty", description="oas36ty", property="subdomain")
     */
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'subdomain' => $this->subdomain,
        ];
    }
}
