<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Guests may check out; cart ownership is enforced by CartService
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            '_idempotency_key' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'string', 'email', 'max:190'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'saved_address_id' => ['nullable', 'integer'],
            'shipping_method' => ['required', 'string', 'in:standard_courier,express_city'],
            'payment_method' => ['required', 'string', 'in:cod,bank_transfer,online_gateway'],
            'customer_notes' => ['nullable', 'string', 'max:500'],
            'save_address' => ['nullable', 'boolean'],
        ];

        // A new delivery address is only required when no saved address is chosen
        if (! $this->filled('saved_address_id')) {
            $rules['line1'] = ['required', 'string', 'max:190'];
            $rules['area'] = ['nullable', 'string', 'max:120'];
            $rules['city'] = ['required', 'string', 'max:80'];
            $rules['province'] = ['required', 'string', 'max:80'];
            $rules['postal_code'] = ['nullable', 'string', 'max:20'];
        }

        return $rules;
    }
}
