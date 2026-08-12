<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Mcp\JsonSchemaValidator;

use Espo\Modules\Mcp\Tools\Mcp\Exceptions\InvalidParamsError;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootObjectSchema;
use Espo\Modules\Mcp\Tools\Mcp\Schema\General\RootSchema;
use Espo\Modules\Mcp\Vendor\Opis\JsonSchema\Errors\ErrorFormatter;
use Espo\Modules\Mcp\Vendor\Opis\JsonSchema\Validator as OpisValidator;

class Validator
{
    /**
     * @throws InvalidParamsError
     */
    public function assert(RootObjectSchema|RootSchema $schema, mixed $data): void
    {
        $validator = (new OpisValidator());

        $result = $validator->validate($data, $schema->jsonSerialize());

        if ($result->isValid()) {
            return;
        }

        $errorMessage = $result->error() !== null ?
            (new ErrorFormatter())->formatErrorMessage($result->error()) :
            null;

        $message = "JSON Schema validation failure.";

        if ($errorMessage) {
            $message .= ' ' . $errorMessage;
        }

        throw new InvalidParamsError($message);
    }
}
