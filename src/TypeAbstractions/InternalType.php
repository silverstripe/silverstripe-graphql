<?php


namespace SilverStripe\GraphQL\TypeAbstractions;


class InternalType
{
    const TYPE_ID = 'ID';

    const TYPE_INT = 'Int';

    const TYPE_STRING = 'String';

    const TYPE_FLOAT = 'Float';

    const TYPE_BOOLEAN = 'Boolean';

    /**
     * @return TypeReference
     */
    public static function id()
    {
        return new TypeReference(self::TYPE_ID);
    }

    /**
     * @return TypeReference
     */
    public static function int()
    {
        return new TypeReference(self::TYPE_INT);
    }

    /**
     * @return TypeReference
     */
    public static function string()
    {
        return new TypeReference(self::TYPE_STRING);
    }

    /**
     * @return TypeReference
     */
    public static function float()
    {
        return new TypeReference(self::TYPE_FLOAT);
    }

    /**
     * @return TypeReference
     */
    public static function boolean()
    {
        return new TypeReference(self::TYPE_BOOLEAN);
    }

    /*
     * @return array
     */
    public static function getAll()
    {
        return [
            self::TYPE_BOOLEAN,
            self::TYPE_STRING,
            self::TYPE_FLOAT,
            self::TYPE_INT,
            self::TYPE_ID,
        ];
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function exists($type)
    {
        return in_array($type, self::getAll());
    }

}