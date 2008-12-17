<?php
require('./application/modules/event/models/Calendar.php');
class Zend_View_Helper_SelectCalendar
{	
    /**
     */
	public function SelectCalendar($name, $value = null, $attribs = null)
	{
   
	    $p = new Event_Calendar();
	    $calendars = $p->fetchAll(null, 'title');
	    if($calendars)
	    {
	        foreach ($calendars as $calendar)
	        {
	            $options[$calendar->id] = $calendar->title;
	        }
	        if(is_array($options))
	        {
	            return $this->view->formSelect($name, $value, $attribs, $options);
	        }
	    }
	    
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