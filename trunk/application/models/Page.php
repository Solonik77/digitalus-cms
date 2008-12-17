<?php
class Page extends DSF_Db_Table 
{
    protected $_name = "pages";
 	protected $_defaultTemplate = "base_page";
 	protected $_defaultPageName = "New Page";
 	protected $_ignoredFields = array('update','version'); //these are the fields that are not saved as nodes
 	
 	public function getContent($uri)
 	{
 		$uriObj = new DSF_Uri($uri);
 		$pointer = $this->fetchPointer($uriObj->toArray());
 		$node = new ContentNode();
		//fetch the content nodes
		return $node->fetchContentArray($pointer, null, null, null);
 	}
    
    public function getCurrentUsersPages()
    {
        return null;
    }
    
    public function createPage($pageName, $parentId = 0, $contentTemplate = null ) 
    {
    	if(empty($pageName)) {
    		$pageName = $this->_defaultPageName;
    	}
    	
    	if($contentTemplate == null) {
    		$contentTemplate = $this->_defaultTemplate;
    	}
    	
    	$settings = new SiteSettings();
    	$makeMenuLinks = $settings->get('add_menu_links');
    	
    	//first create the new page
    	$data = array(
    		'name'  			=>	$pageName,
    		'content_template'	=>	$contentTemplate,
			'parent_id'			=>	$parentId,
    	    'show_on_menu'		=>  $makeMenuLinks				
    	);
    	$this->insert($data);
    	$id = $this->_db->lastInsertId();

    	$this->_flushCache();
    	
    	//return the new page
    	return $this->find($id)->current();
    	
    }
    
    public function getTemplate($pageId)
    {
    	$currentPage = $this->find($pageId)->current();
    	if($currentPage) {
    		return $currentPage->content_template;
    	}    	
    }
    
    public function open($pageId, $version = null)
    {
        if($version == null) {
            $version = $this->getDefaultVersion();
        }
        
    	$currentPage = $this->find($pageId)->current();
    	if($currentPage) {
    		$page = new stdClass();
    		$page->page = $currentPage;
    		
    		$node = new ContentNode();

    		//fetch the content nodes
    		$page->content = $node->fetchContentArray($pageId, null, null, $version);
    		
    		return $page;   		
    	}else{
    		return null;
    	}
    }
    
    public function pageExists(DSF_Uri $uri)
    {     
        if($this->_fetchPointer($uri->toArray(), 0) || $uri == null) {
            return true;
        }else{
            return false;
        }
    }
    
    public function edit($pageArray)
    {
    	$pageId = $pageArray['page_id'];
    	if(!$pageId) {
    		throw new Zend_Exception("Invalid Page: No key found for id");
    	}else{
    		unset($pageArray['page_id']);
    		$name = $pageArray['name'];
    		unset($pageArray['name']);
    		
    		//save the page details
    		$currentPage = $this->find($pageId)->current();
    		if(!$currentPage) {
    			throw new Zend_Exception("Could not load page");
    		}else{
    			$currentPage->name = $name;
    			$currentPage->save();
    		}
    		
    		//page version
    		if(isset($pageArray['version']) && !empty($pageArray['version'])) {
    		    $version = $pageArray['version'];
    		}else{
    		    $siteSettings = new SiteSettings();
    		    $version = $this->getDefaultVersion();
    		}    		
    		//update the content
    		$contentNode = new ContentNode();
    		
    		if(count($pageArray) > 0) {
    			foreach ($pageArray as $node => $content) {
    			    if(!in_array($node, $this->_ignoredFields)) { 
    				    $contentNode->set($pageId,$node, $content, $version);
    			    }
    			}
    		}
    		
    		$this->_flushCache();
    		return $this->open($pageId, $version);
    	}
    }
    
    public function getVersions($pageId)
    {
        $node = new ContentNode();
        return $node->getVersions("page_" . $pageId);
    }
    
    public function getDefaultVersion()
    {
        $settings = new SiteSettings();
        return $settings->get('default_language');
    }
    
    /**
     * returns the content type of the selected page
     *
     * @param int $pageId
     * @return string
     */
    public function getcontentTemplate($pageId)
    {
    	$page = $this->find($pageId)->current();
    	if($page) {
    		return $page->content_template;
    	}else{
    		return null;
    	}
    }
    
    public function getTitle($pageId)
    {
    	$titleParts[] = $this->getPageTitle($pageId);
    	$parents = $this->getParents($pageId);
    	if($parents){
    		foreach ($parents as $parent) {
    			$titleParts[] = $this->getPageTitle($parent->id);
    		}
    	}
    	
    	return array_reverse($titleParts);
    }
    
    public function getPageTitle($pageId)
    {
    	$mdlMeta = new MetaData();
    	$metaData = $mdlMeta->asArray($pageId);
    	if(isset($metaData['title'])) {
    		return $metaData['title'];
    	}else{
    		$page = $this->find($pageId)->current();
    		return $page->name;
    	}
    }
    
    public function deletePageById($pageId) 
    {
    	
    	$this->_flushCache();
    	$where[] = $this->_db->quoteInto("id = ?", $pageId);
    	$this->delete($where);
    }
    
    public function setDesign($pageId, $designId)
    {
    	$page = $this->find($pageId)->current();
    	if($page) {
    		$page->design = $designId;
    		$page->save();
    		return true;
    	}else{
    		return false;
    	}
    }
    
    public function getDesign($pageId)
    {
    	$page = $this->find($pageId)->current();
    	$designId = $page->design;
  		$mdlDesign = new Design();
  		$mdlDesign->setDesign($designId);
  		return $mdlDesign;
    }
    
    public function getPagesByDesign($designId)
    {
    	$select = $this->select();
    	$select->where("design = ?", $designId);
    	$select->order("name");
    	return $this->fetchAll($select);
    }
    
    /**
     * this function sets the related pages for a given page
     *
     * @param int $pageId
     * @param array $relatedPages
     * @return boolean
     */
    public function setRelatedPages($pageId, $relatedPages)
    {
    	if(is_array($relatedPages)) {
    		$data = array(
    			'related_pages'	=> implode(',', $relatedPages)
    		);
    		$where[] = $this->_db->quoteInto("id = ?", $pageId);
    		return $this->update($data, $where);
    	}
    }
    
    /**
     * this function returns an array of the ids of the pages which are related to $pageId
     * if asObject is set to true it will return a rowset instead
     *
     * @param int $pageId
     * @param boolean $asObject
     * @return mixex
     */
    public function getRelatedPages($pageId, $asObject = false)
    {
    	$row = $this->find($pageId)->current();
    	if($row) {
    		$pageArray = explode(',', $row->related_pages);
    		if(is_array($pageArray) && count($pageArray) > 0) {
    			if($asObject) {
    				//return the rowset
    				return $this->find($pageArray);
    			}else{
    				//return the array
    				return $pageArray;
    			}
    		}
    	}
    }
    
    // the following functions handle the site tree
    
    public function fetchPointer($uri)
    {
    	if(!is_array($uri)) {
    		//return home page
    		return $this->getHomePage();
    	}else{
    		return $this->_fetchPointer($uri);
    	}
    }
    
    /**
     * this function returns the children of a selected page
     * you can pass it a page id (integer) or a page object
     * you can optionally pass it an array of where clauses
     *
     * @param mixed $page
     * @param array $where
     * @return zend_db_rowset
     */
    public function getChildren($page, $where = array())
    {
        $id = $this->_getPageId($page);
                
        $where[] = $this->_db->quoteInto("parent_id = ?", $id);
        $order = "position ASC";
        return $this->fetchAll($where, $order);
    }
    
    public function getPages($treeItems)
    {
    	 if($treeItems->count() > 0) {
            foreach ($treeItems as $row) {
                $arrIds[] = $row->id;
            }
            return $this->find($arrIds);
        }else{
            return null;
        }
    }
    
    /**
     * this function returns the parent of the selected page
     * you can pass it a page id (integer) or a page object
     *
     * @param mixed $page
     * @return zend_db_row
     */
    public function getParent($page)
    {
        $id = $this->_getPageId($page);
     	$result = $this->find($id)->current();
        return $this->find($result->parent_id)->current();
    }
    
    /**
     * this function returns an array of the parents of the current page
     *
     * @param mixed $page
     * @return unknown
     */
    public function getParents($page)
    {
    	$parents = null;
    	while($parent = $this->getParent($page)) {
    		$parents[] = $parent;
    		$page = $parent;
    	}
    	if(is_array($parents)) {
    		return $parents;	
    	}
    }
    
    /**
     * this function tests whether the page is a child of another page
     *
     * @param mixed $page
     * @param mixed $parent
     * @return boolean
     */
    public function isChildOf($page, $parent)
    {
        $pageId = $this->_getPageId($page);
        $parentId = $this->_getPageId($parent);
        
        $where[] = $this->_db->quoteInto("id = ?", $pageId);
        $where[] = $this->_db->quoteInto("parent_id = ?", $parentId);
        
        if($this->fetchRow($where)) {
            return true;
        }else{
            return false;
        }
        
    }
    
    /**
     * this function tests whether the page is a parent of the other page
     *
     * @param mixed $page
     * @param mixed $parent
     * @return boolean
     */
    public function isParentOf($page, $child)
    {
        $pageId = $this->_getPageId($page);
        $childId = $this->_getPageId($child);
        
        $where[] = $this->_db->quoteInto("id = ?", $childId);
        $where[] = $this->_db->quoteInto("parent_id = ?", $pageId);
        
        if($this->fetchRow($where)) {
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * this function tests whether the page has children
     *
     * @param mixed $page
     * @return boolean
     */
    public function hasChildren($page)
    {
        $pageId = $this->_getPageId($page);
        
        $where[] = $this->_db->quoteInto("parent_id = ?", $pageId);
        
        if($this->fetchRow($where)) {
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * this function returns the siblings for a selected page
     * you can pass it a page id (integer) or a page object
     * you can optionally pass it an array of where clauses
     *
     * @param mixed $page
     * @param array $where
     * @return zend_db_rowset
     */
    public function getSiblings($page, $where = array())
    {
        $id = $this->_getPageId($page);
        $parent = $this->getParent($page);
        
        //do not return the current page
        $where[] = $this->_db->quoteInto("id != ?", $id);
        
        return $this->getChildren($parent, $where);
    }
    
	/**
	 * returns the current site index
	 *
	 * @return array
	 */
	public function getIndex($rootId = 0)
	{
		if(empty($this->_index)) {
		    $this->_indexPages($rootId);
		}
		return $this->_index;
	}
    
    /**
	 * creates loads the page index
	 * if you pass the optional parentId the index will start with this page
	 * if not it will index the whole site
	 * 
	 * @param integer $parentId
	 */
	private function _indexPages($parentId = 0, $path = null, $pathSeparator = '/')
	{
		if($this->hasChildren($parentId)){
		    $children = $this->getChildren($parentId);
		    foreach ($children as $child) {
		    	//check to see if the child has children
		    	$tmpPath = $path . $child->name;
		
		    	//add the child
		    	$this->_index[$child->id] = $tmpPath;
		    	
		    	$this->_indexPages($child->id, $tmpPath . $pathSeparator, $pathSeparator);		    	
		    }		    
		}
	}
    
    /**
     * moves a page from one parent to another
     *
     * @param mixed $page
     * @param mixed $parent
     */
    public function movePage($page, $parent)
    {
		$this->_flushCache();
        $id = $this->_getPageId($page);
        $parentId = $this->_getPageId($parent);
        $row = $this->find($id)->current();
        if($row) {
            $row->parent_id = $parentId;
            $row->save();
        }
    }
    
    /**
     * this function removes all of the children from a page
     *
     * @param mixed $page
     */
    public function removeChildren($page)
    {
		$this->_flushCache();
		$children = $this->getChildren($page);
		if($children) {
		    foreach ($children as $child) {
		        $this->removeChildren($child, true);
		    }   
		   $where = array();
		   $where[] = $this->_db->quoteInto("parent_id = ?", $this->_getPageId($page));
		   $this->delete($where);
		}
           
    }
    
    /**
     * removes the selected page and all of its children
     *
     * @param mixed $page
     */
    public function removePage($page)
    {
        $id = $this->_getPageId($page);
        $where[] = $this->_db->quoteInto("id = ?", $id);
        $this->delete($where);
        $this->removeChildren($page);
    }
    
    public function makeHomePage($pageId)
    {
    	$data['is_home_page'] = 0;
    	$this->update($data);
    	
    	unset($data);
    	$data['is_home_page'] = 1;
        $where[] = $this->_db->quoteInto("id = ?", $pageId);
        $this->update($data, $where);    	
    }
    
    static function isHomePage($page)
    {
        if(is_object($page) && $page->page->is_home_page == 1) {
            return true;
        }else{
            return false;  
        }
    }
    
    public function getHomePage()
    {
    	$where[] = "is_home_page = 1";
    	$row = $this->fetchRow($where);
    	return $row->id;
    }
    
    /**
     * if page is an object then this returns its id property
     * otherwise it returns its integer value
     *
     * @param mixed $page
     * @return unknown
     */
    private function _getPageId($page)
    {
        if(is_object($page)){
            return $page->id;
        }else{
            return intval($page);
        }
    }
    
    
    /**
     * returns the next position of the children of a page
     *
     * @param int $parentId
     * @return int
     */
    private function _getNextPosition($parentId)
    {
    	$last = $this->_getLastPosition($parentId);
    	$next = intval($last) + 1;
    	return $next;
    }
    
    /**
     * returns the last (highest) position of the children of a page
     *
     * @param int $parentId
     * @return int
     */
    private function _getLastPosition($parentId)
    {
    	$where[] = $this->_db->quoteInto('parent_id = ?', $parentId);
    	$order = "position DESC";
    	$row = $this->fetchRow($where, $order);
    	return $row->position;
    }
    
    
    
    private function _flushCache()
    {
    	$cache = Zend_Registry::get('cache');
       	$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('filetree'));
       	$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('tree'));
    }
    
    private function _fetchPointer($uri, $parent = 0)
    {
        if(is_array($uri)) {
        	foreach ($uri as $uriPart) {
        		//fetch the pointer to the current uri part
        		$pointer = $this->_getPageByLabel($uriPart, $parent);
        		
        		//if the page was not found then return null
        		if(null == $pointer) {
        			return null;
        		}
        		
        		//set the parent id to the current pointer to traverse down the tree
        		$parent = $pointer;
        	}
        	
        	return $pointer;
        }else{
            return $this->getHomePage();
        }
    }
    
    private function _getPageByLabel($label, $parent = 0)
    {
    	if($label != 'p') {
	    	$where[] = $this->_db->quoteInto("(label = ? OR name = ?)", $label);
	    	$where[] = $this->_db->quoteInto("parent_id = ?", $parent);
	    	$page = $this->fetchRow($where);
	    	if($page) {
				return $page->id;
			}else{
				return null;
			}
    	}
    }
}