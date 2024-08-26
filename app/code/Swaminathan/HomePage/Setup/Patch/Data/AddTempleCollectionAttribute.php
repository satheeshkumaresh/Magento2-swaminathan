<?php

namespace Swaminathan\HomePage\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

class AddTempleCollectionAttribute implements DataPatchInterface
{

    const TEMPLE_COLLECTION = "temple_collection";

    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Category::ENTITY, self::TEMPLE_COLLECTION);
        $eavSetup->addAttribute(
            Category::ENTITY,
            self::TEMPLE_COLLECTION,
            [
                'type' => 'int',
                'label' => 'Temple Collection',
                'input' => 'select',
                'source' => Boolean::class,
                'default' => '1',
                'sort_order' => 10,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'General Information',
            ]
        );

        $attributeSet = $eavSetup->getDefaultAttributeSetId(Category::ENTITY);
        $attributeGroup = $eavSetup->getDefaultAttributeGroupId(Category::ENTITY);
        $eavSetup->addAttributeToGroup(
            Category::ENTITY,
            $attributeSet,
            $attributeGroup,
            $eavSetup->getAttributeId(Category::ENTITY, self::TEMPLE_COLLECTION),
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
