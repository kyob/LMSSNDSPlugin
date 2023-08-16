<?php

class SNDSHandler
{

    function convertDate($dateString)
    {
        $formats = array(
            'Y-m-d H:i:s',
            'm/d/Y H:i:s',
        );

        foreach ($formats as $format) {
            try {
                $dateTime = new DateTime($dateString, new DateTimeZone('UTC'));
                return $dateTime->format($format);
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    public function smartySNDS(Smarty $hook_data)
    {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSSNDSPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'templates';
        array_unshift($template_dirs, $plugin_templates);
        $hook_data->setTemplateDir($template_dirs);
        return $hook_data;
    }

    public function modulesDirSNDS(array $hook_data = array())
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSSNDSPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }

    public function welcomeSNDS(array $hook_data = [])
    {
        try {
            $SMARTY = LMSSmarty::getInstance();
            $DB = LMSDB::getInstance();
            $snds_welcome_limit_rows = ConfigHelper::getConfig('snds.welcome_limit_rows', 5);

            require_once(PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSSNDSPlugin::PLUGIN_DIRECTORY_NAME . '/lib/SmartyPlugins/smarty_templates_addons.php');

            $array = $DB->GetAll('SELECT ip_address,
                activity_period_start,
                activity_period_end,
                rcpt_commands,
                data_commands,
                message_recipients,
                filter_result,
                complaint_rate,
                trap_message_period_start,
                trap_message_period_end,
                trap_hits,
                sample_helo,
                jmr_p1_sender,
                comments,
                node_id
                FROM alfa_snds
                ORDER BY id DESC LIMIT ' . $snds_welcome_limit_rows);

            // Sprawdź czy $array jest puste
            if (empty($array)) {
                // Możesz zwrócić tutaj pustą tablicę, jeśli to jest oczekiwane zachowanie
                return $hook_data;
            }

            $fieldsToConvert = [
                'activity_period_start',
                'activity_period_end',
                'trap_message_period_start',
                'trap_message_period_end',
            ];

            foreach ($array as &$item) {
                foreach ($fieldsToConvert as $field) {
                    if (isset($item[$field])) {
                        $dateString = $item[$field];
                        if (!empty($dateString)) {
                            $newDate = $this->convertDate($dateString);
                            $item[$field] = $newDate;
                        }
                    }
                }
            }
            unset($item);

            $SMARTY->assign([
                'snds' => $array,
                'snds_count' => count($array),
            ]);

            return $hook_data;
        } catch (Exception $e) {
            // Handle the exception
            error_log('Error in welcomeSNDS: ' . $e->getMessage());
            // Optionally, you can throw the exception again to propagate it
            throw $e;
        }
    }


    public function nodeInfoBeforeDisplay(array $hook_data = [])
    {
        try {
            $DB = LMSDB::getInstance();
            $nid = $_GET['id'];

            $snds_node_limit_rows = ConfigHelper::getConfig('snds.node_limit_rows', 5);

            $array = $DB->GetAll('SELECT * FROM alfa_snds WHERE node_id=' . $nid . ' ORDER BY id DESC LIMIT ' . $snds_node_limit_rows);

            // Sprawdź czy $array jest puste
            if (empty($array)) {
                // Możesz zwrócić tutaj oryginalne dane wejściowe $hook_data, jeśli to jest oczekiwane zachowanie
                return $hook_data;
            }

            $fieldsToConvert = [
                'activity_period_start',
                'activity_period_end',
                'trap_message_period_start',
                'trap_message_period_end',
            ];

            foreach ($array as &$item) {
                foreach ($fieldsToConvert as $field) {
                    if (isset($item[$field])) {
                        $dateString = $item[$field];
                        if (!empty($dateString)) {
                            $newDate = $this->convertDate($dateString);
                            $item[$field] = $newDate;
                        }
                    }
                }
            }
            unset($item);

            $hook_data['nodeinfo']['snds'] = $array;

            return $hook_data;
        } catch (Exception $e) {
            // Handle the exception
            error_log('Error in nodeInfoBeforeDisplay: ' . $e->getMessage());
            // Optionally, you can throw the exception again to propagate it
            throw $e;
        }
    }


    public function accessTableInit()
    {
        $access = AccessRights::getInstance();
        $access->insertPermission(
            new Permission(
                'SNDS_full_access',
                trans('Smart Network Data Service'),
                '^SNDS$'
            ),
            AccessRights::FIRST_FORBIDDEN_PERMISSION
        );
    }
}
