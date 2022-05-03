<?php


namespace SilverStripe\GraphQL\Config;

use SilverStripe\Config\MergeStrategy\Priority;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;

class Configuration
{
    use Injectable;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * ModelConfiguration constructor.
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    /**
     * Return a setting by dot.separated.syntax
     * @param string|array $path
     * @param mixed $default
     * @return mixed
     * @throws SchemaBuilderException
     */
    public function get($path, $default = null)
    {
        Schema::invariant(
            is_array($path) || is_string($path),
            'get() must be passed an array or string'
        );
        $parts = is_string($path) ? explode('.', $path) : $path;
        $scope = $this->settings;
        foreach ($parts as $part) {
            $scope = $scope[$part] ?? $default;
            if (!is_array($scope)) {
                break;
            }
        }

        return $scope;
    }

    /**
     * @throws SchemaBuilderException
     */
    private function path($path, callable $callback): void
    {
        if (is_string($path)) {
            $path = explode('.', $path ?? '');
        }
        Schema::invariant(
            is_array($path),
            '%s::%s path must be a dot-separated string or array',
            __CLASS__,
            __FUNCTION__
        );
        $scope = &$this->settings;
        foreach ($path as $i => $part) {
            $last = ($i + 1) === sizeof($path ?? []);
            if ($last) {
                $callback($scope, $part);
                return;
            }
            if (!isset($scope[$part])) {
                $scope[$part] = [];
            }
            $scope = &$scope[$part];
        }
    }

    /**
     * @param $path
     * @param $value
     * @return $this
     * @throws SchemaBuilderException
     */
    public function set($path, $value): self
    {
        $this->path($path, function (&$scope, $part) use ($value) {
            $scope[$part] = $value;
        });

        return $this;
    }

    /**
     * @param $path
     * @param $value
     * @return $this
     * @throws SchemaBuilderException
     */
    public function unset($path): self
    {
        $this->path($path, function (&$scope, $part) {
            unset($scope[$part]);
        });

        return $this;
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function apply(array $settings): self
    {
        $this->settings = Priority::mergeArray($settings, $this->settings);

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->settings;
    }
}
