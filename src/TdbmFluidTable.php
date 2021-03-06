<?php


namespace TheCodingMachine\FluidSchema;

use function addslashes;
use Doctrine\DBAL\Schema\Table;
use function in_array;

class TdbmFluidTable
{
    /**
     * @var FluidSchema|TdbmFluidSchema
     */
    private $schema;
    /**
     * @var FluidTable
     */
    private $fluidTable;
    /**
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var array<string, TdbmFluidColumn>
     */
    private $tdbmFluidColumns = [];

    public function __construct(TdbmFluidSchema $schema, FluidTable $fluidTable, NamingStrategyInterface $namingStrategy)
    {
        $this->schema = $schema;
        $this->fluidTable = $fluidTable;
        $this->namingStrategy = $namingStrategy;
    }

    public function column(string $name): TdbmFluidColumn
    {
        if (!isset($this->tdbmFluidColumns[$name])) {
            $this->tdbmFluidColumns[$name] = new TdbmFluidColumn($this, $this->fluidTable->column($name), $this->namingStrategy);
        }
        return $this->tdbmFluidColumns[$name];
    }

    public function index(array $columnNames): TdbmFluidTable
    {
        $this->fluidTable->index($columnNames);
        return $this;
    }

    public function unique(array $columnNames): TdbmFluidTable
    {
        $this->fluidTable->unique($columnNames);
        return $this;
    }

    public function primaryKey(array $columnNames, ?string $indexName = null): TdbmFluidTable
    {
        $this->fluidTable->primaryKey($columnNames, $indexName);
        return $this;
    }

    public function id(): TdbmFluidTable
    {
        $this->fluidTable->id();
        return $this;
    }

    public function uuid(string $version = 'v4'): TdbmFluidTable
    {
        if ($version !== 'v1' && $version !== 'v4') {
            throw new FluidSchemaException('UUID version must be one of "v1" or "v4"');
        }
        $this->fluidTable->uuid();

        $this->column('uuid')->guid()->addAnnotation('UUID', '("'.$version.'")');
        return $this;
    }

    public function timestamps(): TdbmFluidTable
    {
        $this->fluidTable->timestamps();
        return $this;
    }

    /**
     * @throws FluidSchemaException
     */
    public function extends(string $tableName): TdbmFluidTable
    {
        $this->fluidTable->extends($tableName);
        return $this;
    }

    /**
     * Adds a "Bean" annotation to the table.
     */
    public function customBeanName(string $beanName): TdbmFluidTable
    {
        $this->addAnnotation('Bean', '(name="'.addslashes($beanName).'")');
        return $this;
    }

    /**
     * Adds a "Type" annotation.
     */
    public function graphqlType(): TdbmFluidTable
    {
        $this->addAnnotation('TheCodingMachine\\GraphQLite\\Annotations\\Type');
        return $this;
    }

    private function getComment(): Comment
    {
        $options = $this->fluidTable->getDbalTable()->getOptions();
        $comment = $options['comment'] ?? '';

        return new Comment($comment);
    }

    private function saveComment(Comment $comment): self
    {
        $this->fluidTable->getDbalTable()->addOption('comment', $comment->getComment());
        return $this;
    }

    public function addAnnotation(string $annotation, string $content = '', bool $replaceExisting = true): self
    {
        $comment = $this->getComment()->addAnnotation($annotation, $content, $replaceExisting);
        $this->saveComment($comment);
        return $this;
    }

    public function getDbalTable(): Table
    {
        return $this->fluidTable->getDbalTable();
    }
}
