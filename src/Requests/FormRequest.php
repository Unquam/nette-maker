<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Requests;

use Nette\Http\IRequest;
use Unquam\NetteMaker\Exceptions\ValidationException;

abstract class FormRequest
{
    /** @var IRequest */
    protected $httpRequest;

    /** @var array<string, mixed> */
    protected $requestData = [];

    public function __construct(IRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
        $this->requestData = $this->resolveRequestInput();
    }

    /**
     * Determine if the user is authorized to make this request.
     * Defaults to true so developers don't have to write it every time.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string|array<string>>
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors override mapping layer.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Safely run validations routines manually inside Presenter scopes.
     *
     * @return array<string, mixed> Filtered validated inputs array layout
     * @throws ValidationException
     */
    public function validate(): array
    {
        if (!$this->authorize()) {
            throw new ValidationException('This action is unauthorized.', 403);
        }

        $validator = new RuleValidator();

        $failedErrors = $validator->validate($this->requestData, $this->rules(), $this->messages());

        if (!empty($failedErrors)) {
            $exception = new ValidationException('The given data was invalid.', 422);
            $exception->setErrors($failedErrors);
            throw $exception;
        }

        $validated = [];
        foreach (array_keys($this->rules()) as $validField) {
            if (array_key_exists($validField, $this->requestData)) {
                $validated[$validField] = $this->requestData[$validField];
            }
        }

        return $validated;
    }

    /**
     * Consolidated input capture extracting standard POST, file uploads, or raw JSON streams.
     *
     * @return array<string, mixed>
     */
    private function resolveRequestInput(): array
    {
        $post = $this->httpRequest->getPost();
        $files = $this->httpRequest->getFiles();

        if (!empty($files)) {
            $post = array_merge($post, $this->resolveFiles($files));
        }

        if (empty($post)) {
            $rawBody = $this->httpRequest->getRawBody();
            if ($rawBody !== null && $rawBody !== '') {
                $decoded = json_decode($rawBody, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return (array) $post;
    }

    /**
     * Recursively process Nette nested files tree array formatting fields for Validator constraints.
     */
    private function resolveFiles(array $files): array
    {
        $result = [];

        foreach ($files as $key => $fileObj) {
            if ($fileObj instanceof \Nette\Http\FileUpload) {
                // Ignore empty file upload slots to prevent throwing false required/size faults
                if ($fileObj->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $result[$key] = [
                    'name' => $fileObj->getName(),
                    'type' => $fileObj->getContentType(),
                    'size' => $fileObj->getSize(),
                    'tmp'  => $fileObj->getTemporaryFile()
                ];
            } elseif (is_array($fileObj)) {
                // Seamlessly handle multi-file arrays fields navigation wrapper loop mapping
                $result[$key] = $this->resolveFiles($fileObj);
            }
        }

        return $result;
    }
}