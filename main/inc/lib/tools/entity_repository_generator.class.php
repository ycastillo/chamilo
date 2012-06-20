<?php

/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Tools;

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\AssociationMapping,
    Doctrine\Common\Util\Inflector;

/**
 * Class to generate entity repository classes
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EntityRepositoryGenerator
{

    protected static $_template =
        '<?php

namespace Entity\Repository;
use \db;

/**
 * 
 * @license see /license.txt
 * @author autogenerated
 */
class <className> extends <extends>
{

    /**
     * @return \Entity\Repository\<className>
     */
    public static function instance(){
        static $result = false;
        if($result === false){
            $result = db::instance()->get_repository(\'\\Entity\\<entityName>\');
        }
        return $result;
    }
    
    /**
     * 
     * @param EntityManager $em The EntityManager to use.
     * @param ClassMetadata $class The class descriptor.
     */
    public function __construct($em, $class){
        parent::__construct($em, $class);
    }
    
}';

    public function generateEntityRepositoryClass($name)
    {
        $name = Inflector::tableize($name);
        $is_course_table = (strpos($name, 'c_') === 0);
        if ($is_course_table) {
            $name = substr($name, 2, strlen($name) - 2);
        }
        $name = Inflector::classify($name);
        $className = $name;
        //$namespace = substr($fullClassName, 0, strrpos($fullClassName, '\\'));
        //$className = substr($fullClassName, strrpos($fullClassName, '\\') + 1, strlen($fullClassName));

        $is_course_table = $metadata->is_course_table;
        
        
        $variables = array(
            '<namespace>' => $namespace,
            '<className>' => $className,
            '<entityName>' => str_replace('Repository', '', $className),
            '<extends>' => $is_course_table ? '\CourseEntityRepository' : '\EntityRepository'
        );
        return str_replace(array_keys($variables), array_values($variables), self::$_template);
    }

    /**
     *
     * @param type $name
     * @param type $outputDirectory 
     */
    public function writeEntityRepositoryClass($name, $outputDirectory)
    {
        $name = explode('\\', $name);
        $name = end($name);
        $name = Inflector::tableize($name);
        $is_course_table = (strpos($name, 'c_') === 0);
        if ($is_course_table) {
            $name = substr($name, 2, strlen($name) - 2);
        }
        $name = Inflector::classify($name) . 'Repository';
        $fullClassName = $name;
        
        $file_name = Inflector::tableize($name);

        $code = $this->generateEntityRepositoryClass($fullClassName);

        $path = $outputDirectory . DIRECTORY_SEPARATOR
            . str_replace('\\', \DIRECTORY_SEPARATOR, $file_name) . '.class.php';
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!file_exists($path)) {
            file_put_contents($path, $code);
        }
    }

}