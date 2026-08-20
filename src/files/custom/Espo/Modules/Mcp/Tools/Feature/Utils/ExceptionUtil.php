<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Tools\Feature\Utils;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Language;
use JsonException;
use stdClass;

class ExceptionUtil
{
    public function __construct(
        private Language $defaultLanguage,
    ) {}

    public function getBodyMessage(Conflict|BadRequest|Forbidden $exception): ?string
    {
        $body = $exception->getBody();

        if (!$body) {
            return null;
        }

        try {
            $data = Json::decode($body);
        } catch (JsonException) {
            return null;
        }

        if (!$data instanceof stdClass) {
            return null;
        }

        $messageTranslation = $data->messageTranslation ?? null;

        if (!$messageTranslation instanceof stdClass) {
            return null;
        }

        $label = $messageTranslation->label ?? null;
        $scope = $messageTranslation->scope ?? null;
        $replaceDataRaw = $messageTranslation->data ?? (object) [];

        if (!is_string($label)) {
            return null;
        }

        if ($scope !== null && !is_string($scope)) {
            return null;
        }

        if (!$replaceDataRaw instanceof stdClass) {
            return null;
        }

        $message = $this->defaultLanguage->translateLabel($label, 'messages', $scope ?? 'Global');

        $replaceData = [];

        foreach (get_object_vars($replaceDataRaw) as $k => $v) {
            $replaceData['{' . $k . '}'] = $v;
        }

        return strtr($message, $replaceData);
    }

    public function appendBodyMessage(string $message, Conflict|BadRequest|Forbidden $exception): string
    {
        $part = $this->getBodyMessage($exception);

        if (!$part) {
            return $message;
        }

        return $message . ' ' . $part;
    }
}
