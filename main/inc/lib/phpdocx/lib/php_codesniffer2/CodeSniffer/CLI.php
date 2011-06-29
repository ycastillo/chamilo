<?php
/**
 * A class to process command line phpcs scripts.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: CLI.php 302679 2010-08-23 05:41:12Z squiz $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (is_file(dirname(__FILE__).'/../CodeSniffer.php') === true) {
    include_once dirname(__FILE__).'/../CodeSniffer.php';
} else {
    include_once 'PHP/CodeSniffer.php';
}

/**
 * A class to process command line phpcs scripts.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.3.0RC1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHP_CodeSniffer_CLI
{

    /**
     * An array of all values specified on the command line.
     *
     * @var array
     */
    protected $values = array();

    /**
     * The minimum severity level errors must have to be displayed.
     *
     * @var bool
     */
    public $errorSeverity = 0;

    /**
     * The minimum severity level warnings must have to be displayed.
     *
     * @var bool
     */
    public $warningSeverity = 0;


    /**
     * Exits if the minimum requirements of PHP_CodSniffer are not met.
     *
     * @return array
     */
    public function checkRequirements()
    {
        // Check the PHP version.
        if (version_compare(PHP_VERSION, '5.1.2') === -1) {
            echo 'ERROR: PHP_CodeSniffer requires PHP version 5.1.2 or greater.'.PHP_EOL;
            exit(2);
        }

        if (extension_loaded('tokenizer') === false) {
            echo 'ERROR: PHP_CodeSniffer requires the tokenizer extension to be enabled.'.PHP_EOL;
            exit(2);
        }

    }//end checkRequirements()


    /**
     * Get a list of default values for all possible command line arguments.
     *
     * @return array
     */
    public function getDefaults()
    {
        // The default values for config settings.
        $defaults['files']       = array();
        $defaults['standard']    = null;
        $defaults['verbosity']   = 0;
        $defaults['interactive'] = false;
        $defaults['local']       = false;
        $defaults['showSources'] = false;
        $defaults['extensions']  = array();
        $defaults['sniffs']      = array();
        $defaults['ignored']     = array();
        $defaults['reportFile']  = '';
        $defaults['generator']   = '';

        $defaults['report'] = PHP_CodeSniffer::getConfigData('report_format');
        if ($defaults['report'] === null) {
            $defaults['report'] = 'full';
        }

        $defaults['warningSeverity'] = null;
        $showWarnings = PHP_CodeSniffer::getConfigData('show_warnings');
        if ($showWarnings !== null) {
            $showWarnings = (bool) $showWarnings;
            if ($showWarnings === false) {
                $defaults['warningSeverity'] = 0;
            }
        }

        $tabWidth = PHP_CodeSniffer::getConfigData('tab_width');
        if ($tabWidth === null) {
            $defaults['tabWidth'] = 0;
        } else {
            $defaults['tabWidth'] = (int) $tabWidth;
        }

        $encoding = PHP_CodeSniffer::getConfigData('encoding');
        if ($encoding === null) {
            $defaults['encoding'] = 'iso-8859-1';
        } else {
            $defaults['encoding'] = strtolower($encoding);
        }

        $severity = PHP_CodeSniffer::getConfigData('severity');
        if ($severity === null) {
            $defaults['errorSeverity']   = null;
            $defaults['warningSeverity'] = null;
        } else {
            $defaults['errorSeverity']   = (int) $severity;
            $defaults['warningSeverity'] = (int) $severity;
        }

        $severity = PHP_CodeSniffer::getConfigData('error_severity');
        if ($severity === null) {
            $defaults['errorSeverity'] = null;
        } else {
            $defaults['errorSeverity'] = (int) $severity;
        }

        $severity = PHP_CodeSniffer::getConfigData('warning_severity');
        if ($severity === null) {
            $defaults['warningSeverity'] = null;
        } else {
            $defaults['warningSeverity'] = (int) $severity;
        }

        $reportWidth = PHP_CodeSniffer::getConfigData('report_width');
        if ($reportWidth === null) {
            $defaults['reportWidth'] = 80;
        } else {
            $defaults['reportWidth'] = (int) $reportWidth;
        }

        return $defaults;

    }//end getDefaults()


    /**
     * Process the command line arguments and returns the values.
     *
     * @return array
     */
    public function getCommandLineValues()
    {
        if (empty($this->values) === false) {
            return $this->values;
        }

        $values = $this->getDefaults();

        for ($i = 1; $i < $_SERVER['argc']; $i++) {
            $arg = $_SERVER['argv'][$i];
            if ($arg{0} === '-') {
                if ($arg === '-' || $arg === '--') {
                    // Empty argument, ignore it.
                    continue;
                }

                if ($arg{1} === '-') {
                    $values
                        = $this->processLongArgument(substr($arg, 2), $i, $values);
                } else {
                    $switches = str_split($arg);
                    foreach ($switches as $switch) {
                        if ($switch === '-') {
                            continue;
                        }

                        $values = $this->processShortArgument($switch, $i, $values);
                    }
                }
            } else {
                $values = $this->processUnknownArgument($arg, $i, $values);
            }//end if
        }//end for

        $this->values = $values;
        return $values;

    }//end getCommandLineValues()


    /**
     * Processes a sort (-e) command line argument.
     *
     * @param string $arg    The command line argument.
     * @param int    $pos    The position of the argument on the command line.
     * @param array  $values An array of values determined from CLI args.
     *
     * @return array The updated CLI values.
     * @see getCommandLineValues()
     */
    public function processShortArgument($arg, $pos, $values)
    {
        switch ($arg) {
        case 'h':
        case '?':
            $this->printUsage();
            exit(0);
            break;
        case 'i' :
            $this->printInstalledStandards();
            exit(0);
            break;
        case 'v' :
            $values['verbosity']++;
            break;
        case 'l' :
            $values['local'] = true;
            break;
        case 's' :
            $values['showSources'] = true;
            break;
        case 'a' :
            $values['interactive'] = true;
            break;
        case 'n' :
            $values['warningSeverity'] = 0;
            break;
        case 'w' :
            $values['warningSeverity'] = null;
            break;
        default:
            $values = $this->processUnknownArgument('-'.$arg, $pos, $values);
        }//end switch

        return $values;

    }//end processShortArgument()


    /**
     * Processes a long (--example) command line argument.
     *
     * @param string $arg    The command line argument.
     * @param int    $pos    The position of the argument on the command line.
     * @param array  $values An array of values determined from CLI args.
     *
     * @return array The updated CLI values.
     * @see getCommandLineValues()
     */
    public function processLongArgument($arg, $pos, $values)
    {
        switch ($arg) {
        case 'help':
            $this->printUsage();
            exit(0);
            break;
        case 'version':
            echo 'PHP_CodeSniffer version 1.3.0RC1 (beta) ';
            echo 'by Squiz Pty Ltd. (http://www.squiz.net)'.PHP_EOL;
            exit(0);
            break;
        case 'config-set':
            $key   = $_SERVER['argv'][($pos + 1)];
            $value = $_SERVER['argv'][($pos + 2)];
            PHP_CodeSniffer::setConfigData($key, $value);
            exit(0);
            break;
        case 'config-delete':
            $key = $_SERVER['argv'][($pos + 1)];
            PHP_CodeSniffer::setConfigData($key, null);
            exit(0);
            break;
        case 'config-show':
            $data = PHP_CodeSniffer::getAllConfigData();
            print_r($data);
            exit(0);
            break;
        default:
            if (substr($arg, 0, 7) === 'sniffs=') {
                $values['sniffs'] = array();

                $sniffs = substr($arg, 7);
                $sniffs = explode(',', $sniffs);

                // Convert the sniffs to class names.
                foreach ($sniffs as $sniff) {
                    $parts = explode('.', $sniff);
                    $values['sniffs'][] = $parts[0].'_Sniffs_'.$parts[1].'_'.$parts[2].'Sniff';
                }
            } else if (substr($arg, 0, 7) === 'report=') {
                $values['report'] = substr($arg, 7);
                $validReports     = array(
                                     'full',
                                     'xml',
                                     'checkstyle',
                                     'csv',
                                     'emacs',
                                     'source',
                                     'summary',
                                     'svnblame',
                                     'gitblame',
                                    );

                if (in_array($values['report'], $validReports) === false) {
                    echo 'ERROR: Report type "'.$values['report'].'" not known.'.PHP_EOL;
                    exit(2);
                }
            } else if (substr($arg, 0, 12) === 'report-file=') {
                $values['reportFile'] = realpath(substr($arg, 12));

                // It may not exist and return false instead.
                if ($values['reportFile'] === false) {
                    $values['reportFile'] = substr($arg, 12);
                }

                if (is_dir($values['reportFile']) === true) {
                    echo 'ERROR: The specified report file path "'.$values['reportFile'].'" is a directory.'.PHP_EOL.PHP_EOL;
                    $this->printUsage();
                    exit(2);
                }

                $dir = dirname($values['reportFile']);
                if (is_dir($dir) === false) {
                    echo 'ERROR: The specified report file path "'.$values['reportFile'].'" points to a non-existent directory.'.PHP_EOL.PHP_EOL;
                    $this->printUsage();
                    exit(2);
                }
            } else if (substr($arg, 0, 9) === 'standard=') {
                $values['standard'] = substr($arg, 9);
            } else if (substr($arg, 0, 11) === 'extensions=') {
                $values['extensions'] = explode(',', substr($arg, 11));
            } else if (substr($arg, 0, 9) === 'severity=') {
                $values['errorSeverity']   = (int) substr($arg, 9);
                $values['warningSeverity'] = $values['errorSeverity'];
            } else if (substr($arg, 0, 15) === 'error-severity=') {
                $values['errorSeverity'] = (int) substr($arg, 15);
            } else if (substr($arg, 0, 17) === 'warning-severity=') {
                $values['warningSeverity'] = (int) substr($arg, 17);
            } else if (substr($arg, 0, 7) === 'ignore=') {
                // Split the ignore string on commas, unless the comma is escaped
                // using 1 or 3 slashes (\, or \\\,).
                $values['ignored'] = preg_split(
                    '/(?<=(?<!\\\\)\\\\\\\\),|(?<!\\\\),/',
                    substr($arg, 7)
                );
            } else if (substr($arg, 0, 10) === 'generator=') {
                $values['generator'] = substr($arg, 10);
            } else if (substr($arg, 0, 9) === 'encoding=') {
                $values['encoding'] = strtolower(substr($arg, 9));
            } else if (substr($arg, 0, 10) === 'tab-width=') {
                $values['tabWidth'] = (int) substr($arg, 10);
            } else if (substr($arg, 0, 13) === 'report-width=') {
                $values['reportWidth'] = (int) substr($arg, 13);
            } else {
                $values = $this->processUnknownArgument('--'.$arg, $pos, $values);
            }//end if

            break;
        }//end switch

        return $values;

    }//end processLongArgument()


    /**
     * Processes an unknown command line argument.
     *
     * Assumes all unknown arguments are files and folders to check.
     *
     * @param string $arg    The command line argument.
     * @param int    $pos    The position of the argument on the command line.
     * @param array  $values An array of values determined from CLI args.
     *
     * @return array The updated CLI values.
     * @see getCommandLineValues()
     */
    public function processUnknownArgument($arg, $pos, $values)
    {
        // We don't know about any additional switches; just files.
        if ($arg{0} === '-') {
            echo 'ERROR: option "'.$arg.'" not known.'.PHP_EOL.PHP_EOL;
            $this->printUsage();
            exit(2);
        }

        $file = realpath($arg);
        if (file_exists($file) === false) {
            echo 'ERROR: The file "'.$arg.'" does not exist.'.PHP_EOL.PHP_EOL;
            $this->printUsage();
            exit(2);
        } else {
            $values['files'][] = $file;
        }

        return $values;

    }//end processUnknownArgument()


    /**
     * Runs PHP_CodeSniffer over files and directories.
     *
     * @param array $values An array of values determined from CLI args.
     *
     * @return int The number of error and warning messages shown.
     * @see getCommandLineValues()
     */
    public function process($values=array())
    {
        if (empty($values) === true) {
            $values = $this->getCommandLineValues();
        }

        if ($values['generator'] !== '') {
            $phpcs = new PHP_CodeSniffer($values['verbosity']);
            $phpcs->generateDocs(
                $values['standard'],
                $values['files'],
                $values['generator']
            );
            exit(0);
        }

        if (empty($values['files']) === true) {
            echo 'ERROR: You must supply at least one file or directory to process.'.PHP_EOL.PHP_EOL;
            $this->printUsage();
            exit(2);
        }

        $values['standard'] = $this->validateStandard($values['standard']);
        if (PHP_CodeSniffer::isInstalledStandard($values['standard']) === false) {
            // They didn't select a valid coding standard, so help them
            // out by letting them know which standards are installed.
            echo 'ERROR: the "'.$values['standard'].'" coding standard is not installed. ';
            $this->printInstalledStandards();
            exit(2);
        }

        $phpcs = new PHP_CodeSniffer(
            $values['verbosity'],
            $values['tabWidth'],
            $values['encoding'],
            $values['interactive']
        );

        // Set file extensions if they were specified. Otherwise,
        // let PHP_CodeSniffer decide on the defaults.
        if (empty($values['extensions']) === false) {
            $phpcs->setAllowedFileExtensions($values['extensions']);
        }

        // Set ignore patterns if they were specified.
        if (empty($values['ignored']) === false) {
            $phpcs->setIgnorePatterns($values['ignored']);
        }

        // Set some convenience member vars.
        if ($values['errorSeverity'] === null) {
            $this->errorSeverity = PHPCS_DEFAULT_ERROR_SEV;
        } else {
            $this->errorSeverity = $values['errorSeverity'];
        }

        if ($values['warningSeverity'] === null) {
            $this->warningSeverity = PHPCS_DEFAULT_WARN_SEV;
        } else {
            $this->warningSeverity = $values['warningSeverity'];
        }

        $phpcs->setCli($this);

        $phpcs->process(
            $values['files'],
            $values['standard'],
            $values['sniffs'],
            $values['local']
        );

        return $this->printErrorReport(
            $phpcs,
            $values['report'],
            $values['showSources'],
            $values['reportFile'],
            $values['reportWidth']
        );

    }//end process()


    /**
     * Prints the error report.
     *
     * @param PHP_CodeSniffer $phpcs       The PHP_CodeSniffer object containing
     *                                     the errors.
     * @param string          $report      The type of report to print.
     * @param bool            $showSources TRUE if report should show error sources
     *                                     (not used by all reports).
     * @param string          $reportFile  A file to log the report out to.
     * @param int             $reportWidth How wide the screen reports should be.
     *
     * @return int The number of error and warning messages shown.
     */
    public function printErrorReport(
        PHP_CodeSniffer $phpcs,
        $report,
        $showSources,
        $reportFile,
        $reportWidth
    ) {
        $filesViolations = $phpcs->getFilesErrors();

        $reporting = new PHP_CodeSniffer_Reporting();
        return $reporting->printReport(
            $report,
            $filesViolations,
            $showSources,
            $reportFile,
            $reportWidth
        );

    }//end printErrorReport()


    /**
     * Convert the passed standard into a valid standard.
     *
     * Checks things like default values and case.
     *
     * @param string $standard The standard to validate.
     *
     * @return string
     */
    public function validateStandard($standard)
    {
        if ($standard === null) {
            // They did not supply a standard to use.
            // Try to get the default from the config system.
            $standard = PHP_CodeSniffer::getConfigData('default_standard');
            if ($standard === null) {
                $standard = 'PEAR';
            }
        }

        // Check if the standard name is valid. If not, check that the case
        // was not entered incorrectly.
        if (PHP_CodeSniffer::isInstalledStandard($standard) === false) {
            $installedStandards = PHP_CodeSniffer::getInstalledStandards();
            foreach ($installedStandards as $validStandard) {
                if (strtolower($standard) === strtolower($validStandard)) {
                    $standard = $validStandard;
                    break;
                }
            }
        }

        return $standard;

    }//end validateStandard()


    /**
     * Prints out the usage information for this script.
     *
     * @return void
     */
    public function printUsage()
    {
        echo 'Usage: phpcs [-nwlsavi] [--extensions=<extensions>] [--ignore=<patterns>]'.PHP_EOL;
        echo '    [--report=<report>] [--report-width=<reportWidth>] [--report-file=<reportfile>]'.PHP_EOL;
        echo '    [--severity=<severity>] [--error-severity=<severity>] [--warning-severity=<severity>]'.PHP_EOL;
        echo '    [--config-set key value] [--config-delete key] [--config-show]'.PHP_EOL;
        echo '    [--standard=<standard>] [--sniffs=<sniffs>] [--encoding=<encoding>]'.PHP_EOL;
        echo '    [--generator=<generator>] [--tab-width=<tabWidth>] <file> ...'.PHP_EOL;
        echo '        -n            Do not print warnings (shortcut for --warning-severity=0)'.PHP_EOL;
        echo '        -w            Print both warnings and errors (on by default)'.PHP_EOL;
        echo '        -l            Local directory only, no recursion'.PHP_EOL;
        echo '        -s            Show sniff codes in all reports'.PHP_EOL;
        echo '        -a            Run interactively'.PHP_EOL;
        echo '        -v[v][v]      Print verbose output'.PHP_EOL;
        echo '        -i            Show a list of installed coding standards'.PHP_EOL;
        echo '        --help        Print this help message'.PHP_EOL;
        echo '        --version     Print version information'.PHP_EOL;
        echo '        <file>        One or more files and/or directories to check'.PHP_EOL;
        echo '        <extensions>  A comma separated list of file extensions to check'.PHP_EOL;
        echo '                      (only valid if checking a directory)'.PHP_EOL;
        echo '        <patterns>    A comma separated list of patterns that are used'.PHP_EOL;
        echo '                      to ignore directories and files'.PHP_EOL;
        echo '        <encoding>    The encoding of the files being checked (default is iso-8859-1)'.PHP_EOL;
        echo '        <sniffs>      A comma separated list of sniff codes to limit the check to'.PHP_EOL;
        echo '                      (all sniffs must be part of the specified standard)'.PHP_EOL;
        echo '        <severity>    The minimum severity that an error or warning must have'.PHP_EOL;
        echo '                      for it to be displayed.'.PHP_EOL;
        echo '        <standard>    The name of the coding standard to use'.PHP_EOL;
        echo '        <tabWidth>    The number of spaces each tab represents'.PHP_EOL;
        echo '        <generator>   The name of a doc generator to use'.PHP_EOL;
        echo '                      (forces doc generation instead of checking)'.PHP_EOL;
        echo '        <report>      Print either the "full", "xml", "checkstyle",'.PHP_EOL;
        echo '                      "csv", "emacs", "source", "summary",'.PHP_EOL;
        echo '                      "svnblame" or "gitblame" report'.PHP_EOL;
        echo '                      (the "full" report is printed by default)'.PHP_EOL;
        echo '        <reportWidth> How many columns wide screen reports should be printed'.PHP_EOL;
        echo '        <reportfile>  Write the report to the specified file path'.PHP_EOL;

    }//end printUsage()


    /**
     * Prints out a list of installed coding standards.
     *
     * @return void
     */
    public function printInstalledStandards()
    {
        $installedStandards = PHP_CodeSniffer::getInstalledStandards();
        $numStandards       = count($installedStandards);

        if ($numStandards === 0) {
            echo 'No coding standards are installed.'.PHP_EOL;
        } else {
            $lastStandard = array_pop($installedStandards);
            if ($numStandards === 1) {
                echo "The only coding standard installed is $lastStandard".PHP_EOL;
            } else {
                $standardList  = implode(', ', $installedStandards);
                $standardList .= ' and '.$lastStandard;
                echo 'The installed coding standards are '.$standardList.PHP_EOL;
            }
        }

    }//end printInstalledStandards()


}//end class

?>
