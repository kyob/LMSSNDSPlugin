<?php

$parameters = [
    't:' => 'date',
    'h' => 'help',
    'd' => 'debug',
];

$short_to_longs = [];
foreach ($parameters as $key => $val) {
    $val = str_replace(':', '', $val);
    $newkey = str_replace(':', '', $key);
    $short_to_longs[$newkey] = $val;
}

$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long) {
    if (array_key_exists($short, $options)) {
        $options[$long] = $options[$short];
        unset($options[$short]);
    }
}

if (isset($options['date'])) {
    $date = $options['date'];
    echo "lms-snds.php\n(C) 2001-2023 LMS Developers\n";
} else {
    $today = new DateTime();
    $today->modify('-1 day'); // Zmniejszamy datę o 1 dzień, gdyż dane na SNDS są z opóźnieniem
    $date = $today->format('mdy');
}
if (array_key_exists('help', $options)) {
    echo "lms-snds.php\n(C) 2001-2023 LMS Developers\n\n";
    echo "-t, --date      date in format MMDDYY;\n";
    echo "-h, --help      print this help and exit;\n";
    echo "-d, --debug     debug do nothing only print;\n";
    echo "-v, --version   print version info and exit;\n";
    exit(0);
}

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE
$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();
$AUTH = null;
$curl = null;

function fetchSNDSdata($date = null)
{
    echo PHP_EOL . 'fetchSNDSdata: ' . $date . PHP_EOL;
    $DB = LMSDB::getInstance();

    // Config
    $snds_key = ConfigHelper::getConfig('snds.key');
    $snds_url = 'https://sendersupport.olc.protection.outlook.com/snds/data.aspx?key=' . $snds_key . '&date=';

    if (empty($snds_key)) {
        // Configurations are missing or empty
        die('Check "snds.key". Missing or empty configurations for $snds_key. Script execution stopped.');
    }

    // Determine the CSV URL
    if ($date) {
        $csvUrl = $snds_url . $date;
    } else {
        // Get the previous day's date
        $previousDay = strtotime('-1 day');
        $previousDate = date('mdy', $previousDay);
        $csvUrl = $snds_url . $previousDate;
    }

    // Fetch CSV data
    $csvData = file_get_contents($csvUrl);
    if ($csvData === false) {
        throw new Exception('Failed to fetch data from URL.');
    }

    // Process CSV data
    $rows = explode("\n", $csvData);

    // Prepare log file
    $logFile = '/var/log/lms/plugins/snds.log';
    if (!file_exists($logFile)) {
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        touch($logFile);
        chmod($logFile, 0644);
    }

    // Open log file
    $logHandle = fopen($logFile, 'a');
    if ($logHandle === false) {
        throw new Exception('Failed to open log file.');
    }

    // Log script execution
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Script SNDS executed successfully. Parameter \$date: " . ($date ?? 'current date');
    fwrite($logHandle, $logMessage . PHP_EOL);

    // Track added rows
    $addedRows = 0;

    // Process each CSV row
    foreach ($rows as $row) {
        if (empty($row)) {
            continue;
        }

        $data = str_getcsv($row);

        $ip_long = ip2long($data[0]);
        $node_id = $DB->GetOne('SELECT id FROM vnodes WHERE ipaddr = ?', [$ip_long]);

        // Check if the query returned any results
        if (!$node_id) {
            echo "node_id not found for IP: " . $data[0] . PHP_EOL;
        } else {
            $node_id = null;
        }

        if (count($data) !== 14) {
            throw new Exception('Invalid number of columns in the row.');
        }

        $hash = md5(implode('', $data));
        $data[] = $hash;
        $data[] = $node_id;

        try {
            $DB->Execute(
                'INSERT INTO alfa_snds (ip_address, activity_period_start, activity_period_end, rcpt_commands, data_commands, message_recipients, filter_result, complaint_rate, trap_message_period_start, trap_message_period_end, trap_hits, sample_helo, jmr_p1_sender, comments, hash, node_id) 
                        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                        ON CONFLICT (hash) DO NOTHING',
                array_values($data)
            );

            $addedRows++;
        } catch (Exception $e) {
            throw new Exception('Failed to add to the database: ' . $e->getMessage());
        }
    }

    // Log the number of added rows
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Added to the database: " . $addedRows . " rows." . PHP_EOL;
    fwrite($logHandle, $logMessage . PHP_EOL);

    // Close log file
    fclose($logHandle);

    return true;
}


function populateWithDataFromDate($from_date = null)
{
    // Get the current date
    $today = new DateTime();

    // Set the start date
    if ($from_date) {
        $startDate = $from_date;
    } else {
        $startDate = new DateTime('2023-04-15');
    }

    // Iterate over the dates
    while ($startDate <= $today) {

        // Get the date in MMDDYY format
        $date = $startDate->format('mdy');

        // Call the fetchSNDSdata() function
        fetchSNDSdata($date);

        // Increment the start date by one day
        $startDate->add(new DateInterval('P1D'));
    }
    return true;
}

$time_start = microtime(true);

//uncomment if you need historical data
//populateWithDataFromDate();

if ($date != null && preg_match('/^\d{6}$/', $date)) {
    fetchSNDSdata($date);
    $DB->Destroy();
}

$time_end = microtime(true);
$time = $time_end - $time_start;

echo PHP_EOL . "Did nothing in $time seconds\n";
