<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Order access (session grant, legacy token or policy) is checked in the controller
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transaction_reference' => ['required', 'string', 'max:120'],
            'sender_bank_name' => ['required', 'string', 'max:120'],
            'sender_account_last4' => ['nullable', 'string', 'max:16'],
            'receipt_file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }
}
