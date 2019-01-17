<?php


namespace SilverStripe\GraphQL\Storage\Encode;


use SilverStripe\GraphQL\TypeAbstractions\ResolverAbstraction;
use SilverStripe\GraphQL\TypeAbstractions\TypeAbstraction;
use InvalidArgumentException;

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
     * @param ResolverAbstraction
     * @return ResolverEncoderInterface
     */
    public function getEncoderForResolver(ResolverAbstraction $type)
    {
        foreach ($this->encoders as $encoder) {
            if ($encoder->appliesTo($type)) {
                return $encoder;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'No encoder found for resolver %s',
            get_class($type)
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