<?php
class Digitalus_Form_Element_Tinymce extends Zend_Form_Element_Textarea
{
    public function init()
    {
        $this->setAttrib('class', 'tinymce');
        $this->setDecorators(array('Composite'));
    }
}