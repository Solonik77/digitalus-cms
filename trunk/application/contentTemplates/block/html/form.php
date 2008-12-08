<?php
class Block_Html_Form extends DSF_Content_Form_Abstract {
    public function setup() {        
        $content = $this->form->createElement( 'textarea', 'content' );
    
        $content->setRequired(true)
            ->setLabel('Content Block')
            ->setAttrib('class',"med");
        
        // Add elements to form:
        $this->form->addElement($content)
            ->addElement('submit','update',array('label'=>'Update Page'));
    }
}