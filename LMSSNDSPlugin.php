<?php

/**
 * LMSSNDSPlugin
 * 
 * @author Łukasz Kopiszka <lukasz@alfa-system.pl>
 */
class LMSSNDSPlugin extends LMSPlugin
{
    const PLUGIN_NAME = 'LMS SNDS API plugin';
    const PLUGIN_DESCRIPTION = 'Integration with SNDS API.';
    const PLUGIN_AUTHOR = 'Łukasz Kopiszka &lt;lukasz@alfa-system.pl&gt;';
    const PLUGIN_DIRECTORY_NAME = 'LMSSNDSPlugin';

    public function registerHandlers()
    {
        $this->handlers = array(
            'smarty_initialized' => array(
                'class' => 'SNDSHandler',
                'method' => 'smartySNDS'
            ),
            'modules_dir_initialized' => array(
                'class' => 'SNDSHandler',
                'method' => 'modulesDirSNDS'
            ),
            'welcome_before_module_display' => array(
                'class' => 'SNDSHandler',
                'method' => 'welcomeSNDS'
            ),
            'access_table_initialized' => array(
                'class' => 'SNDSHandler',
                'method' => 'accessTableInit'
            ),
            'nodeinfo_before_display' => array(
                'class' => 'SNDSHandler',
                'method' => 'nodeInfoBeforeDisplay'
            )            
        );
    }
}
