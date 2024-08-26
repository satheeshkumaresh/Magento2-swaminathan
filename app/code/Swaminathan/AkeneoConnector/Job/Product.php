<?php

declare(strict_types=1);

namespace Swaminathan\AkeneoConnector\Job;

use Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field\Configurable as TypeField;
use Akeneo\Connector\Executor\JobExecutor;
use Akeneo\Connector\Executor\JobExecutorFactory;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\FamilyVariant;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Helper\Import\Product as ProductImportHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\ProductFilters;
use Akeneo\Connector\Helper\ProductModel;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Job\Import as JobImport;
use Akeneo\Connector\Job\Option as JobOption;
use Akeneo\Connector\Logger\Handler\ProductHandler;
use Akeneo\Connector\Logger\ProductLogger;
use Akeneo\Connector\Model\Source\Attribute\Metrics as AttributeMetrics;
use Akeneo\Connector\Model\Source\Attribute\Tables as AttributeTables;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Connector\Model\Source\Filters\Mode;
use Akeneo\Connector\Model\Source\Filters\ModelCompleteness;
use Akeneo\Connector\Model\Source\StatusMode;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursor;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Exception;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Product as BaseProductModel;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\ImageEntryConverter;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductLink\Link as ProductLink;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EavAttribute;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql as AdapterMysql;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Statement\Pdo\Mysql;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend_Db_Exception;
use Zend_Db_Expr as Expr;
use Zend_Db_Statement_Exception;
use Zend_Db_Statement_Pdo;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Product extends \Akeneo\Connector\Job\Product
{
    /**
     * @var string PIM_PRODUCT_STATUS_DISABLED
     */
    public const PIM_PRODUCT_STATUS_DISABLED = '0';
    /**
     * @var string MAGENTO_PRODUCT_STATUS_DISABLED
     */
    public const MAGENTO_PRODUCT_STATUS_DISABLED = '2';
    /**
     * @var int CONFIGURABLE_INSERTION_MAX_SIZE
     */
    public const CONFIGURABLE_INSERTION_MAX_SIZE = 500;
    /**
     * Description CATALOG_PRODUCT_ENTITY_TABLE_NAME constant
     *
     * @var string CATALOG_PRODUCT_ENTITY_TABLE_NAME
     */
    public const CATALOG_PRODUCT_ENTITY_TABLE_NAME = 'catalog_product_entity';
    public const SUFFIX_SEPARATOR = '-';
    /**
     * @var string AKENEO_PRICE_ATTRIBUTE_TYPE
     */
    public const AKENEO_PRICE_ATTRIBUTE_TYPE = 'pim_catalog_price_collection';
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'product';
    /**
     * This variable contains entities
     *
     * @var Entities $entities
     */
    protected $entities;
    /**
     * This variable contains a string
     *
     * @var string $step
     */
    protected $family = null;
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Product';
    /**
     * list of allowed type_id that can be imported
     *
     * @var string[]
     */
    protected $allowedTypeId = ['simple', 'virtual'];
    /**
     * List of column to exclude from attribute value setting
     *
     * @var string[]
     */
    protected $excludedColumns = [
        '_entity_id',
        '_is_new',
        '_status',
        '_type_id',
        '_options_container',
        '_tax_class_id',
        '_attribute_set_id',
        '_visibility',
        '_children',
        '_axis',
        'identifier',
        'sku',
        'categories',
        'family',
        'groups',
        'parent',
        'enabled',
        'created',
        'updated',
        'associations',
        'PACK',
        'SUBSTITUTION',
        'UPSELL',
        'X_SELL',
    ];
    /**
     * This variable contains a ProductImportHelper
     *
     * @var ProductImportHelper $entitiesHelper
     */
    protected $entitiesHelper;
    /**
     * This variable contains a ProductModel
     *
     * @var ProductModel $productModelHelper
     */
    protected $productModelHelper;
    /**
     * This variable contains a FamilyVariant
     *
     * @var FamilyVariant $familyVariantHelper
     */
    protected $familyVariantHelper;
    /**
     * This variable contains an EavConfig
     *
     * @var  EavConfig $eavConfig
     */
    protected $eavConfig;
    /**
     * This variable contains an EavAttribute
     *
     * @var  EavConfig $eavConfig
     */
    protected $eavAttribute;
    /**
     * This variable contains a ProductFilters
     *
     * @var ProductFilters $productFilters
     */
    protected $productFilters;
    /**
     * This variable contains product filters
     *
     * @var mixed[] $filters
     */
    protected $filters;
    /**
     * This variable contains a ScopeConfigInterface
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;
    /**
     * This variable contains a Json
     *
     * @var Json $jsonSerializer
     */
    protected $jsonSerializer;
    /**
     * This variable contains a ProductModel
     *
     * @var BaseProductModel $product
     */
    protected $product;
    /**
     * This variable contains a ProductUrlPathGenerator
     *
     * @var ProductUrlPathGenerator $productUrlPathGenerator
     */
    protected $productUrlPathGenerator;
    /**
     * This variable contains a TypeListInterface
     *
     * @var TypeListInterface $cacheTypeList
     */
    protected $cacheTypeList;
    /**
     * This variable contains a StoreHelper
     *
     * @var StoreHelper $storeHelper
     */
    protected $storeHelper;
    /**
     * This variable contains a JobOption
     *
     * @var JobOption $jobOption
     */
    protected $jobOption;
    /**
     * This variable contains an AttributeMetrics
     *
     * @var AttributeMetrics $attributeMetrics
     */
    protected $attributeMetrics;
    /**
     * This variable contains an $attributeTables
     *
     * @var AttributeTables $attributeTables
     */
    protected $attributeTables;
    /**
     * This variable contains an StoreManagerInterface
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;
    /**
     * This variable contains a logger
     *
     * @var ProductLogger $logger
     */
    protected $logger;
    /**
     * This variable contains a handler
     *
     * @var ProductHandler $handler
     */
    protected $handler;
    /**
     * This variable contains a IndexerInterface
     *
     * @var IndexerFactory $indexFactory
     */
    protected $indexFactory;
    /**
     * Description $jobExecutorFactory field
     *
     * @var JobExecutorFactory $jobExecutorFactory
     */
    protected $jobExecutorFactory;

    /**
     * Product constructor.
     *
     * @param ProductLogger $logger
     * @param ProductHandler $handler
     * @param OutputHelper $outputHelper
     * @param ManagerInterface $eventManager
     * @param Authenticator $authenticator
     * @param ProductImportHelper $entitiesHelper
     * @param ConfigHelper $configHelper
     * @param ProductModel $productModel
     * @param FamilyVariant $familyVariant
     * @param EavConfig $eavConfig
     * @param EavAttribute $eavAttribute
     * @param ProductFilters $productFilters
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $jsonSerializer
     * @param BaseProductModel $product
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param TypeListInterface $cacheTypeList
     * @param StoreHelper $storeHelper
     * @param Entities $entities
     * @param Option $jobOption
     * @param AttributeMetrics $attributeMetrics
     * @param AttributeTables $attributeTables
     * @param StoreManagerInterface $storeManager
     * @param IndexerFactory $indexFactory
     * @param JobExecutorFactory $jobExecutorFactory
     * @param array $data
     */
    public function __construct(
        ProductLogger $logger,
        ProductHandler $handler,
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        ProductImportHelper $entitiesHelper,
        ConfigHelper $configHelper,
        ProductModel $productModel,
        FamilyVariant $familyVariant,
        EavConfig $eavConfig,
        EavAttribute $eavAttribute,
        ProductFilters $productFilters,
        ScopeConfigInterface $scopeConfig,
        Json $jsonSerializer,
        BaseProductModel $product,
        ProductUrlPathGenerator $productUrlPathGenerator,
        TypeListInterface $cacheTypeList,
        StoreHelper $storeHelper,
        Entities $entities,
        JobOption $jobOption,
        AttributeMetrics $attributeMetrics,
        AttributeTables $attributeTables,
        StoreManagerInterface $storeManager,
        IndexerFactory $indexFactory,
        JobExecutorFactory $jobExecutorFactory,
        ProductRepositoryInterface $productRepositoryInterface,
        StockRegistryInterface $stockRegistryInterface,
        ProductFactory $productFactory,
        Grouped $grouped,
        array $data = []
    ) {
        parent::__construct(
            $logger,
            $handler,
            $outputHelper,
            $eventManager,
            $authenticator,
            $entitiesHelper,
            $configHelper,
            $productModel,
            $familyVariant,
            $eavConfig,
            $eavAttribute,
            $productFilters,
            $scopeConfig,
            $jsonSerializer,
            $product,
            $productUrlPathGenerator,
            $cacheTypeList,
            $storeHelper,
            $entities,
            $jobOption,
            $attributeMetrics,
            $attributeTables,
            $storeManager,
            $indexFactory,
            $jobExecutorFactory,
            $data
        );
        $this->logger = $logger;
        $this->handler = $handler;
        $this->productModelHelper = $productModel;
        $this->familyVariantHelper = $familyVariant;
        $this->eavConfig = $eavConfig;
        $this->eavAttribute = $eavAttribute;
        $this->productFilters = $productFilters;
        $this->scopeConfig = $scopeConfig;
        $this->jsonSerializer = $jsonSerializer;
        $this->product = $product;
        $this->cacheTypeList = $cacheTypeList;
        $this->storeHelper = $storeHelper;
        $this->jobOption = $jobOption;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->attributeMetrics = $attributeMetrics;
        $this->attributeTables = $attributeTables;
        $this->storeManager = $storeManager;
        $this->entities = $entities;
        $this->indexFactory = $indexFactory;
        $this->jobExecutorFactory = $jobExecutorFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->productFactory = $productFactory;
        $this->grouped = $grouped;   
    }


    public function addRequiredData()
    {
        $family = $this->getFamily();
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());

        /** @var string $edition */
        $edition = $this->configHelper->getEdition();
        $productType= "simple"; // Assigning product type as "simple" by default - by NITS
        // If family is grouped, create grouped products
        if (($edition === Edition::SERENITY || $edition === Edition::GREATER_OR_FIVE || $edition === Edition::GROWTH) && $this->entitiesHelper->isFamilyGrouped(
                $family
            )
        ) {
            $connection->addColumn(
                $tmpTable,
                '_type_id',
                [
                    'type' => 'text',
                    'length' => 255,
                    'default' => 'grouped',
                    'COMMENT' => ' ',
                    'nullable' => false,
                ]
            );
            $productType= "grouped";  // Assigning product type as "simple" if it is grouped product - by NITS
        } else {
            $connection->addColumn(
                $tmpTable,
                '_type_id',
                [
                    'type' => 'text',
                    'length' => 255,
                    'default' => 'simple',
                    'COMMENT' => ' ',
                    'nullable' => false,
                ]
            );
        }
        $connection->addColumn(
            $tmpTable,
            '_options_container',
            [
                'type' => 'text',
                'length' => 255,
                'default' => 'container2',
                'COMMENT' => ' ',
                'nullable' => false,
            ]
        );
        $connection->addColumn(
            $tmpTable,
            '_tax_class_id',
            [
                'type' => 'integer',
                'length' => 11,
                'default' => 0,
                'COMMENT' => ' ',
                'nullable' => false,
            ]
        ); // None
        $connection->addColumn(
            $tmpTable,
            '_attribute_set_id',
            [
                'type' => 'integer',
                'length' => 11,
                'default' => 4,
                'COMMENT' => ' ',
                'nullable' => false,
            ]
        ); // Default
        $connection->addColumn(
            $tmpTable,
            '_visibility',
            [
                'type' => 'integer',
                'length' => 11,
                'default' => Visibility::VISIBILITY_BOTH,
                'COMMENT' => ' ',
                'nullable' => false,
            ]
        );
        if (!$connection->tableColumnExists($tmpTable, 'quantity')) {
            $connection->addColumn(
                $tmpTable,
                'quantity',
                [
                    'type' => 'integer',
                    'length' => 11,
                    'default' => 0,
                    'COMMENT' => ' ',
                    'nullable' => false,
                ]
            ); // Default
        }
        $connection->addColumn(
            $tmpTable,
            '_status',
            [
                'type' => 'integer',
                'length' => 11,
                'default' => 2,
                'COMMENT' => ' ',
                'nullable' => false,
            ]
        ); // Disabled

        if (!$connection->tableColumnExists($tmpTable, 'is_returnable')) {
            $connection->addColumn(
                $tmpTable,
                'is_returnable',
                [
                    'type' => 'integer',
                    'length' => 11,
                    'default' => 2,
                    'COMMENT' => ' ',
                    'nullable' => false,
                ]
            );
        }

        if (!$connection->tableColumnExists($tmpTable, 'url_key') && $this->configHelper->isUrlGenerationEnabled()) {
            $connection->addColumn(
                $tmpTable,
                'url_key',
                [
                    'type' => 'text',
                    'length' => 255,
                    'default' => '',
                    'COMMENT' => ' ',
                    'nullable' => false,
                ]
            );
            $connection->update($tmpTable, ['url_key' => new Expr('LOWER(`identifier`)')]);
        }

        /** @var string|null $groupColumn */
        $groupColumn = null;
        if ($connection->tableColumnExists($tmpTable, 'parent')) {
            $groupColumn = 'parent';
        }
        if ($connection->tableColumnExists($tmpTable, 'groups') && !$groupColumn) {
            $groupColumn = 'groups';
        }

        if ($groupColumn) {
            $connection->update(
                $tmpTable,
                [
                    '_visibility' => new Expr(
                        'IF(`' . $groupColumn . '` <> "", ' . Visibility::VISIBILITY_NOT_VISIBLE . ', ' . Visibility::VISIBILITY_BOTH . ')'
                    ),
                ]
            );
        }
        /** @var string $productMappingAttribute */
        $productMappingAttribute = $this->configHelper->getMappingAttribute();
        if ($connection->tableColumnExists($tmpTable, $productMappingAttribute)) {
            /** @var string $types */
            $types = $connection->quote($this->allowedTypeId);
            $connection->update(
                $tmpTable,
                [
                    '_type_id' => new Expr(
                        "IF($productMappingAttribute IN ($types), $productMappingAttribute, 'simple')"
                    ),
                ]
            );
        }


        if ($connection->tableColumnExists($tmpTable, 'enabled')) {
            $connection->update(
                $tmpTable,
                ['_status' => new Expr('IF(`enabled` <> 1, 2, 1)')],
                ['_type_id = ?' => $productType] // updating status based on product type - by NITS
            );
        }

        /** @var string|array $matches */
        $matches = $this->configHelper->getAttributeMapping();
        if (!is_array($matches)) {
            return;
        }

        /** @var array $stores */
        $stores = $this->storeHelper->getAllStores();

        /** @var array $match */
        foreach ($matches as $match) {
            if (!isset($match['akeneo_attribute'], $match['magento_attribute'])) {
                continue;
            }

            /** @var string $pimAttribute */
            $pimAttribute = $match['akeneo_attribute'];
            /** @var string $magentoAttribute */
            $magentoAttribute = $match['magento_attribute'];

            $this->entitiesHelper->copyColumn($tmpTable, $pimAttribute, $magentoAttribute, $family);

            /**
             * @var string $local
             * @var string $affected
             */
            foreach ($stores as $local => $affected) {
                $this->entitiesHelper->copyColumn(
                    $tmpTable,
                    $pimAttribute . '-' . $local,
                    $magentoAttribute . '-' . $local,
                    $family
                );
            }
        }
    }


    public function initStock()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var int $websiteId */
        $websiteId = $this->configHelper->getDefaultScopeId();
        /** @var array $values --  assigning stock details from akeneo api by NITS */
        $values = [
            'product_id' => '_entity_id',
            'stock_id' => new Expr(1),
            'qty' => 'quantity',
            'is_in_stock' => 'quantity_and_stock_status',
            'low_stock_date' => new Expr('null'),
            'stock_status_changed_auto' => new Expr(0),
            'website_id' => new Expr($websiteId),
        ];

        /** @var Select $select */
        $select = $connection->select()->from($tmpTable, $values);

        $connection->query(
            $connection->insertFromSelect(
                $select,
                $this->entitiesHelper->getTable('cataloginventory_stock_item'),
                array_keys($values),
                AdapterInterface::INSERT_IGNORE
            )
        );
        $inventoryValues = [
            'sku' => 'identifier',
            'source_code' => new Expr('"default"'),
            'quantity' => 'quantity',
            'status' => new Expr(1)
        ];
        $select = $connection->select()->from($tmpTable, $inventoryValues);
         $connection->query(
            $connection->insertFromSelect(
                $select,
                $this->entitiesHelper->getTable('inventory_source_item'),
                array_keys($inventoryValues),
                AdapterInterface::INSERT_IGNORE
            )
        );
        // Update stock details
        $this->updateStock($connection, $tmpTable);
    }

     /**
     * Import the medias
     *
     * @return void
     * @throws LocalizedException
     * @throws FileSystemExceptiont
     * @throws Zend_Db_Statement_Exception
     * @throws Exception
     */
    public function importMedia(): void
    {
        if (!$this->configHelper->isMediaImportEnabled()) {
            $this->setStatus(true);
            $this->jobExecutor->setMessage(__('Media import is not enabled'), $this->logger);

            return;
        }

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var array $gallery */
        $gallery = $this->configHelper->getMediaImportGalleryColumns();

        if (empty($gallery)) {
            $this->setStatus(true);
            $this->jobExecutor->setMessage(__('Akeneo Images Attributes is empty'), $this->logger);

            return;
        }

        $gallery = array_unique($gallery);

        /** @var string $table */
        $table = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string $columnIdentifier */
        $columnIdentifier = $this->entitiesHelper->getColumnIdentifier($table);
        /** @var array $data */
        $data = [
            $columnIdentifier => '_entity_id',
            'sku'             => 'identifier',
        ];

        /** @var mixed[] $stores */
        $stores = $this->storeHelper->getAllStores();
        /** @var string[] $dataToImport */
        $dataToImport = [];
        /** @var bool $valueFound */
        $valueFound = false;
        foreach ($gallery as $image) {
            if (!$connection->tableColumnExists($tmpTable, strtolower($image))) {
                // If not exist, check for each store if the field exist
                /**
                 * @var string  $suffix
                 * @var mixed[] $storeData
                 */
                foreach ($stores as $suffix => $storeData) {
                    if (!$connection->tableColumnExists(
                        $tmpTable,
                        strtolower($image) . self::SUFFIX_SEPARATOR . $suffix
                    )) {
                        continue;
                    }
                    $valueFound = true;
                    $data[$image . self::SUFFIX_SEPARATOR . $suffix] = strtolower($image) . self::SUFFIX_SEPARATOR . $suffix;
                    $dataToImport[strtolower($image) . self::SUFFIX_SEPARATOR . $suffix] = $suffix;
                }
                if (!$valueFound) {
                    $this->jobExecutor->setMessage(
                        __('Info: No value found in the current batch for the attribute %1', $image),
                        $this->logger
                    );
                }
                continue;
            }
            // Global image
            $data[$image] = strtolower($image);
            $dataToImport[$image] = null;
        }

        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($table);
        if ($rowIdExists) {
            $data[$columnIdentifier] = 'p.row_id';
        }

        /** @var Select $select */
        $select = $connection->select()->from($tmpTable, $data);

        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($select, []);
        }

        /** @var Mysql $query */
        $query = $connection->query($select);

        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $galleryAttribute */
        $galleryAttribute = $this->configHelper->getAttribute(
            BaseProductModel::ENTITY,
            'media_gallery'
        );
        /** @var string $galleryTable */
        $galleryTable = $this->entitiesHelper->getTable('catalog_product_entity_media_gallery');
        /** @var string $galleryValueTable */
        $galleryValueTable = $this->entitiesHelper->getTable(
            'catalog_product_entity_media_gallery_value'
        );
        /** @var string $galleryEntityTable */
        $galleryEntityTable = $this->entitiesHelper->getTable(
            'catalog_product_entity_media_gallery_value_to_entity'
        );
        /** @var string $productImageTable */
        $productImageTable = $this->entitiesHelper->getTable('catalog_product_entity_varchar');
        /** @var string[] $medias */
        $medias = [];

        /** @var array $row */
        while (($row = $query->fetch())) {
            /** @var int $positionCounter */
            $positionCounter = 0;
            /** @var array $files */
            $files = [];
            /**
             * @var string $image
             * @var string $suffix
             */
            foreach ($dataToImport as $image => $suffix) {
                if (!isset($row[$image])) {
                    continue;
                }

                if (!$row[$image]) {
                    continue;
                }

                if (!isset($medias[$row[$image]])) {
                    $medias[$row[$image]] = $this->akeneoClient->getProductMediaFileApi()->get(
                        $row[$image]
                    );
                }
                /** @var string $name */
                $name = $this->entitiesHelper->formatMediaName(basename($medias[$row[$image]]['code']));
                /** @var string $filePath */
                $filePath = $this->configHelper->getMediaFullPath($name);
                /** @var bool|string[] $databaseRecords */
                $databaseRecords = false;

                if (!$this->configHelper->mediaFileExists($filePath)) {
                    /** @var ResponseInterface $binary */
                    $binary = $this->akeneoClient->getProductMediaFileApi()->download($row[$image]);
                    /** @var string $imageContent */
                    $imageContent = $binary->getBody()->getContents();
                    $this->configHelper->saveMediaFile($filePath, $imageContent);
                }

                /** @var string $file */
                $file = $this->configHelper->getMediaFilePath($name);

                /** @var int $valueId */
                $valueId = $connection->fetchOne(
                    $connection->select()->from($galleryTable, ['value_id'])->where('value = ?', $file)
                );

                if (!$valueId) {
                    /** @var int $valueId */
                    $valueId = $connection->fetchOne(
                        $connection->select()->from($galleryTable, [new Expr('MAX(`value_id`)')])
                    );
                    ++$valueId;
                }

                /** @var array $data */
                $data = [
                    'value_id'     => $valueId,
                    'attribute_id' => $galleryAttribute->getId(),
                    'value'        => $file,
                    'media_type'   => ImageEntryConverter::MEDIA_TYPE_CODE,
                    'disabled'     => 0,
                ];
                $connection->insertOnDuplicate($galleryTable, $data, array_keys($data));

                /** @var array $data */
                $data = [
                    'value_id'        => $valueId,
                    $columnIdentifier => $row[$columnIdentifier],
                ];
                $connection->insertOnDuplicate($galleryEntityTable, $data, array_keys($data));

                /**
                 * @var string  $storeSuffix
                 * @var mixed[] $storeArray
                 */
                foreach ($stores as $storeSuffix => $storeArray) {
                    /** @var mixed[] $store */
                    foreach ($storeArray as $store) {
                        $disabled = 0;
                        if ($suffix) {
                            /** @var bool $storeIsInEnabledStores */
                            $storeIsInEnabledStores = false;
                            if ($suffix !== $storeSuffix) {
                                /** @var int $disabled */
                                $disabled = 1;
                                // Disable image for this store, only if this store is not in enabled stores list
                                /** @var mixed[] $enabledStores */
                                foreach ($stores[$suffix] as $enabledStores) {
                                    if ($enabledStores['store_code'] === $store['store_code']) {
                                        $storeIsInEnabledStores = true;
                                    }
                                }

                                if ($storeIsInEnabledStores) {
                                    continue;
                                }
                            }
                        }
                        // Get potential record_id from gallery value table
                        /** @var int $databaseRecords */
                        $databaseRecords = $connection->fetchOne(
                            $connection->select()->from($galleryValueTable, [new Expr('MAX(`record_id`)')])->where(
                                'value_id = ?',
                                $valueId
                            )->where(
                                'store_id = ?',
                                $store['store_id']
                            )->where(
                                $columnIdentifier . ' = ?',
                                $row[$columnIdentifier]
                            )
                        );
                        /** @var int $recordId */
                        $recordId = 0;
                        if (!empty($databaseRecords)) {
                            $recordId = $databaseRecords;
                        }

                        /** @var string[] $data */
                        $data = [
                            'value_id' => $valueId,
                            'store_id' => $store['store_id'],
                            $columnIdentifier => $row[$columnIdentifier],
                            'label' => '',
                            'position' => $positionCounter,
                            'disabled' => $disabled,
                        ];

                        $positionCounter++;

                        if ($recordId != 0) {
                            $data['record_id'] = $recordId;
                        }
                        $connection->insertOnDuplicate($galleryValueTable, $data, array_keys($data));

                        /** @var array $columns */
                        $columns = $this->configHelper->getMediaImportImagesColumns();

                        foreach ($columns as $column) {
                            /** @var string $columnName */
                            $columnName = $column['column'] . self::SUFFIX_SEPARATOR . $suffix;
                            /** @var mixed[] $mappings */
                            $mappings = $this->configHelper->getWebsiteMapping();
                            /** @var string|null $locale */
                            $locale = null;
                            /** @var string|null $scope */
                            $scope = null;
                            if ($suffix) {
                                if (str_contains($suffix, '-')) {
                                    /** @var string[] $suffixs */
                                    $suffixs = explode('-', $suffix);
                                    if (isset($suffixs[0])) {
                                        $locale = $suffixs[0];
                                    }
                                    if (isset($suffixs[1])) {
                                        $scope = $suffixs[1];
                                    }
                                } elseif (str_contains($suffix, '_')) {
                                    $locale = $suffix;
                                } else {
                                    $scope = $suffix;
                                }
                            }
 //($column['column']  !== $image && ( ($scope == "" &&  $locale == "") ) ) added  the condition if atribute common to all channel - NITS
                            foreach ($mappings as $mapping) {
                             if (((isset($scope, $locale)) && ($columnName !== $image || $store['website_code'] !== $mapping['website'] || $store['channel_code'] !== $scope || $store['lang'] !== $locale))
                                    || ((isset($scope)) && ($columnName !== $image || $store['website_code'] !== $mapping['website'] || $store['channel_code'] !== $scope))
                                    || ((isset($locale)) && ($columnName !== $image || $store['website_code'] !== $mapping['website'] || $store['lang'] !== $locale) )
                                    ||  ($column['column']  !== $image && ( ($scope == "" &&  $locale == "") ) )
                                ) {
                                    continue;
                                }
                                /** @var string[] $data */
                                $data = [
                                    'attribute_id'    => $column['attribute'],
                                    'store_id'        => $store['store_id'],
                                    $columnIdentifier => $row[$columnIdentifier],
                                    'value'           => $file,
                                ];
                                $connection->insertOnDuplicate($productImageTable, $data, array_keys($data));
                            }
                        }
                    }
                }

                $files[] = $file;
            }

            /** @var Select $cleaner */
            $cleaner = $connection->select()->from($galleryTable, ['value_id'])->where('value NOT IN (?)', $files);

            $connection->delete(
                $galleryEntityTable,
                [
                    'value_id IN (?)' => $cleaner,
                    $columnIdentifier . ' = ?' => $row[$columnIdentifier],
                ]
            );
            // Delete old value association with the imported product
            $connection->delete(
                $galleryValueTable,
                [
                    'value_id IN (?)'          => $cleaner,
                    $columnIdentifier . ' = ?' => $row[$columnIdentifier],
                ]
            );
        }
    }
    /**
     * Replace option code by id
     *
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function updateOption()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var string $websiteAttribute */
        $websiteAttribute = $this->configHelper->getWebsiteAttribute();
        /** @var string[] $except */
        $except = [
            'url_key',
            'country_of_manufacture',
            'quantity_and_stock_status',
            'visibility'
        ];
        if ($websiteAttribute) {
            $except[] = strtolower($websiteAttribute);
        }
        $except = array_merge($except, $this->excludedColumns);

        /** @var string $column */
        //update tax class option id by Nits
        foreach ($columns as $column) {
            if ($column === 'tax_class_id'){
                continue;
            }
            if (in_array($column, $except) || preg_match('/-unit/', $column)) {
                continue;
            }

            if (!$connection->tableColumnExists($tmpTable, $column)) {
                continue;
            }

            /** @var string[] $columnParts */
            $columnParts = explode('-', $column ?? '', 2);
            /** @var string $columnPrefix */
            $columnPrefix = reset($columnParts);
            $columnPrefix = sprintf('%s-', $columnPrefix);
            /** @var int $prefixLength */
            $prefixLength = strlen($columnPrefix) + 1;
            /** @var string $entitiesTable */
            $entitiesTable = $this->entitiesHelper->getTable('akeneo_connector_entities');

            // Sub select to increase performance versus FIND_IN_SET
            /** @var Select $subSelect */
            $subSelect = $connection->select()->from(
                ['c' => $entitiesTable],
                ['code' => sprintf('SUBSTRING(`c`.`code`, %s)', $prefixLength), 'entity_id' => 'c.entity_id']
            )->where(sprintf('c.code LIKE "%s%s"', $columnPrefix, '%'))->where('c.import = ?', 'option');

            // if no option no need to continue process
            if (!$connection->query($subSelect)->rowCount()) {
                continue;
            }

            //in case of multiselect
            /** @var string $conditionJoin */
            $conditionJoin = "IF ( locate(',', `" . $column . "`) > 0 , " . new Expr(
                "FIND_IN_SET(`c1`.`code`,`p`.`" . $column . "`) > 0"
            ) . ", `p`.`" . $column . "` = `c1`.`code` )";

            /** @var Select $select */
            $select = $connection->select()->from(
                ['p' => $tmpTable],
                ['identifier' => 'p.identifier', 'entity_id' => 'p._entity_id']
            )->joinInner(
                ['c1' => new Expr('(' . (string)$subSelect . ')')],
                new Expr($conditionJoin),
                [$column => new Expr('GROUP_CONCAT(`c1`.`entity_id` SEPARATOR ",")')]
            )->group('p.identifier');

            /** @var string $query */
            $query = $connection->insertFromSelect(
                $select,
                $tmpTable,
                ['identifier', '_entity_id', $column],
                AdapterInterface::INSERT_ON_DUPLICATE
            );

            $connection->query($query);
        }
    }
    /**
     * Set values to attributes
     *
     * @return void
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @throws Exception
     */
    public function setValues()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var string[] $attributeScopeMapping */
        $attributeScopeMapping = $this->entitiesHelper->getAttributeScopeMapping();
        /** @var array $stores */
        $stores = $this->storeHelper->getAllStores();

        // Format url_key columns
        /** @var string|array $matches */
        $matches = $this->configHelper->getAttributeMapping();
        if (is_array($matches)) {
            /** @var array $stores */
            $stores = $this->storeHelper->getAllStores();

            /** @var array $match */
            foreach ($matches as $match) {
                if (!isset($match['akeneo_attribute'], $match['magento_attribute'])) {
                    continue;
                }
                /** @var string $magentoAttribute */
                $magentoAttribute = $match['magento_attribute'];

                /**
                 * @var string $local
                 * @var string $affected
                 */
                foreach ($stores as $local => $affected) {
                    if ($magentoAttribute === 'url_key') {
                        $this->entitiesHelper->formatUrlKeyColumn($tmpTable, $local);
                    }
                }
            }
            $this->entitiesHelper->formatUrlKeyColumn($tmpTable);
        }

        /** @var string $adminBaseCurrency */
        $adminBaseCurrency = $this->storeManager->getStore()->getBaseCurrencyCode();
        /** @var mixed[] $values */
        $values = [
            0 => [
                'options_container' => '_options_container',
                'tax_class_id' => '_tax_class_id',
                'visibility' => '_visibility',
            ],
        ];

        $values[0]['status'] = '_status';

        // Set products status
        /** @var string $statusAttributeId */
        $statusAttributeId = $this->eavAttribute->getIdByCode('catalog_product', 'status');
        /** @var string $identifierColumn */
        $identifierColumn = $this->entitiesHelper->getColumnIdentifier('catalog_product_entity_int');
        /** @var string $productTable */
        $productTable = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string[] $pKeyColumn */
        $pKeyColumn = 'a._entity_id';
        /** @var string[] $columnsForStatus */
        $columnsForStatus = ['entity_id' => $pKeyColumn, '_entity_id', '_is_new' => 'a._is_new'];
        /** @var mixed[] $mappings */
        $mappings = $this->configHelper->getWebsiteMapping();
        /** @var string[] $columnsForCompleteness */
        $columnsForCompleteness = ['entity_id' => $pKeyColumn, '_entity_id'];
        /** @var string[] $mapping */
        foreach ($mappings as $mapping) {
            /** @var string $filterCompletenesses */
            $filterCompletenesses = 'a.completenesses_' . $mapping['channel'];
            if (!in_array($filterCompletenesses, $columnsForCompleteness)
                && $connection->tableColumnExists($tmpTable, 'completenesses_' . $mapping['channel'])
            ) {
                /** @var string[] $columnsForCompleteness */
                $columnsForCompleteness['completenesses_' . $mapping['channel']] = $filterCompletenesses;
            }
            if ($this->configHelper->getProductStatusMode() === StatusMode::ATTRIBUTE_PRODUCT_MAPPING) {
                $connection->addColumn(
                    $tmpTable,
                    'status-' . $mapping['channel'],
                    [
                        'type' => 'integer',
                        'length' => 11,
                        'default' => 2,
                        'COMMENT' => ' ',
                        'nullable' => false,
                    ]
                );
            }
        }

        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($productTable);
        if ($rowIdExists) {
            $pKeyColumn = 'p.row_id';
            $columnsForStatus['entity_id'] = $pKeyColumn;
        }

        /* Simple status management */
        /** @var Select $select */
        $select = $connection->select()->from(['a' => $tmpTable], $columnsForStatus);
        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($select, []);
        }

        $select->joinInner(
            ['b' => $this->entitiesHelper->getTable('catalog_product_entity_int')],
            $pKeyColumn . ' = b.' . $identifierColumn
        )->where('a._is_new = ?', 0)->where('a._status = ?', 1)->where('a._type_id = ?', 'simple')->where(
            'b.attribute_id = ?',
            $statusAttributeId
        );

        // Update existing simple status
        /** @var Zend_Db_Statement_Pdo $oldStatus */
        $oldStatus = $connection->query($select);
        /** @var string $status */
        $status = $this->configHelper->getProductActivation();
        if ($this->configHelper->getProductStatusMode() === StatusMode::STATUS_BASED_ON_COMPLETENESS_LEVEL) {
            /** @var Select $selectComplet */
            $selectComplet = $connection->select()->from(['a' => $tmpTable], $columnsForCompleteness)->where(
                'a._type_id = ?',
                'simple'
            );
            /** @var Zend_Db_Statement_Pdo $completQuery */
            $completQuery = $connection->query($selectComplet);
            /** @var string $completenessConfig */
            $completenessConfig = $this->configHelper->getEnableSimpleProductsPerWebsite();
            /** @var string[] $completenesses */
            $completenesses = [];
            while (($row = $completQuery->fetch())) {
                /** @var string[] $mapping */
                foreach ($mappings as $mapping) {
                    if ($connection->tableColumnExists(
                        $tmpTable,
                        'completenesses_' . $mapping['channel']
                    )
                    ) {
                        if (!$row['completenesses_' . $mapping['channel']]) {
                            continue;
                        }

                        /** @var string $map */
                        $map = $this->jsonSerializer->unserialize($row['completenesses_' . $mapping['channel']]);

                        if (!in_array($map, $completenesses)) {
                            $completenesses[$mapping['channel']] = $map;
                        }
                        /** @var string[] $completeness */
                        foreach ($completenesses[$mapping['channel']] as $completeness) {
                            $connection->addColumn(
                                $tmpTable,
                                'status-' . $completeness['scope'],
                                [
                                    'type' => 'integer',
                                    'length' => 11,
                                    'default' => 2,
                                    'COMMENT' => ' ',
                                    'nullable' => false,
                                ]
                            );
                            /** @var int $status */
                            $status = 1;
                            if ($completeness['data'] < $completenessConfig) {
                                $status = 2;
                            }

                            /** @var string[] $valuesToInsert */
                            $valuesToInsert = [
                                'status-' . $completeness['scope'] => $status,
                            ];

                            $connection->update(
                                $tmpTable,
                                $valuesToInsert,
                                ['_entity_id = ?' => $row['_entity_id']]
                            );
                        }
                    }
                }
            }
        } else {
            if ($this->configHelper->getProductStatusMode() === StatusMode::ATTRIBUTE_PRODUCT_MAPPING) {
                /** @var string $attributeCodeSimple */
                $attributeCodeSimple = strtolower($this->configHelper->getAttributeCodeForSimpleProductStatuses());
                $status = $this->setProductStatuses($attributeCodeSimple, $mappings, $connection, $tmpTable, 'simple');
            } 
        }

        // Update new simple status
        $connection->update(
            $tmpTable,
            ['_status' => $status],
            ['_is_new = ?' => 1, '_status = ?' => 1, '_type_id = ?' => 'simple']
        );

        /*  Configurable status management */
        $select = $connection->select()->from(['a' => $tmpTable], $columnsForStatus);
        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($select, []);
        }

        $select->joinInner(
            ['b' => $this->entitiesHelper->getTable('catalog_product_entity_int')],
            $pKeyColumn . ' = b.' . $identifierColumn
        )->where('a._is_new = ?', 0)->where('a._type_id = ?', 'configurable')->where(
            'b.attribute_id = ?',
            $statusAttributeId
        );

        /** @var Zend_Db_Statement_Pdo $oldConfigurableStatus */
        $oldConfigurableStatus = $connection->query($select);
        /** @var int $isNoError */
        $isNoError = 1;
        // Update existing configurable status scopable
        if ($this->configHelper->getProductStatusMode() === StatusMode::ATTRIBUTE_PRODUCT_MAPPING) {
            /** @var string $attributeCodeConfigurable */
            $attributeCodeConfigurable = strtolower(
                $this->configHelper->getAttributeCodeForConfigurableProductStatuses()
            );
            $isNoError = $this->setProductStatuses(
                $attributeCodeConfigurable,
                $mappings,
                $connection,
                $tmpTable,
                'configurable'
            );
        }
        while (($row = $oldConfigurableStatus->fetch())) {
            /** @var string $status */
            $status = $row['value'];
            // Update existing configurable status scopable
            if ($this->configHelper->getProductStatusMode() === StatusMode::STATUS_BASED_ON_COMPLETENESS_LEVEL) {
                foreach ($mappings as $mapping) {
                    if ($connection->tableColumnExists(
                        $tmpTable,
                        'status-' . $mapping['channel']
                    )
                    ) {
                        $connection->update(
                            $tmpTable,
                            ['status-' . $mapping['channel'] => $status],
                            ['_entity_id = ?' => $row['_entity_id']]
                        );
                    }
                }
            }
            // Update existing configurable status
            $valuesToInsert = [
                '_status' => $status,
            ];
            $connection->update($tmpTable, $valuesToInsert, ['_entity_id = ?' => $row['_entity_id']]);
        }

        /** @var string $status */
        $status = $this->configHelper->getProductActivation();
        if ($this->configHelper->getProductStatusMode() === StatusMode::STATUS_BASED_ON_COMPLETENESS_LEVEL) {
            // Update new configurable status scopable
            $status = $this->configHelper->getDefaultConfigurableProductStatus();
            foreach ($mappings as $mapping) {
                if ($connection->tableColumnExists(
                    $tmpTable,
                    'status-' . $mapping['channel']
                )
                ) {
                    $connection->update(
                        $tmpTable,
                        ['status-' . $mapping['channel'] => $status],
                        ['_is_new = ?' => 1, '_type_id = ?' => 'configurable']
                    );
                }
            }
        } else {
            if ($this->configHelper->getProductStatusMode() === StatusMode::ATTRIBUTE_PRODUCT_MAPPING) {
                $status = $isNoError;
            }
        }
        // Update new configurable status
        $connection->update(
            $tmpTable,
            ['_status' => $status],
            ['_is_new = ?' => 1, '_type_id = ?' => 'configurable']
        );

        /** @var mixed[] $taxClasses */
        $taxClasses = $this->configHelper->getProductTaxClasses();
        if (count($taxClasses)) {
            foreach ($taxClasses as $storeId => $taxClassId) {
                $values[$storeId]['tax_class_id'] = new Expr($taxClassId);
            }
        }else{
            $columns = $connection->describeTable($tmpTable);
            if (isset($columns['tax_class_id'])) {
                $sql = "SELECT tax_class_id ,identifier FROM $tmpTable";
                $taxValues = $connection->fetchAll($sql);
                foreach($taxValues as $taxValue)
                {
                    if(!empty($taxValue['tax_class_id'])){
                        $produtTax = json_decode($taxValue['tax_class_id'], true);
                        $storeId = 0;
                        $values[$storeId]['tax_class_id'] = new Expr($produtTax);
                    }
                }
            }
        }

        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));

        /** @var string $column */
        foreach ($columns as $column) {
            /** @var string[] $columnParts */
            $columnParts = explode('-', $column ?? '', 2);
            /** @var string $columnPrefix */
            $columnPrefix = $columnParts[0];

            if (in_array($columnPrefix, $this->excludedColumns) || preg_match('/-unit/', $column)) {
                continue;
            }

            if (!isset($attributeScopeMapping[$columnPrefix])) {
                // If no scope is found, attribute does not exist
                continue;
            }

            if (empty($columnParts[1])) {
                // No channel and no locale found: attribute scope naturally is Global
                $values[0][$columnPrefix] = $column;

                continue;
            }

            /** @var int $scope */
            $scope = (int)$attributeScopeMapping[$columnPrefix];
            if ($scope === ScopedAttributeInterface::SCOPE_GLOBAL
                && !empty($columnParts[1])
                && $columnParts[1] === $adminBaseCurrency
            ) {
                // This attribute has global scope with a suffix: it is a price with its currency
                // If Price scope is set to Website, it will be processed afterwards as any website scoped attribute
                $values[0][$columnPrefix] = $column;

                continue;
            }

            /** @var string $columnSuffix */
            $columnSuffix = $columnParts[1];
            if (!isset($stores[$columnSuffix])) {
                // No corresponding store found for this suffix
                continue;
            }

            /** @var mixed[] $affectedStores */
            $affectedStores = $stores[$columnSuffix];
            /** @var mixed[] $store */
            foreach ($affectedStores as $store) {
                // Handle website scope
                if ($scope === ScopedAttributeInterface::SCOPE_WEBSITE && !$store['is_website_default']) {
                    continue;
                }

                if ($scope === ScopedAttributeInterface::SCOPE_STORE || empty($store['siblings'])) {
                    $values[$store['store_id']][$columnPrefix] = $column;

                    continue;
                }

                /** @var string[] $siblings */
                $siblings = $store['siblings'];
                /** @var string $storeId */
                foreach ($siblings as $storeId) {
                    $values[$storeId][$columnPrefix] = $column;
                }
            }
        }

        /** @var int $entityTypeId */
        $entityTypeId = $this->configHelper->getEntityTypeId(BaseProductModel::ENTITY);

        /**
         * @var string $storeId
         * @var string[] $data
         */
        foreach ($values as $storeId => $data) {
            $this->entitiesHelper->setValues(
                $this->jobExecutor->getCurrentJob()->getCode(),
                'catalog_product_entity',
                $data,
                $entityTypeId,
                $storeId,
                AdapterInterface::INSERT_ON_DUPLICATE
            );
        }

        if ($this->configHelper->isAdvancedLogActivated()) {
            $this->logImportedEntities($this->logger, true, 'identifier');
        }
    }
    public function updateStock($connection, $tmpTable)
    {
         $columns = $connection->describeTable($tmpTable);
        if (isset($columns['identifier']) && isset($columns['quantity']) && isset($columns['quantity_and_stock_status']) && isset($columns['_type_id'])){     
            $tableName = $tmpTable;
            $select = $connection ->select()
                                ->from($tableName, ['identifier','quantity', 'quantity_and_stock_status','_type_id']);
            $tmpData = $connection->fetchAll($select);
            if(is_array($tmpData) && count($tmpData) > 0){
             foreach($tmpData as $tmpTableData){
                $identifier = $this->productRepositoryInterface->get($tmpTableData['identifier']);
                $productId =  $identifier['entity_id'];
                $stockItem = $this->stockRegistryInterface;
                $typeId = $tmpTableData['_type_id'];
                $stockStatus = $tmpTableData['quantity_and_stock_status'];
                $akeneoQty = $tmpTableData['quantity'];
                 if(empty($akeneoQty)){
                    $akeneoQty = 0;
                 }
                $productStockInfo = $stockItem->getStockItem($productId);
                $productStockInfo->setQty($akeneoQty);
                $productStockInfo->setIsInStock($stockStatus);
                 if ($typeId =='simple') { 
                       $productId = $this->grouped->getParentIdsByChild($productId);
                    if(($stockStatus == 1)){
                       ($akeneoQty == 0) || ($akeneoQty == -$akeneoQty)?$productStockInfo->setIsInStock(0):$productStockInfo->setIsInStock(1);
                         }
                   }
                  $stockItem->updateStockItemBySku($tmpTableData['identifier'], $productStockInfo);
                  //Update stock in Grouped product parent by NITS
                $parentProduct= $this->productFactory->create()->load($productId);
                $childProducts = $this->grouped->getAssociatedProducts($parentProduct);
                $allOutOfStock = true;
                foreach ($childProducts as $childProduct) {
                    $id = $childProduct->getEntityId();
                    $stockStatus =  $stockItem->getStockItem($id);
                    if ($stockStatus->getIsInStock()==true) {
                        $allOutOfStock = false;
                     break;
                     }
                   }
                 if ($allOutOfStock == true) {
                      $parentProduct->setStockData(['is_in_stock' => 0]);
                      $parentProduct->save();
                 }           
              }
            }
         }
    }
}