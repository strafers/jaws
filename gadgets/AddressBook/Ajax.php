<?php
/**
 * AddressBook  AJAX API
 *
 * @category   Ajax
 * @package    AddressBook
 * @author     HamidReza Aboutalebi <hamid@aboutalebi.com>
 * @copyright  2013 Jaws Development Group
 */
class AddressBook_Ajax extends Jaws_Gadget_HTML
{
    /**
     * Get user information
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function GetUserInfo()
    {
        $uid = (int) jaws()->request->fetch('uid');
        $uModel = new Jaws_User();
        $userInfo = $uModel->GetUser($uid, true, true);
        if (empty($userInfo['avatar'])) {
            $userInfo['avatar'] = $GLOBALS['app']->getSiteURL('/gadgets/AddressBook/images/photo128px.png');
        } else {
            $userInfo['avatar'] = $GLOBALS['app']->getDataURL(). 'avatar/'. $userInfo['avatar'];
        }
        return $userInfo;
    }

    /**
     * Filter AddressBook and return result
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function FilterAddress()
    {
        $rqst = jaws()->request->fetch(array('gid:int', 'term'));
        $gadgetHTML = $GLOBALS['app']->LoadGadget('AddressBook', 'HTML', 'AddressBook');
        return $gadgetHTML->AddressList(0, (int) $rqst['gid'], $rqst['term']); // TODO: Send request user id
    }

    /**
     * Generate download link for vCard format
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function GetVCardDownloadLink()
    {
        $rqst = jaws()->request->fetch(array('gid:int', 'term'));
        return $this->gadget->urlMap('VCardBuild', array('group' => $rqst['gid'], 'term' => $rqst['term']));
    }

    function DeleteAddress()
    {
        //return $GLOBALS['app']->Session->GetResponse($res->getMessage(), RESPONSE_ERROR);
        return $GLOBALS['app']->Session->GetResponse('afasfasf', RESPONSE_ERROR);
    }
}