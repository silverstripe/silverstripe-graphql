<?php


namespace SilverStripe\GraphQL\Schema\Type;

use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Interfaces\SchemaValidator;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\ORM\ArrayLib;

/**
 * Abstraction for enum types
 */
class Enum extends Type implements SchemaValidator
{
    private string $name;

    private array $values;

    private ?string $description;

    public function __construct(string $name, array $values, ?string $description = null)
    {
        parent::__construct($name);
        $this->setValues($values);
        $this->setDescription($description);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function getValueList(): array
    {
        $list = [];
        $values = $this->getValues();
        if (!ArrayLib::is_associative($values)) {
            $entries = array_values($values ?? []);
            $values = array_combine($entries ?? [], $entries ?? []);
        }
        foreach ($values as $key => $val) {
            $value = null;
            $description = null;
            if (is_array($val)) {
                Schema::assertValidConfig($val, ['value', 'description']);
                $value = $val['value'];
                $description = $val['description'] ?? null;
            } else {
                $value = $val;
            }
            $list[] = [
                'Key' => static::sanitise($key),
                'Value' => $value,
                'Description' => $description,
            ];
        }

        return $list;
    }

    /**
     * @throws SchemaBuilderException
     */
    public function validate(): void
    {
        Schema::invariant(
            !empty($this->getValueList()),
            'Enum type %s has no values defined',
            $this->getName()
        );
        $rx = '/^[_a-zA-Z][_a-zA-Z0-9]*$/';
        foreach ($this->getValueList() as $item) {
            Schema::invariant(
                preg_match($rx ?? '', $item['Key'] ?? ''),
                'Key "%s" for "%s" is not valid. Must match %s',
                $item['Key'],
                $this->getName(),
                $rx
            );
        }
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @param mixed $val
     */
    public function addValue(string $key, $val = null): self
    {
        if ($val === null) {
            $this->values[$key] = $key;
        } else {
            $this->values[$key] = $val;
        }

        return $this;
    }

    public function removeValue(string $key): self
    {
        unset($this->values[$key]);

        return $this;
    }

    public function getSignature(): string
    {
        $components = [
            $this->getName(),
            $this->values,
            $this->getDescription(),
        ];

        return md5(json_encode($components) ?? '');
    }

    public static function sanitise(string $str): string
    {
        $str = preg_replace('/\s+/', '_', $str ?? '');
        $str = preg_replace('/[^A-Za-z0-9_]/', '', $str ?? '');

        return $str;
    }
}
