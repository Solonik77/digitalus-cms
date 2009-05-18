<?php
class Admin_ReportController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->breadcrumbs = array(
           $this->view->getTranslation('Site Settings') => $this->getFrontController()->getBaseUrl() . '/admin/site'
        );
    }

    public function indexAction()
    {
    }

    /**
     * render the traffic report
     *
     */
    public function trafficAction()
    {
        $breadcrumbLabel = $this->view->getTranslation('Traffic Report');
        $this->view->breadcrumbs[$breadcrumbLabel] = $this->getFrontController()->getBaseUrl() . '/admin/report/traffic';
        $this->view->toolbarLinks['Add to my bookmarks'] = $this->getFrontController()->getBaseUrl() . '/admin/index/bookmark'
            . '/url/admin_report_traffic'
            . '/label/' . $this->view->getTranslation('Report') . ':' . $this->view->getTranslation('Traffic');
        $log = new Model_TrafficLog();
        $this->view->hitsThisWeek = $log->getLogByDay();
        $this->view->hitsByWeek   = $log->getLogByWeek();
    }

    /**
     * render the admin access log
     *
     */
    public function adminAccessAction()
    {
        $breadcrumbLabel = $this->view->getTranslation('Admin Access Report');
        $this->view->breadcrumbs[$breadcrumbLabel] = $this->getFrontController()->getBaseUrl() . '/admin/report/admin-access';
        $this->view->toolbarLinks['Add to my bookmarks'] = $this->getFrontController()->getBaseUrl() . '/admin/index/bookmark/url'
            . '/admin_report_admin-access'
            . '/label/' . $this->view->getTranslation('Report') . ':' . $this->view->getTranslation('Access');
        $log = new Model_TrafficLog();
        $this->view->accessLog = $log->adminAccess();
    }
}