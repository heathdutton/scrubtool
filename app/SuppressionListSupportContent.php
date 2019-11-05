<?php

namespace App;

use App\Helpers\HashHelper;
use App\Schema\MySqlGrammar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dynamically creates tables for efficient content correlation
 *
 * Class SuppressionListSupportContent
 *
 * @package App
 */
class SuppressionListSupportContent extends Model
{
    /** @var int */
    const BATCH_INSERT_SIZE = 500;

    /** @var string */
    const CONTENT_COLUMN = 'content';

    /** @var string */
    const TABLE_PREFIX = 'suppression_list_support_content';

    /** @var bool */
    public $timestamps = false;

    /** @var array */
    protected $fillable = [
        'id',
        self::CONTENT_COLUMN,
    ];

    /** @var SuppressionListSupport */
    private $support;

    /** @var bool */
    private $customBinarySize = 0;

    /** @var array */
    private $queue = [];

    /** @var int */
    private $queueCount = 0;

    /**
     * SuppressionListSupportContent constructor.
     *
     * @param  array  $attributes
     * @param  SuppressionListSupport  $support
     */
    public function __construct(array $attributes = [], SuppressionListSupport $support = null)
    {
        $this->support = $support;

        // Dynamic table name based on the column type, and hash type.
        $pieces = [self::TABLE_PREFIX, $support->column_type];
        if ($support->hash_type) {
            $pieces[] = str_replace(',', '_', $support->hash_type);
        }
        $this->setTable(implode('_', $pieces));

        return parent::__construct($attributes);
    }

    /**
     * Generate the table if it doesn't exist to fill the data based on the Suppression List Support type.
     *
     * @return $this
     */
    public function createTableIfNotExists()
    {
        if (!Schema::hasTable($this->getTable()) && isset($this->support->id)) {

            // If MySQL is in use, we can customize the binary column to be as tight as possible.
            $connection = DB::connection();
            if ($connection instanceof MySqlConnection) {
                $connection->setSchemaGrammar(new MySqlGrammar());
                Blueprint::macro('customBinary', function ($column, $length) {
                    return $this->addColumn('customBinary', $column, compact('length'));
                });
                $this->customBinarySize = HashHelper::hashSize($this->support->hash_type);
            }

            Schema::create($this->getTable(), function (Blueprint $table) {
                // Id is only kept in this table for correlation back to plaintext equivalents.
                $table->bigIncrements('id');
                if ($this->support->hash_type) {
                    // A hash is best stored as binary (faster to generate, faster to store, faster to index).
                    if ($this->customBinarySize) {
                        $table->customBinary(self::CONTENT_COLUMN, $this->customBinarySize)->unique();
                    } else {
                        $table->binary(self::CONTENT_COLUMN)->unique();
                    }
                } else {
                    // Plain text will be placed here.
                    $table->text(self::CONTENT_COLUMN)->unique();
                }
            });
        }

        return $this;
    }

    /**
     * @param $content
     * @param  int  $id
     *
     * @return $this
     */
    public function addContentToQueue($content, $id = 0)
    {
        if ($id) {
            $this->queue[$id] = [
                'id'                 => $id,
                self::CONTENT_COLUMN => $content,
            ];
        } else {
            $this->queue[] = [
                self::CONTENT_COLUMN => $content,
            ];
        }
        $this->queueCount++;
        if (0 == $this->queueCount % self::BATCH_INSERT_SIZE) {
            $this->persistQueue();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function persistQueue()
    {
        DB::table($this->getTable())->insertOrIgnore($this->queue);

        $this->queueCount = 0;
        $this->queue      = [];

        return $this;
    }

}
