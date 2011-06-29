<?php
/**
 * A PHP_CodeSniffer specific test suite for PHPUnit.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: TestSuite.php 302830 2010-08-26 23:46:40Z squiz $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

require_once 'PHPUnit/Framework/TestSuite.php';

/**
 * A PHP_CodeSniffer specific test suite for PHPUnit.
 *
 * Unregisters the PHP_CodeSniffer autoload function after the run.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.3.0RC1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHP_CodeSniffer_TestSuite extends PHPUnit_Framework_TestSuite
{


    /**
     * Runs the tests and collects their result in a TestResult.
     *
     * @param PHPUnit_Framework_TestResult $result A test result.
     * @param mixed                        $filter The filter passed to each test.
     *
     * @return PHPUnit_Framework_TestResult
     */
    public function run(PHPUnit_Framework_TestResult $result=null, $filter=false)
    {
        spl_autoload_register(array('PHP_CodeSniffer', 'autoload'));
        $result = parent::run($result, $filter);
        spl_autoload_unregister(array('PHP_CodeSniffer', 'autoload'));
        return $result;

    }//end run()


}//end class

?>
