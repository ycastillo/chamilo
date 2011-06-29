<?php
/**
 * Ensures that object indexes are written in dot notation.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer_MySource
 * @author    Sertan Danis <sdanis@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: DisallowObjectStringIndexSniff.php 301632 2010-07-28 01:57:56Z squiz $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Ensures that object indexes are written in dot notation.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Sertan Danis <sdanis@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.3.0RC1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Squiz_Sniffs_Objects_DisallowObjectStringIndexSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array('JS');


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_OPEN_SQUARE_BRACKET);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check if the next non whitespace token is a string.
        $index = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($tokens[$index]['code'] !== T_CONSTANT_ENCAPSED_STRING) {
            return;
        }

        // Make sure it is the only thing in the square brackets.
        $next = $phpcsFile->findNext(T_WHITESPACE, ($index + 1), null, true);
        if ($tokens[$next]['code'] !== T_CLOSE_SQUARE_BRACKET) {
            return;
        }

        // Allow indxes that have dots in them because we can't write
        // them in dot notation.
        $content = trim($tokens[$index]['content'], '"\' ');
        if (strpos($content, '.') !== false) {
            return;
        }

        // Also ignore reserved words.
        if ($content === 'super') {
            return;
        }

        // Token before the opening square bracket cannot be a var name.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($tokens[$prev]['code'] === T_STRING) {
            $error = 'Object indexes must be written in dot notation';
            $phpcsFile->addError($error, $prev, 'Found');
        }

    }//end process()


}//end class

?>
