<?php
/**
 * GetTranslation helper
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://digitalus-media.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@digitalus-media.com so we can send you a copy immediately.
 *
 * @author      Forrest Lyman
 * @category    Digitalus
 * @package     Digitalus_View
 * @subpackage  Helper
 * @copyright   Copyright (c) 2007 - 2009,  Digitalus Media USA (digitalus-media.com)
 * @license     http://digitalus-media.com/license/new-bsd     New BSD License
 * @version     $Id:$
 * @link        http://www.digitaluscms.com
 * @since       Release 1.5.0
 */

/**
 * @see Zend_View_Helper_Abstract
 */
require_once 'Zend/View/Helper/Abstract.php';

/**
 * GetTranslation helper
 *
 * @author      Forrest Lyman
 * @copyright   Copyright (c) 2007 - 2009,  Digitalus Media USA (digitalus-media.com)
 * @license     http://digitalus-media.com/license/new-bsd     New BSD License
 * @version     Release: @package_version@
 * @link        http://www.digitaluscms.com
 * @since       Release 1.5.0
 * @uses        viewHelper  Digitalus_View_Helper_GetCurrentLanguage
 * @uses        viewHelper  Digitalus_View_Helper_GetRequest
 */
class Digitalus_View_Helper_Internationalization_GetTranslation extends Zend_View_Helper_Abstract
{
    /**
     * This helper returns the translation for the passed key,
     * it will optionally add the controller and action to the key
     *
     * example: controller_action_page_title
     *
     * @return  unknown
     */
    public function getTranslation($key, $locale = null, $viewInstance = null)
    {
        if ($viewInstance !== null) {
            $this->setview($viewInstance);
        }
        $adapter = Zend_Registry::get('Zend_Translate');
        $moduleName = $this->view->getRequest()->getModuleName();
        $currentLanguage = $this->view->getCurrentLanguage();
        if ($locale != null) {
#            $this->view->translate()->setLocale($locale);
        } else if ($moduleName != 'admin' && $adapter->isAvailable($currentLanguage)) {
#            $this->view->translate()->setLocale($currentLanguage);
        }
        return $this->view->translate($key);
    }
}