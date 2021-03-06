<?php
namespace App\Validators;

use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

class RespectValidator
{
    protected $errors;
    protected $inputs;

    public function validate(Request $request, array $rules)
    {
        $data = $request->getParsedBody();
        foreach ($rules as $field => $rule) {
            try {
                $this->inputs[$field] = $data[$field];
                $rule->setName(ucfirst($field))->assert($data[$field]);
            } catch (NestedValidationException $e) {
                $this->errors[$field] = $e->getMessages();
            }
        }
        return $this;
    }

    public function failed()
    {
        return !empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getInputs()
    {
        return $this->inputs;
    }
}