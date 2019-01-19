<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Registries;

use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use InvalidArgumentException;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\FunctionEncoderInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\FunctionEncoderRegistryInterface;

class ResolverEncoderRegistry implements FunctionEncoderRegistryInterface
{
    /**
     * @var FunctionEncoderInterface[]
     */
    protected $encoders = [];

    /**
     * ResolverEncoderRegistry constructor.
     * @param FunctionEncoderInterface[] ...$encoders
     */
    public function __construct(...$encoders)
    {
        $this->setEncoders($encoders);
    }

    /**
     * @param AbstractFunction $resolver
     * @return FunctionEncoderInterface
     */
    public function getEncoderForResolver(AbstractFunction $resolver)
    {
        foreach ($this->encoders as $encoder) {
            if ($encoder->appliesTo($resolver)) {
                return $encoder;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'No encoder found for resolver %s',
            get_class($resolver)
        ));
    }

    /**
     * @param FunctionEncoderInterface[] $encoders
     */
    public function setEncoders(array $encoders)
    {
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof FunctionEncoderInterface) {
                throw new InvalidArgumentException(sprintf(
                    '%s must be composed with only %s instances',
                    __CLASS__,
                    FunctionEncoderInterface::class
                ));
            }
        }

        $this->encoders = $encoders;
    }
}
