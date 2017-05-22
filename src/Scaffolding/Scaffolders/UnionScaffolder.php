<?php

namespace SilverStripe\GraphQL\Scaffolding\Scaffolders;

use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffolderInterface;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\GraphQL\Scaffolding\Util\ScaffoldingUtil;
use SilverStripe\GraphQL\Manager;
use GraphQL\Type\Definition\UnionType;
use \Exception;

class UnionScaffolder implements ScaffolderInterface
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @param string $baseType
     * @param array  $types
     */
    public function __construct($name, $types = [])
    {
        $this->name = $name;
        $this->types = $types;
    }

    /**
     * @param Manager $manager
     * @return mixed
     */
    public function scaffold(Manager $manager)
    {
        $types = $this->types;
        return new UnionType([
            'name' => $this->name,
            'types' => array_map(function ($item) use ($manager) {
                return $manager->getType($item);
            }, $types),
            'resolveType' => function ($obj) use ($manager) {
                if (!$obj instanceof DataObject) {
                    throw new Exception(sprintf(
                        'Type with class %s is not a DataObject',
                        get_class($obj)
                    ));
                }
                $ancestry = array_reverse(ClassInfo::ancestry($obj));
                foreach ($ancestry as $class) {
                    if ($class === DataObject::class) {
                        throw new Exception(sprintf(
                            'There is no type defined for %s, and none of its ancestors are defined.',
                            get_class($obj)
                        ));
                    }

                    $typeName = ScaffoldingUtil::typeNameForDataObject($class);
                    if ($manager->hasType($typeName)) {
                        return $manager->getType($typeName);
                    }
                }
            }
        ]);
    }
}
