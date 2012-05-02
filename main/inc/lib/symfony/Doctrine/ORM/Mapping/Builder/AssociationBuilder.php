<?php
/*
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


namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Mapping\ClassMetadata;

class AssociationBuilder
{
    /**
     * @var ClassMetadataBuilder
     */
    protected $builder;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var array
     */
    protected $joinColumns;

    /**
     *
     * @var int
     */
    protected $type;

    /**
     * @param ClassMetadataBuilder $builder
     * @param array $mapping
     */
    public function __construct(ClassMetadataBuilder $builder, array $mapping, $type)
    {
        $this->builder = $builder;
        $this->mapping = $mapping;
        $this->type = $type;
    }

    public function mappedBy($fieldName)
    {
        $this->mapping['mappedBy'] = $fieldName;
        return $this;
    }

    public function inversedBy($fieldName)
    {
        $this->mapping['inversedBy'] = $fieldName;
        return $this;
    }

    public function cascadeAll()
    {
        $this->mapping['cascade'] = array("ALL");
        return $this;
    }

    public function cascadePersist()
    {
        $this->mapping['cascade'][] = "persist";
        return $this;
    }

    public function cascadeRemove()
    {
        $this->mapping['cascade'][] = "remove";
        return $this;
    }

    public function cascadeMerge()
    {
        $this->mapping['cascade'][] = "merge";
        return $this;
    }

    public function cascadeDetach()
    {
        $this->mapping['cascade'][] = "detach";
        return $this;
    }

    public function cascadeRefresh()
    {
        $this->mapping['cascade'][] = "refresh";
        return $this;
    }

    public function fetchExtraLazy()
    {
        $this->mapping['fetch'] = ClassMetadata::FETCH_EXTRA_LAZY;
        return $this;
    }

    public function fetchEager()
    {
        $this->mapping['fetch'] = ClassMetadata::FETCH_EAGER;
        return $this;
    }

    public function fetchLazy()
    {
        $this->mapping['fetch'] = ClassMetadata::FETCH_LAZY;
        return $this;
    }

    /**
     * Add Join Columns
     *
     * @param string $columnName
     * @param string $referencedColumnName
     * @param bool $nullable
     * @param bool $unique
     * @param string $onDelete
     * @param string $columnDef
     */
    public function addJoinColumn($columnName, $referencedColumnName, $nullable = true, $unique = false, $onDelete = null, $columnDef = null)
    {
        $this->joinColumns[] = array(
            'name' => $columnName,
            'referencedColumnName' => $referencedColumnName,
            'nullable' => $nullable,
            'unique' => $unique,
            'onDelete' => $onDelete,
            'columnDefinition' => $columnDef,
        );
        return $this;
    }

    /**
     * @return ClassMetadataBuilder
     */
    public function build()
    {
        $mapping = $this->mapping;
        if ($this->joinColumns) {
            $mapping['joinColumns'] = $this->joinColumns;
        }
        $cm = $this->builder->getClassMetadata();
        if ($this->type == ClassMetadata::MANY_TO_ONE) {
            $cm->mapManyToOne($mapping);
        } else if ($this->type == ClassMetadata::ONE_TO_ONE) {
            $cm->mapOneToOne($mapping);
        } else {
            throw new \InvalidArgumentException("Type should be a ToOne Assocation here");
        }
        return $this->builder;
    }
}