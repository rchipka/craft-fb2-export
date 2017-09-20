<?php
namespace Craft;

class FB2ExportPlugin extends BasePlugin
{
    public function getName()
    {
         return Craft::t('FB2 Export');
    }

    public function getVersion()
    {
        return '0.0.1';
    }

    public function getDeveloper()
    {
        return 'Robbie Chipka';
    }

    public function getDeveloperUrl()
    {
        return 'http://github.com/rchipka';
    }

    public function hasCpSection()
    {
        return true;
    }

    public function init() {
    }

    public function registerCpRoutes() {
        return array(
            'fb2-export/export' => ['action' => 'FB2Export/Export/Export'],
        );
    }

}
