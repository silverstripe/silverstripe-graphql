<?php


namespace SilverStripe\GraphQL\Schema\Encoding\Registries;

use SilverStripe\GraphQL\Schema\Components\AbstractFunction;
use InvalidArgumentException;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ResolverEncoderInterface;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\ResolverEncoderRegistryInterface;

class ResolverEncoderRegistry implements ResolverEncoderRegistryInterface
{
    /**
     * @var ResolverEncoderInterface[]
     */
    protected $encoders = [];

    /**
     * ResolverEncoderRegistry constructor.
     * @param ResolverEncoderInterface[] ...$encoders
     */
    public function __construct(...$encoders)
    {
        $this->setEncoders($encoders);
    }

    /**
     * @param AbstractFunction $resolver
     * @return ResolverEncoderInterface
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
     * @param ResolverEncoderInterface[] $encoders
     */
    public function setEncoders(array $encoders)
    {
        foreach ($encoders as $encoder) {
            if (!$encoder instanceof ResolverEncoderInterface) {
                throw new InvalidArgumentException(sprintf(
                    '%s must be composed with only %s instances',
                    __CLASS__,
                    ResolverEncoderInterface::class
                ));
            }
        }

        $this->encoders = $encoders;
    }
}
