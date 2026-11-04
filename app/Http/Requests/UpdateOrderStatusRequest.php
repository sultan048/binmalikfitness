<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('orders.fulfil');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Staff may move orders through these states; OrderStatus::canTransitionTo() checks the path
            'status' => ['required', 'string', Rule::in([
                OrderStatus::Processing->value,
                OrderStatus::Shipped->value,
                OrderStatus::Delivered->value,
                OrderStatus::Cancelled->value,
            ])],
            'courier_name' => ['nullable', 'string', 'max:80'],
            'tracking_number' => ['nullable', 'string', 'max:80'],
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
