<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Registries;

use SilverStripe\GraphQL\Schema\Components\AbstractType;
use InvalidArgumentException;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeEncoderInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\TypeEncoderRegistryInterface;

class TypeEncoderRegistry implements TypeEncoderRegistryInterface
{
    /**
     * @var TypeEncoderInterface[]
     */
    protected $encoders = [];

    /**
     * TypeEncoderRegistry constructor.
     * @param TypeEncoderInterface[] ...$encoders
     */
    public function __construct(...$encoders)
    {
        $this->setEncoders($encoders);
    }

    /**
     * @param AbstractType $type
     * @return TypeEncoderInterface
     */
    public function getEncoderForType(AbstractType $type)
    {
        foreach ($this->encoders as $encoder) {
            if ($encoder->appliesTo($type)) {
                return $encoder;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'No encoder found for type %s',
            get_class($type)
        ));
    }

    /**
     * @param TypeEncoderInterface[] $encoders
     */
    public function setEncoders(array $encoders)
    {
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof TypeEncoderInterface) {
                throw new InvalidArgumentException(sprintf(
                    '%s must be composed with only %s instances',
                    __CLASS__,
                    TypeEncoderInterface::class
                ));
            }
        }

        $this->encoders = $encoders;
    }
}
