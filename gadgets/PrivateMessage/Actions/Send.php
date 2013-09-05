<?php
/**
 * PrivateMessage Gadget
 *
 * @category    Gadget
 * @package     PrivateMessage
 * @author      Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class PrivateMessage_Actions_Send extends Jaws_Gadget_HTML
{
    /**
     * Display Send page
     *
     * @access  public
     * @return  void
     */
    function Send()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }

        $this->gadget->CheckPermission('SendMessage');
        $this->AjaxMe('site_script.js');

//        $date = $GLOBALS['app']->loadDate();
//        $model = $GLOBALS['app']->LoadGadget('PrivateMessage', 'Model', 'Send');
//        $usrModel = new Jaws_User;
        $id = jaws()->request->get('id', 'get');

        $tpl = $this->gadget->loadTemplate('Send.html');
        $tpl->SetBlock('send');

        // forward a message?
        if (!empty($id) && $id > 0) {
            $model = $GLOBALS['app']->LoadGadget('PrivateMessage', 'Model', 'Message');
            $message = $model->GetMessage($id, true);
            $tpl->SetVariable('body', $message['body']);
            $tpl->SetVariable('subject', $message['subject']);

            if (!empty($message['attachments'])) {
                $tpl->SetVariable('lbl_attachments', _t('PRIVATEMESSAGE_MESSAGE_ATTACHMENTS'));
                foreach ($message['attachments'] as $file) {
                    $tpl->SetBlock('send/file');
                    $tpl->SetVariable('lbl_hints_count', _t('PRIVATEMESSAGE_FILE_HINTS_COUNT'));
                    $tpl->SetVariable('lbl_file_size', _t('PRIVATEMESSAGE_MESSAGE_FILE_SIZE'));
                    $tpl->SetVariable('file_name', $file['user_filename']);
                    $tpl->SetVariable('file_size', Jaws_Utils::FormatSize($file['file_size']));
                    $tpl->SetVariable('hints_count', $file['hints_count']);
                    $tpl->SetVariable('file_id', $file['id']);

                    $tpl->SetVariable('file_download_link', $file['user_filename']);
                    $file_url = $this->gadget->urlMap('Attachment',
                        array(
                            'uid' => $message['from'],
                            'mid' => $id,
                            'aid' => $file['id'],
                        ));
                    $tpl->SetVariable('file_download_link', $file_url);

                    $tpl->ParseBlock('send/file');
                }
            }
        }


        $tpl->SetVariable('lbl_recipient_users', _t('PRIVATEMESSAGE_MESSAGE_RECIPIENT_USERS'));
        $tpl->SetVariable('lbl_recipient_groups', _t('PRIVATEMESSAGE_MESSAGE_RECIPIENT_GROUPS'));
        $tpl->SetVariable('lbl_subject', _t('PRIVATEMESSAGE_MESSAGE_SUBJECT'));
        $tpl->SetVariable('lbl_body', _t('PRIVATEMESSAGE_MESSAGE_BODY'));
        $tpl->SetVariable('lbl_attachments', _t('PRIVATEMESSAGE_MESSAGE_ATTACHMENTS'));
        $tpl->SetVariable('lbl_send', _t('PRIVATEMESSAGE_SEND'));
        $tpl->SetVariable('lbl_back', _t('PRIVATEMESSAGE_BACK'));
        $tpl->SetVariable('lbl_file', _t('PRIVATEMESSAGE_FILE'));
        $tpl->SetVariable('lbl_add_file', _t('PRIVATEMESSAGE_ADD_ANOTHER_FILE'));
        $tpl->SetVariable('back_url', $this->gadget->urlMap('Inbox'));

        $tpl->SetVariable('icon_add', STOCK_ADD);

        $tpl->ParseBlock('send');
        return $tpl->Get();
    }

    /**
     * Send a message
     *
     * @access  public
     * @return  void
     */
    function SendMessage()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(403);
        }
        $this->gadget->CheckPermission('SendMessage');

        $attachments = array();
        $post = jaws()->request->get(
            array('recipient_users', 'recipient_groups', 'subject', 'body', 'selected_files:array'), 'post');
        $user = $GLOBALS['app']->Session->GetAttribute('user');
        $model = $GLOBALS['app']->LoadGadget('PrivateMessage', 'Model', 'Message');

        $pm_dir = JAWS_DATA . 'pm' . DIRECTORY_SEPARATOR . $user . DIRECTORY_SEPARATOR;
        if (!file_exists($pm_dir)) {
            if (!Jaws_Utils::mkdir($pm_dir)) {
                $GLOBALS['app']->Session->PushResponse(
                    _t('GLOBAL_ERROR_FAILED_CREATING_DIR', $pm_dir),
                    'PrivateMessage.Message',
                    RESPONSE_ERROR
                );

                Jaws_Header::Location($this->gadget->urlMap('Inbox'));
            }
        }

        // detect message have attachment(s)?
        if(!empty($_FILES['file1']['name'])) {
            $files = Jaws_Utils::UploadFiles(
                $_FILES,
                $pm_dir,
                '',
                'php,php3,php4,php5,phtml,phps,pl,py,cgi,pcgi,pcgi5,pcgi4,htaccess',
                null
            );

            if (Jaws_Error::IsError($files)) {
                $GLOBALS['app']->Session->PushResponse(
                    $files->GetMessage(),
                    'PrivateMessage.Message',
                    RESPONSE_ERROR
                );
            } else if ($files === false || count($files)<1) {
                $GLOBALS['app']->Session->PushResponse(
                    _t('PRIVATEMESSAGE_ERROR_NO_FILE_UPLOADED'),
                    'PrivateMessage.Message',
                    RESPONSE_ERROR
                );
            } else {
                for ($i = 1; $i <= count($files); $i++) {
                    if (!isset($files['file'.$i])) {
                        continue;
                    }
                    $user_filename = $files['file'.$i][0]['user_filename'];
                    $host_filename = $files['file'.$i][0]['host_filename'];
                    if (!empty($host_filename)) {
                        $attachments[] = array(
                            'user_filename'=> $user_filename,
                            'host_filename'=> $host_filename,
                            'file_size'=> $_FILES['file'.$i]['size'],
                        );
                    }
                }
            }

        }

        // check forward with pre attachments
        if (!empty($post['selected_files'])) {
            $aModel = $GLOBALS['app']->LoadGadget('PrivateMessage', 'Model', 'Attachment');

            foreach ($post['selected_files'] as $attachment_id) {
                if ($attachment_id < 1) {
                    continue;
                }
                $attachment_info = $aModel->GetMessageAttachment($attachment_id);
                $message_info = $model->GetMessage($attachment_info['message_id']);
                $filepath = JAWS_DATA . 'pm' . DIRECTORY_SEPARATOR . $message_info['from'] . DIRECTORY_SEPARATOR .
                    $attachment_info['host_filename'];
                $filepath_info = pathinfo($filepath);

                $host_filename = Jaws_Utils::RandomText(15, true, false, true) . '.' . $filepath_info['extension'];
                $new_filepath = JAWS_DATA . 'pm' . DIRECTORY_SEPARATOR . $user . DIRECTORY_SEPARATOR .
                    $host_filename;
                $cres = Jaws_Utils::copy($filepath, $new_filepath);
                if ($cres) {
                    $attachments[] = array(
                        'user_filename' => $attachment_info['user_filename'],
                        'host_filename' => $host_filename,
                        'file_size' => $attachment_info['file_size'],
                    );
                }
            }
        }

        $res = $model->SendMessage($user, $post, $attachments);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushResponse(
                $res->GetMessage(),
                'PrivateMessage.Message',
                RESPONSE_ERROR
            );
        }

        Jaws_Header::Location($this->gadget->urlMap('Inbox'));
    }

}