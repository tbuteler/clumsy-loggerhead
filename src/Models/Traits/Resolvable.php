<?php

namespace Clumsy\Loggerhead\Models\Traits;

trait Resolvable
{
    public $resolved = false;

    abstract protected function getMetaRelationship();

    public function metaArray()
    {
        return $this->getMetaRelationship()->pluck('value', 'key')->toArray();
    }

    public function metaValue($key)
    {
        return array_get($this->metaArray(), $key);
    }

    protected function resolveMeta()
    {
        $attributes = $this->toArray();

        $attributes = array_dot(array_except($attributes, ['pivot', 'activity', 'meta']) + array_get($attributes, 'pivot', []));

        // Attributes passed to translation function is an array of flattened
        // notification information plus "resolved" notification meta
        return $attributes + app('clumsy.loggerhead')->resolveMeta($this->metaArray(), $this);
    }

    public function resolve()
    {
        if (!$this->resolved) {
            $this->meta_attributes = $this->resolveMeta();
            app('clumsy.loggerhead')->resolve($this, $this->meta_attributes);
            $this->resolved = true;
        }

        return $this;
    }

    public function getContentAttribute()
    {
        if (!array_key_exists('content', $this->getAttributes())) {
            $this->resolve();
        }

        return array_get($this->getAttributes(), 'content');
    }

    public function __toString()
    {
        return $this->content;
    }
}
