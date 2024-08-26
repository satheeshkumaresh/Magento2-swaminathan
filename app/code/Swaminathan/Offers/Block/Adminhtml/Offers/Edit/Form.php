<?php
 
namespace Swaminathan\Offers\Block\Adminhtml\Offers\Edit;


/**
 * Adminhtml Add New Row Form.
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Swaminathan\Offers\Model\Status $options,
        \Swaminathan\CmsPlpPdp\Helper\UrlHelper $urlHelper,
        array $data = []
    ) 
    {
        $this->_options = $options;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->urlHelper = $urlHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $model = $this->_coreRegistry->registry('row_data');
        $form = $this->_formFactory->create(
            ['data' => [
                            'id' => 'edit_form', 
                            'enctype' => 'multipart/form-data', 
                            'action' => $this->getData('action'), 
                            'method' => 'post'
                        ]
            ]
        );

        $form->setHtmlIdPrefix('offers_');
        if ($model->getEntityId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __(''), 'class' => 'fieldset-wide']
            );
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __(''), 'class' => 'fieldset-wide']
            );
        }

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Offer Title'),
                'id' => 'title',
                'title' => __('Offer Title'),
                'class' => 'required-entry',
                'required' => true,
                'style' => 'width:500px',

            ]
        );

        $fieldset->addField(
            'percentage',
            'text',
            [
                'name' => 'percentage',
                'label' => __('Offer Percentage'),
                'id' => 'percentage',
                'title' => __('Offer Percentage'),
                'class' => 'required-entry validate-number',
                'required' => true,
                'style' => 'width:500px',
                'maxlength' => 2,
            ]
        );
        $fieldset->addField(
            'category',
            'select',
            [
                'name' => 'category',
                'label' => __('Category'),
                'id' => 'category',
                'title' => __('Limitted Tag'),
                'values' => $this->_options->getCategories(),
                'class' => 'category',
                'style' => 'width:500px',
                'required' => true,
            ]
        );

        if (is_array($model->getData('image'))) {
            $model->setData(
                'image',
                $model->getData('image')['value']
            );
        }
        
        $fieldset->addField(
            'image',
            'image',
            [
                'name' => 'offer_image',
                'label' => __('Image Upload'),
                'title' => __('Image Upload'),
                'class' => 'required-entry required-file',
                'required'  => true,
                'value' => $model->getData('image'),
                'note' => __('Note : Please upload image 215 x 215 (width x height) size with jpg, jpeg, gif, png format'),
            ]
        )->setAfterElementHtml(
            '
        <script>
            require([
                 "jquery",
            ], function($){
                $(document).ready(function () {
                    $("#edit_form").submit(function(){
                        if($("#edit_form").valid()) {
                            $("#save, #save_and_continue").prop("disabled", true);
                            return;
                        } 
                    });
                    if($("#offers_image").attr("value")){
                        $("#offers_image").removeClass("required-file");
                    }else{
                        $("#offers_image").addClass("required-file");
                    }
                    $( "#offers_image" ).attr( "accept", "image/x-png,image/gif,image/jpeg,image/jpg,image/png,image/webp" );
                    $("#offers_image_image").click(function(){
                        window.open("'.$mediaUrl . $model->getData('image').'");
                        return false;
                    });
                });
              });
       </script>'
        );

        $fieldset->addField(
            'valid_from',
            'date',
            [
                'name' => 'valid_from',
                'label' => __('Valid From'),
                'date_format' => $dateFormat,
                'time_format' => 'HH:mm:ss',
                'class' => 'validate-date validate-date-range date-range-custom_theme-from',
                'class' => 'required-entry',
                'style' => 'width:500px',
                'required' => true,

            ]
        );

        $fieldset->addField(
            'valid_to',
            'date',
            [
                'name' => 'valid_to',
                'label' => __('Valid To'),
                'date_format' => $dateFormat,
                'time_format' => 'HH:mm:ss',
                'class' => 'validate-date validate-date-range date-range-custom_theme-from',
                'class' => 'required-entry',
                'style' => 'width:500px',
                'required' => true,
            ]
        );
        
        $fieldset->addField(
            'limitted_tag',
            'select',
            [
                'name' => 'limitted_tag',
                'label' => __('Add Limtted Tag ?'),
                'id' => 'limitted_tag',
                'title' => __('Limitted Tag'),
                'values' => $this->_options->getLimittedArray(),
                'class' => 'limitted_tag',
                'style' => 'width:150px',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'name' => 'is_active',
                'label' => __('Enabled'),
                'id' => 'limitted_tag',
                'title' => __('Enabled'),
                'values' => $this->_options->getStatusArray(),
                'class' => 'is_active',
                'required' => true,
                'style' => 'width:150px',

            ]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}