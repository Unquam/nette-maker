<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Requests;

use Nette\Http\IRequest;
use Nette\Database\Explorer;
use Unquam\NetteMaker\Exceptions\ValidationException;

abstract class FormRequest
{
    /** @var IRequest */
    protected $httpRequest;

    /** @var Explorer|null */
    protected $explorer;

    /** @var array<string, mixed> */
    protected $requestData = [];

    public function __construct(IRequest $httpRequest, ?Explorer $explorer = null)
    {
        $this->httpRequest = $httpRequest;
        $this->explorer = $explorer;
        $this->requestData = $this->resolveRequestInput();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors override mapping layer.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Safely run validations routines manually inside Presenter scopes.
     */
    public function validate(): array
    {
        if (!$this->authorize()) {
            throw new ValidationException('This action is unauthorized.', 403);
        }

        // Pass Explorer database service instance into the validator engine
        $validator = new RuleValidator($this->explorer);
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

    private function resolveFiles(array $files): array
    {
        $result = [];
        foreach ($files as $key => $fileObj) {
            if ($fileObj instanceof \Nette\Http\FileUpload) {
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
                $result[$key] = $this->resolveFiles($fileObj);
            }
        }
        return $result;
    }
}