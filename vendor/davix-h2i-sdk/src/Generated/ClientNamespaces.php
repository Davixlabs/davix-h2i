<?php
// GENERATED from the Davix H2I API contract 3.0.0. Do not edit.
// Adds one accessor per top-level API namespace: $h2i->images()->resize([...]).
namespace Davix\H2I\Generated;

trait ClientNamespaces
{
    /** @return \Davix\H2I\Api\ApiAccount */
    public function account()
    {
        return new \Davix\H2I\Api\ApiAccount($this);
    }

    /** @return \Davix\H2I\Api\ApiAi */
    public function ai()
    {
        return new \Davix\H2I\Api\ApiAi($this);
    }

    /** @return \Davix\H2I\Api\ApiCertificates */
    public function certificates()
    {
        return new \Davix\H2I\Api\ApiCertificates($this);
    }

    /** @return \Davix\H2I\Api\ApiData */
    public function data()
    {
        return new \Davix\H2I\Api\ApiData($this);
    }

    /** @return \Davix\H2I\Api\ApiDocuments */
    public function documents()
    {
        return new \Davix\H2I\Api\ApiDocuments($this);
    }

    /** @return \Davix\H2I\Api\ApiFiles */
    public function files()
    {
        return new \Davix\H2I\Api\ApiFiles($this);
    }

    /** @return \Davix\H2I\Api\ApiFormats */
    public function formats()
    {
        return new \Davix\H2I\Api\ApiFormats($this);
    }

    /** @return \Davix\H2I\Api\ApiImages */
    public function images()
    {
        return new \Davix\H2I\Api\ApiImages($this);
    }

    /** @return \Davix\H2I\Api\ApiJobs */
    public function jobs()
    {
        return new \Davix\H2I\Api\ApiJobs($this);
    }

    /** @return \Davix\H2I\Api\ApiKeys */
    public function keys()
    {
        return new \Davix\H2I\Api\ApiKeys($this);
    }

    /** @return \Davix\H2I\Api\ApiLimits */
    public function limits()
    {
        return new \Davix\H2I\Api\ApiLimits($this);
    }

    /** @return \Davix\H2I\Api\ApiMeta */
    public function meta()
    {
        return new \Davix\H2I\Api\ApiMeta($this);
    }

    /** @return \Davix\H2I\Api\ApiNotifications */
    public function notifications()
    {
        return new \Davix\H2I\Api\ApiNotifications($this);
    }

    /** @return \Davix\H2I\Api\ApiPdfs */
    public function pdfs()
    {
        return new \Davix\H2I\Api\ApiPdfs($this);
    }

    /** @return \Davix\H2I\Api\ApiPresets */
    public function presets()
    {
        return new \Davix\H2I\Api\ApiPresets($this);
    }

    /** @return \Davix\H2I\Api\ApiRegistry */
    public function registry()
    {
        return new \Davix\H2I\Api\ApiRegistry($this);
    }

    /** @return \Davix\H2I\Api\ApiRender */
    public function render()
    {
        return new \Davix\H2I\Api\ApiRender($this);
    }

    /** @return \Davix\H2I\Api\ApiSubscription */
    public function subscription()
    {
        return new \Davix\H2I\Api\ApiSubscription($this);
    }

    /** @return \Davix\H2I\Api\ApiTemplates */
    public function templates()
    {
        return new \Davix\H2I\Api\ApiTemplates($this);
    }

    /** @return \Davix\H2I\Api\ApiUsage */
    public function usage()
    {
        return new \Davix\H2I\Api\ApiUsage($this);
    }

}
