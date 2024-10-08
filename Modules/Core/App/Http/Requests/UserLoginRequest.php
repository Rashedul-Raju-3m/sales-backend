<?php

namespace Modules\Core\App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {

        switch($this->method())
        {

            case 'POST':
            {
                return [
                    'username' => 'required|string',
                    'password' => 'required|string',
                ];
            }

            case 'PUT':
            case 'PATCH':
            {
                return [
                    'name' => 'required|string',
                    'username' => 'required',
                    'mobile' => 'required|numeric',
                    'email' => 'required|email',
                ];
            }
            default:break;
        }
    }

    public function messages()
    {
        return [
            'username.required' => 'Username field must be required',
            'username.string' => 'Username field must be string',
            'password.required' => 'Password field must be required',
            'password.string' => 'Password field must be string',
        ];
    }
}
