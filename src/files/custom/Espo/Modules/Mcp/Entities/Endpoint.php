<?php
/**LICENSE**/

namespace Espo\Modules\Mcp\Entities;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\ORM\EntityCollection;
use UnexpectedValueException;

class Endpoint extends Entity
{
    public const string ENTITY_TYPE = 'McpEndpoint';

    public const string FIELD_STATUS = 'status';
    public const string FIELD_SLUG = 'slug';
    public const string FIELD_URL = 'url';
    public const string FIELD_PUBLIC_DESCRIPTION = 'publicDescription';

    public const string LINK_USERS = 'users';
    private const string LINK_FEATURES = 'features';

    public const string STATUS_ACTIVE = 'Active';

    public function isActive(): bool
    {
        return $this->get(self::FIELD_STATUS) === self::STATUS_ACTIVE;
    }

    public function getSlug(): string
    {
        return $this->get(self::FIELD_SLUG) ?? throw new UnexpectedValueException("No slug.");
    }

    public function setSlug(string $slug): self
    {
        return $this->set(self::FIELD_SLUG, $slug);
    }

    public function getPublicDescription(): ?string
    {
        return $this->get(self::FIELD_PUBLIC_DESCRIPTION);
    }

    public function setPublicDescription(?string $description): self
    {
        return $this->set(self::FIELD_PUBLIC_DESCRIPTION, $description);
    }

    public function getName(): string
    {
        return $this->get(Field::NAME) ?? '';
    }

    public function setName(string $name): self
    {
        return $this->set(Field::NAME, $name);
    }

    /**
     * @return EntityCollection<Feature>
     */
    public function getFeatures(): EntityCollection
    {
        /** @var EntityCollection<Feature> */
        return $this->relations->getMany(self::LINK_FEATURES);
    }
}
