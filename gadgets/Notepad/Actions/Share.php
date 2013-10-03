<?php
/**
 * Notepad Gadget
 *
 * @category    Gadget
 * @package     Notepad
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Layout->AddHeadLink('gadgets/Notepad/resources/site_style.css');
class Notepad_Actions_Share extends Jaws_Gadget_HTML
{
    /**
     * Builds sharing UI
     *
     * @access  public
     * @return  string  XHTML UI
     */
    function ShareNote()
    {
        $tpl = $this->gadget->loadTemplate('Share.html');
        $tpl->SetBlock('share');

        // Fetch note
        $id = (int)jaws()->request->fetch('id', 'get');
        $model = $GLOBALS['app']->LoadGadget('Notepad', 'Model', 'Notepad');
        $uid = (int)$GLOBALS['app']->Session->GetAttribute('user');
        $note = $model->GetNote($id, $uid);
        if (Jaws_Error::IsError($note) || empty($note)) {
            $tpl->SetVariable('text', _t('NOTEPAD_ERROR_RETRIEVING_DATA'));
            $tpl->SetVariable('type', 'response_error');
        }

        $this->AjaxMe('site_script.js');
        $tpl->SetVariable('id', $id);
        $tpl->SetVariable('note_title', $note['title']);
        $tpl->SetVariable('UID', (int)$GLOBALS['app']->Session->GetAttribute('user'));
        $tpl->SetVariable('title', _t('NOTEPAD_SHARE'));
        $tpl->SetVariable('lbl_users', _t('NOTEPAD_USERS'));

        // User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $uModel = new Jaws_User();
        $groups = $uModel->GetGroups(true, 'title');
        if (!Jaws_Error::IsError($groups)) {
            $combo =& Piwi::CreateWidget('Combo', 'sys_groups');
            $combo->AddEvent(ON_CHANGE, 'toggleUsers(this.value)');
            $combo->AddOption(_t('NOTEPAD_ALL_USERS'), 0);
            foreach ($groups as $group) {
                $combo->AddOption($group['title'], $group['id']);
            }
            $tpl->SetVariable('groups', $combo->Get());
        }
        $tpl->SetVariable('lbl_groups', _t('NOTEPAD_GROUPS'));

        // Note users
        $model = $GLOBALS['app']->LoadGadget('Notepad', 'Model', 'Share');
        $combo =& Piwi::CreateWidget('Combo', 'note_users');
        $combo->SetSize(10);
        $users = $model->GetNoteUsers($id);
        if (!Jaws_Error::IsError($users) && !empty($users)) {
            foreach ($users as $user) {
                if ($user['user_id'] != $uid) {
                    $combo->AddOption($user['nickname'].' ('.$user['username'].')', $user['user_id']);
                }
            }
        }
        $tpl->SetVariable('note_users', $combo->Get());
        $tpl->SetVariable('lbl_note_users', _t('NOTEPAD_SHARED_FOR'));

        // Actions
        $tpl->SetVariable('lbl_ok', _t('GLOBAL_OK'));
        $tpl->SetVariable('lbl_cancel', _t('GLOBAL_CANCEL'));
        $tpl->SetVariable('url_back', $this->gadget->urlMap('Notepad'));

        $tpl->ParseBlock('share');
        return $tpl->Get();
    }

    /**
     * Fetches list of system users
     *
     * @access  public
     * @return  array   Array of users or an empty array
     */
    function GetUsers()
    {
        $gid = (int)jaws()->request->fetch('gid');
        if ($gid === 0) {
            $gid = false;
        }
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $uModel = new Jaws_User();
        $users = $uModel->GetUsers($gid, null, 1);
        if (Jaws_Error::IsError($users)) {
            return array();
        }
        return $users;
    }

    /**
     * Shares note for passed users
     *
     * @access  public
     * @return  array   Response array
     */
    function UpdateShare()
    {
        $id = (int)jaws()->request->fetch('id');
        $model = $GLOBALS['app']->LoadGadget('Notepad', 'Model', 'Notepad');
        $user = (int)$GLOBALS['app']->Session->GetAttribute('user');

        // Validate note
        $note = $model->GetNote($id, $user);
        if (Jaws_Error::IsError($note) || empty($note)) {
            return $GLOBALS['app']->Session->GetResponse(
                _t('NOTEPAD_ERROR_RETRIEVING_DATA'),
                RESPONSE_ERROR
            );
        }

        // Verify owner
        if ($note['user'] != $user) {
            $GLOBALS['app']->Session->GetResponse(
                _t('NOTEPAD_ERROR_NO_PERMISSION'),
                RESPONSE_ERROR
            );
        }

        $users = jaws()->request->fetch('users');
        $users = empty($users)? array() : explode(',', $users);
        $model = $GLOBALS['app']->LoadGadget('Notepad', 'Model', 'Share');
        $res = $model->UpdateNoteUsers($id, $users);
        if (Jaws_Error::IsError($res)) {
            return $GLOBALS['app']->Session->GetResponse(
                _t('NOTEPAD_ERROR_NOTE_SHARE'),
                RESPONSE_ERROR
            );
        }

        return $GLOBALS['app']->Session->GetResponse(
            _t('NOTEPAD_NOTICE_SHARE_UPDATED')
        );
    }
}