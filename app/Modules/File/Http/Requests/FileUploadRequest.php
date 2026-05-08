<?php
declare(strict_types=1);

namespace App\Modules\File\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf,docx|max:10240'
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File is required',
            'file.file' => 'Invalid file',
            'file.mimes' => 'Only PDF and DOCX files are allowed',
            'file.max' => 'File size must not exceed 10MB'
        ];
    }
}
