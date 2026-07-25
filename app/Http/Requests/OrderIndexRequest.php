<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends FormRequest
{
    /** @var array<int, string> */
    public const array SORTABLE = ['placed_at', 'total_cents', 'order_number', 'status'];

    /** @var array<int, int> */
    public const array PAGE_SIZES = [10, 20, 50, 100];

    public const int DEFAULT_PAGE_SIZE = 20;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PAGE_SIZES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'The end date must fall on or after the start date.',
            'per_page.in' => 'Choose 10, 20, 50 or 100 rows per page.',
        ];
    }

    public function sort(): string
    {
        return $this->string('sort', 'placed_at')->value();
    }

    /**
     * @return 'asc'|'desc'
     */
    public function direction(): string
    {
        return $this->string('direction')->value() === 'asc' ? 'asc' : 'desc';
    }

    public function perPage(): int
    {
        return $this->integer('per_page', self::DEFAULT_PAGE_SIZE);
    }

    /**
     * Filter state echoed back to the page so every input stays controlled and
     * the URL alone can reproduce the view.
     *
     * @return array<string, string|null>
     */
    public function filters(): array
    {
        return [
            'q' => $this->query('q'),
            'status' => $this->query('status'),
            'date_from' => $this->query('date_from'),
            'date_to' => $this->query('date_to'),
            'sort' => $this->sort(),
            'direction' => $this->direction(),
            'per_page' => (string) $this->perPage(),
        ];
    }
}
