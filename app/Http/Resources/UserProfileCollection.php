<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;


use App\Models\Ratting;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserProfileCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rating=Ratting::where("to_user_id",$this->id)->avg("rating_star");
        return [
            'id'                 => $this->id,
            'unique_id'          => $this->unique_id ?? '',
            'email'              => $this->email ?? '',
            'login_type'         => $this->login_type ?? '',
            'type'               => $this->type ?? '',
            'social_media_id'    => $this->social_media_id ?? '',
            'mobile'             => $this->mobile ?? '',
            'status'             => $this->status,
            'profile_pic'        => $this->profile_pic ?? '',
            'profile_complete'   => $this->profile_complete,
            'city'               => $this->city,
            'gender'             => ucfirst($this->gender),
            'language'           => @$this->language->title,
            'rating'           => @$this->rating,
            'visible_status'     => @$this->visible_status,
            'twilio_token'       => $this->twilio_token
        ];
    }
}
