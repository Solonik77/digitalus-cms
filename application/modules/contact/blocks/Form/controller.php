<?php
class Contact_Block_Form_Controller extends DSF_Module_Block_Controller 
{
    public function init()
    {
        if($this->isPost())
        {
            $pageId = $this->_request->getParam('pageId');
            $p = new Properties($pageId);
            $moduleData = $p->getGroup('modules')->items;
            $settings = new SiteSettings();
            
            $m = new DSF_View_Message();
            $e = new DSF_View_Error();
            
            $sender = DSF_Filter_Post::get('email');
            $name = DSF_Filter_Post::get('name');
            $subject = DSF_Filter_Post::get('subject');
            $data['name'] = $name;
            $data['sender'] = $sender;
            $data['message'] = DSF_Filter_Post::get('message');
            
            if(DSF_Filter_Post::int('copyMe') == 1)
            {
                $cc = $sender;
            }            
            
    		$mail = new DSF_Mail();
            if($mail->send($moduleData->params['email'], array($sender), $subject, 'contactForm', $data, $cc))
            {
                $m->add($moduleData->params['successMessage']);
            }else{
                $e->add($moduleData->params['errorMessage']);
            }
            
            //autoresponse
            if(!empty($moduleData->params['autoresponse_message']))
            {
                unset($data);
                $data['autoresponse'] = $moduleData->params['autorespond'];
                $response = new DSF_Mail();
                $response->send(
                    $sender, 
                    array($moduleData->params['email'], $moduleData->params['recipient']), 
                    $moduleData->params['autoresponse_subject'], 
                    'autoresponder',
                    $data);
            }
        
        }
		
        $this->view->recipient = $this->_request->getParam('recipient');
    }
    
}