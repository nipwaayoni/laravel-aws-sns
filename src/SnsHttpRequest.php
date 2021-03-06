<?php

namespace Nipwaayoni\SnsHandler;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SnsHttpRequest
 * @package Nipwaayoni\SnsHandler
 *
 * @codeCoverageIgnore
 */
class SnsHttpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function jsonContent(): string
    {
        return $this->content;
    }
}
