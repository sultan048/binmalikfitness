<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{10,20}$/'],
            'fitness_goal' => ['required', 'string', 'max:64'],
            'height_cm' => ['nullable', 'integer', 'min:100', 'max:250'],
            'weight_kg' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'activity_level' => ['nullable', 'string', 'max:64'],
            'experience_level' => ['nullable', 'string', 'max:64'],
            'equipment_access' => ['nullable', 'string', 'max:64'],
        ];
    }
}
