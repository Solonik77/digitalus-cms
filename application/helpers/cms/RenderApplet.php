<?php
class Zend_View_Helper_RenderApplet
{

	/**
	 * comments
	 */
	public function RenderApplet($applet){
	    $config = Zend_Registry::get('config');
	    
	    //create a new instance of view
	    $appletView = new Zend_View();
	    $appletView->setScriptPath($config->view->applet->path . '/' . $applet);
	    $appletView->setHelperPath($config->view->applet->path . '/' . $applet, 'DSF_Applet');
	    
	    //tell the applet about where it is
	    $appletView->page = $this->view->page;
	    $appletView->pageObj = $this->view->pageObj;
	    
	    //run the code behind
	    if(file_exists($config->view->applet->path . '/' . $applet . '/' . $applet . '.php')){
	       $appletView->$applet();
	    }
	    return $appletView->render($applet . '.phtml');
	}
	
    /**
     * Set this->view object
     *
     * @param  Zend_this->view_Interface $this->view
     * @return Zend_this->view_Helper_DeclareVars
     */
    public function setview(Zend_view_Interface $view)
    {
        $this->view = $view;
        return $this;
    }
}