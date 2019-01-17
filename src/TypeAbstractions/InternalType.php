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
     * @return ReferentialTypeAbstraction
     */
    public static function id()
    {
        return new ReferentialTypeAbstraction(self::TYPE_ID);
    }

    /**
     * @return ReferentialTypeAbstraction
     */
    public static function int()
    {
        return new ReferentialTypeAbstraction(self::TYPE_INT);
    }

    /**
     * @return ReferentialTypeAbstraction
     */
    public static function string()
    {
        return new ReferentialTypeAbstraction(self::TYPE_STRING);
    }

    /**
     * @return ReferentialTypeAbstraction
     */
    public static function float()
    {
        return new ReferentialTypeAbstraction(self::TYPE_FLOAT);
    }

    /**
     * @return ReferentialTypeAbstraction
     */
    public static function boolean()
    {
        return new ReferentialTypeAbstraction(self::TYPE_BOOLEAN);
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