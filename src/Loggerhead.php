<?php

namespace Clumsy\Loggerhead;

use Closure;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Arrayable;
use Clumsy\Loggerhead\Models\Activity;
use Clumsy\Loggerhead\Models\Notification;
use Clumsy\Loggerhead\Models\ActivityMeta;
use Clumsy\Loggerhead\Notifier;
use Carbon\Carbon;

class Loggerhead
{
    protected $notifier;

    protected $generalTitleResolver;
    protected $defaultTitle;

    protected $resolvers = [];

    public function __construct(Notifier $notifier)
    {
        $this->notifier = $notifier;

        $this->generalTitleResolver = function () {
            return $this->defaultTitle();
        };
    }

    public function log($slug, $meta = [])
    {
        if (is_object($meta) && ($meta instanceof Arrayable)) {
            $meta = $meta->toArray();
        } else {
            $meta = (array) $meta;
        }

        $activity = Activity::create([
            'slug'      => $slug,
            'timestamp' => Carbon::now(),
        ]);

        if (count($meta)) {
            $metaModels = [];
            foreach ($meta as $key => $value) {
                $key = is_numeric($key) && $key == 0 && count($meta) === 1 ? null : $key;
                $metaModels[] = new ActivityMeta(compact('key', 'value'));
            }

            $activity->meta()->saveMany($metaModels);
        }

        event("clumsy.logged: {$activity->slug}", [$activity]);

        return $activity;
    }

    public function deleteByMeta($metaKey, $metaValue = null)
    {
        Activity::whereIn('id', function ($query) use ($metaKey, $metaValue) {

            $query->select('activity_id')
                  ->from(with(new ActivityMeta)->getTable())
                  ->where('key', $metaKey);

            if ($metaValue) {
                $query->where('value', $metaValue);
            }
        })
        ->delete();
    }

    public function setGeneralTitleResolver(Closure $callback)
    {
        $this->generalTitleResolver = $callback;
    }

    public function setDefaultTitle($title)
    {
        $this->defaultTitle = $title;
    }

    public function defaultTitle()
    {
        return $this->defaultTitle ? $this->defaultTitle : trans('clumsy/loggerhead::content.default-title');
    }

    public function getItemCategory($item)
    {
        return get_class($item) === Activity::class ? 'activity' : 'notification';
    }

    public function setResolver($type, $category, $slug, Closure $callback)
    {
        array_set($this->resolvers, "{$type}.{$category}.{$slug}", $callback);
    }

    public function metaResolver($slugs, Closure $callback, $category = 'activity')
    {
        foreach ((array)$slugs as $slug) {
            $this->setResolver('meta', $category, $slug, $callback);
        }
    }

    public function titleResolver($slugs, Closure $callback, $category = 'activity')
    {
        foreach ((array)$slugs as $slug) {
            $this->setResolver('title', $category, $slug, $callback);
        }
    }

    public function resolver($slugs, Closure $callback, $category = 'activity')
    {
        foreach ((array)$slugs as $slug) {
            $this->setResolver('content', $category, $slug, $callback);
        }
    }

    public function notificationMetaResolver($slugs, Closure $callback)
    {
        $this->metaResolver($slugs, $callback, 'notification');
    }

    public function notificationTitleResolver($slugs, Closure $callback)
    {
        $this->titleResolver($slugs, $callback, 'notification');
    }

    public function notificationResolver($slugs, Closure $callback)
    {
        $this->resolver($slugs, $callback, 'notification');
    }

    public function getResolver($type, $item)
    {
        $category = $this->getItemCategory($item);

        $resolver = array_get($this->resolvers, "{$type}.{$category}.{$item->slug}");

        if (!$resolver) {
            // If no category-specific resolver exists, use the base activity resolver (if any) as fallback
            return array_get($this->resolvers, "{$type}.activity.{$item->slug}");
        }

        return $resolver;
    }

    public function hasResolver($type, $item)
    {
        return $this->getResolver($type, $item) instanceof Closure;
    }

    public function getView($item)
    {
        $category = $this->getItemCategory($item);

        $possibleViews = [
            "clumsy/loggerhead::{$category}.{$item->slug}",
            "clumsy/loggerhead::activity.{$item->slug}",
            "clumsy/loggerhead::{$category}",
            "clumsy/loggerhead::activity",
        ];

        foreach ($possibleViews as $viewSlug) {
            if (view()->exists($viewSlug)) {
                return view($viewSlug)->with($item->meta_attributes);
            }
        }

        return null;
    }

    public function hasView($item)
    {
        return !is_null($this->getView($item));
    }

    public function resolveTitle($item)
    {
        $callback = $this->hasResolver('title', $item)
                    ? $this->getResolver('title', $item)
                    : $this->generalTitleResolver;

        return $callback($item);
    }

    public function resolve(&$item)
    {
        if ($this->hasResolver('content', $item)) {
            $callback = $this->getResolver('content', $item);
            $item->content = $callback($item);
        } elseif ($this->hasView($item)) {
            $item->content = $this->getView($item)->render();
        } else {
            $item->content = trans("clumsy/loggerhead::content.{$item->slug}", $item->meta_attributes);
        }
    }

    public function resolveMeta($meta, $item)
    {
        if ($this->hasResolver('meta', $item)) {

            $callback = $this->getResolver('meta', $item);
            return $callback($meta, $item);
        }

        return $meta;
    }

    public function notifier()
    {
        return $this->notifier;
    }
}
