<?php
/**
 * Comments Gadget
 *
 * @category   Gadget
 * @package    Comments
 * @author     Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright  2012-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class Comments_Actions_Comments extends Comments_HTML
{

    /**
     * Displays a block of pages belongs to the specified group
     *
     * @access  public
     * @param   int    $perPage
     * @param   int    $orderBy
     * @internal param string $gadget
     * @internal param mixed $limit limit recent comments (int)
     * @return  string  XHTML content
     */
    function Comments($perPage = 0, $orderBy = 0)
    {
        $tpl = new Jaws_Template('gadgets/Comments/templates/');
        $tpl->Load('Comments.html');
        $tpl->SetBlock('new_comment');
        $tpl->SetVariable('title', _t('COMMENTS_COMMENTS'));

        $allow_comments_config = $this->gadget->registry->fetch('allow_comments', 'Comments');
        switch ($allow_comments_config) {
            case 'restricted':
                $allow_comments_config = $GLOBALS['app']->Session->Logged();
                break;

            default:
                $allow_comments_config = $allow_comments_config == 'true';
        }

        if ($allow_comments_config) {
            $tpl->SetBlock('new_comment/fieldset');
            $tpl->SetVariable('base_script', BASE_SCRIPT);
            $tpl->SetVariable('message', _t('COMMENTS_MESSAGE'));
            $tpl->SetVariable('send', _t('COMMENTS_SEND'));

            $name  = $GLOBALS['app']->Session->GetCookie('visitor_name');
            $email = $GLOBALS['app']->Session->GetCookie('visitor_email');
            $url   = $GLOBALS['app']->Session->GetCookie('visitor_url');

            $rand = rand();
            $tpl->SetVariable('rand', $rand);
            if (!$GLOBALS['app']->Session->Logged()) {
                $tpl->SetBlock('new_comment/fieldset/info-box');
                $url_value = empty($url)? 'http://' : Jaws_XSS::filter($url);
                $tpl->SetVariable('url', _t('GLOBAL_URL'));
                $tpl->SetVariable('urlvalue', $url_value);
                $tpl->SetVariable('rand', $rand);
                $tpl->SetVariable('name', _t('GLOBAL_NAME'));
                $tpl->SetVariable('namevalue', isset($name) ? Jaws_XSS::filter($name) : '');
                $tpl->SetVariable('email', _t('GLOBAL_EMAIL'));
                $tpl->SetVariable('emailvalue', isset($email) ? Jaws_XSS::filter($email) : '');
                $tpl->ParseBlock('new_comment/fieldset/info-box');
            }

            $mPolicy = $GLOBALS['app']->LoadGadget('Policy', 'Model');
            if (false !== $captcha = $mPolicy->LoadCaptcha()) {
                $tpl->SetBlock('new_comment/fieldset/captcha');
                $tpl->SetVariable('captcha_lbl', $captcha['label']);
                $tpl->SetVariable('captcha_key', $captcha['key']);
                $tpl->SetVariable('captcha', $captcha['captcha']);
                if (!empty($captcha['entry'])) {
                    $tpl->SetVariable('captcha_entry', $captcha['entry']);
                }
                $tpl->SetVariable('captcha_msg', $captcha['description']);
                $tpl->ParseBlock('new_comment/fieldset/captcha');
            }

            $tpl->ParseBlock('new_comment/fieldset');
        } else {
            $tpl->SetBlock('new_comment/unregistered');
            $tpl->SetVariable('msg', _t('GLOBAL_ERROR_ACCESS_RESTRICTED',
                $GLOBALS['app']->Map->GetURLFor('Users', 'LoginBox'),
                $GLOBALS['app']->Map->GetURLFor('Users', 'Registration')));
            $tpl->ParseBlock('new_comment/unregistered');
        }

        if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Comments')) {
            $tpl->SetBlock('new_comment/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('new_comment/response');
        }

        $tpl->SetVariable('comments_messages', $this->GetMessages($perPage, $orderBy));
        $tpl->ParseBlock('new_comment');

        return $tpl->Get();
    }


    /**
     * Displays a block of pages belongs to the specified group
     *
     * @access  public
     * @param   string  $gadget
     * @param   string  $action
     * @param   int     $reference
     * @param   string  $redirect_to
     * @return  string  XHTML content
     */
    function ShowCommentsForm($gadget, $action, $reference, $redirect_to)
    {
        $tpl = new Jaws_Template('gadgets/Comments/templates/');
        $tpl->Load('CommentForm.html');
        $tpl->SetBlock('comment_form');
        $tpl->SetVariable('title', _t('COMMENTS_COMMENTS'));


        $tpl->SetVariable('gadget', $gadget);
        $tpl->SetVariable('action', $action);
        $tpl->SetVariable('reference', $reference);
        $tpl->SetVariable('redirect_to', $redirect_to);

        $allow_comments_config = $this->gadget->registry->fetch('allow_comments', 'Comments');
        switch ($allow_comments_config) {
            case 'restricted':
                $allow_comments_config = $GLOBALS['app']->Session->Logged();
                break;

            default:
                $allow_comments_config = $allow_comments_config == 'true';
        }

        if ($allow_comments_config) {
            $tpl->SetVariable('base_script', BASE_SCRIPT);
            $tpl->SetVariable('message', _t('COMMENTS_MESSAGE'));
            $tpl->SetVariable('send', _t('COMMENTS_SEND'));

            $name  = $GLOBALS['app']->Session->GetCookie('visitor_name');
            $email = $GLOBALS['app']->Session->GetCookie('visitor_email');
            $url   = $GLOBALS['app']->Session->GetCookie('visitor_url');

            $rand = rand();
            $tpl->SetVariable('rand', $rand);
            if (!$GLOBALS['app']->Session->Logged()) {
                $tpl->SetBlock('comment_form/info-box');
                $url_value = empty($url)? 'http://' : Jaws_XSS::filter($url);
                $tpl->SetVariable('url', _t('GLOBAL_URL'));
                $tpl->SetVariable('urlvalue', $url_value);
                $tpl->SetVariable('rand', $rand);
                $tpl->SetVariable('name', _t('GLOBAL_NAME'));
                $tpl->SetVariable('namevalue', isset($name) ? Jaws_XSS::filter($name) : '');
                $tpl->SetVariable('email', _t('GLOBAL_EMAIL'));
                $tpl->SetVariable('emailvalue', isset($email) ? Jaws_XSS::filter($email) : '');
                $tpl->ParseBlock('comment_form/info-box');
            }

            $mPolicy = $GLOBALS['app']->LoadGadget('Policy', 'Model');
            if (false !== $captcha = $mPolicy->LoadCaptcha()) {
                $tpl->SetBlock('comment_form/captcha');
                $tpl->SetVariable('captcha_lbl', $captcha['label']);
                $tpl->SetVariable('captcha_key', $captcha['key']);
                $tpl->SetVariable('captcha', $captcha['captcha']);
                if (!empty($captcha['entry'])) {
                    $tpl->SetVariable('captcha_entry', $captcha['entry']);
                }
                $tpl->SetVariable('captcha_msg', $captcha['description']);
                $tpl->ParseBlock('comment_form/captcha');
            }

        } else {
            $tpl->SetBlock('comment_form/unregistered');
            $tpl->SetVariable('msg', _t('GLOBAL_ERROR_ACCESS_RESTRICTED',
                $GLOBALS['app']->Map->GetURLFor('Users', 'LoginBox'),
                $GLOBALS['app']->Map->GetURLFor('Users', 'Registration')));
            $tpl->ParseBlock('comment_form/unregistered');
        }

        $tpl->SetVariable('url2', _t('GLOBAL_SPAMCHECK_EMPTY'));
        $tpl->SetVariable('url2_value',  '');

        if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Comments')) {
            $tpl->SetBlock('comment_form/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('comment_form/response');
        }

        $tpl->ParseBlock('comment_form');

        return $tpl->Get();
    }


    /**
     * Displays a block of pages belongs to the specified group
     *
     * @access  public
     * @param   string  $gadget          Gadget name
     * @param   string  $action          Gadget action
     * @param   int     $reference
     * @param   array   $pagination_data
     * @param   int     $perPage
     * @param   int     $orderBy
     * @internal param string $gadget
     * @internal param mixed $limit limit recent comments (int)
     * @return  string  XHTML content
     */
    function ShowComments($gadget, $action, $reference, $pagination_data, $perPage = null, $orderBy = 0)
    {
        $request =& Jaws_Request::getInstance();
        $rqst = $request->get(array('order', 'page'), 'get');
        $page = empty($rqst['page'])? 1 : (int)$rqst['page'];

        if(!empty($rqst['order'])) {
            $orderBy = (int)$rqst['order'];
        }

        if(empty($perPage)) {
            $perPage = $this->gadget->registry->fetch('comments_per_page');
        }

        $model = $GLOBALS['app']->LoadGadget('Comments', 'Model');
        $comments = $model->GetComments(strtolower($gadget), $perPage, $reference, $action, array(COMMENT_STATUS_APPROVED), false,
            ($page - 1) * $perPage, $orderBy);
        $comments_count = $model->HowManyComments(strtolower($gadget), $action, $reference);

        $tpl = new Jaws_Template('gadgets/Comments/templates/');
        $tpl->Load('Comments.html');
        $tpl->SetBlock('comments');

        $tpl->SetVariable('gadget', $gadget);

        $objDate = $GLOBALS['app']->loadDate();
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $usrModel = new Jaws_User;
        if (!Jaws_Error::IsError($comments) && $comments != null) {
            foreach ($comments as $entry) {
                $tpl->SetBlock('comments/entry');

                $tpl->SetVariable('postedby_lbl', _t('COMMENTS_POSTEDBY'));

                if ($entry['user_registered_date']) {
                    $tpl->SetBlock('comments/entry/registered_date');
                    $tpl->SetVariable('registered_date_lbl', _t('COMMENTS_USERS_REGISTERED_DATE'));
                    $tpl->SetVariable('registered_date', $objDate->Format($entry['user_registered_date'], 'd MN Y'));
                    $tpl->ParseBlock('comments/entry/registered_date');
                }

                if (!empty($entry['username'])) {
                    // user's profile
                    $tpl->SetVariable(
                        'user_url',
                        $GLOBALS['app']->Map->GetURLFor(
                            'Users',
                            'Profile',
                            array('user' => $entry['username'])
                        )
                    );

                } else {
                    $tpl->SetVariable('user_url', Jaws_XSS::filter($entry['url']));
                }

                $nickname = empty($entry['nickname']) ? $entry['name'] : $entry['nickname'];
                $email = empty($entry['user_email']) ? $entry['email'] : $entry['user_email'];

                $tpl->SetVariable('nickname', Jaws_XSS::filter($nickname));
                $tpl->SetVariable('email', Jaws_XSS::filter($email));
                $tpl->SetVariable('username', Jaws_XSS::filter($entry['username']));
                // user's avatar
                $tpl->SetVariable(
                    'avatar',
                    $usrModel->GetAvatar(
                        $entry['avatar'],
                        $entry['email'],
                        80
                    )
                );
                $tpl->SetVariable('insert_time', $objDate->Format($entry['createtime']));
                $tpl->SetVariable('insert_time_iso', $objDate->ToISO($entry['createtime']));
                $tpl->SetVariable('message', Jaws_String::AutoParagraph($entry['msg_txt']));

                $reply_url = & Piwi::CreateWidget('Link', _t('COMMENTS_REPLY_TO_COMMENT'),
                                                  'javascript:replyComment();');
                $tpl->SetVariable('reply-link', $reply_url->Get());

                $tpl->ParseBlock('comments/entry');
            }
        }

        $pagination_data['params']['order'] = $orderBy;

        // page navigation
        $this->GetPagesNavigation(
            $tpl,
            'comments',
            $page,
            $perPage,
            $comments_count,
            _t('COMMENTS_COMMENTS_COUNT', $comments_count),
            $gadget,
            $pagination_data['action'],
            $pagination_data['params']
        );

        $tpl->ParseBlock('comments');
        return $tpl->Get();

    }

    /**
     * Get the comments messages list
     *
     * @access  public
     * @return  string  XHTML template content
     */
    function GetMessages()
    {
        $request =& Jaws_Request::getInstance();
        $rqst = $request->get(array('order','perpage', 'page'), 'get');
        $page = empty($rqst['page'])? 1 : (int)$rqst['page'];

        if(!empty($rqst['perpage'])) {
            $perPage = (int)$rqst['perpage'];
            $orderBy = (int)$rqst['order'];
        } else {
            $perPage = $this->gadget->registry->fetch('comments_per_page');
            $orderBy = 0;
        }

        $model = $GLOBALS['app']->LoadGadget('Comments', 'Model');
        $comments = $model->GetComments('comments', $perPage, null, null, array(COMMENT_STATUS_APPROVED), false,
                                              ($page - 1) * $perPage, $orderBy);
        $comments_count = $model->HowManyFilteredComments('comments', '', '', 1);

        $tpl = new Jaws_Template('gadgets/Comments/templates/');
        $tpl->Load('Comments.html');
        $tpl->SetBlock('comments');

        $objDate = $GLOBALS['app']->loadDate();
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $usrModel = new Jaws_User;
        if (!Jaws_Error::IsError($comments) && $comments != null) {
            foreach ($comments as $entry) {
                $tpl->SetBlock('comments/entry');

                $tpl->SetVariable('postedby_lbl', _t('COMMENTS_POSTEDBY'));

                if ($entry['user_registered_date']) {
                    $tpl->SetBlock('comments/entry/registered_date');
                    $tpl->SetVariable('registered_date_lbl', _t('COMMENTS_USERS_REGISTERED_DATE'));
                    $tpl->SetVariable('registered_date', $objDate->Format($entry['user_registered_date'], 'd MN Y'));
                    $tpl->ParseBlock('comments/entry/registered_date');
                }

                if (!empty($entry['username'])) {
                    // user's profile
                    $tpl->SetVariable(
                        'user_url',
                        $GLOBALS['app']->Map->GetURLFor(
                            'Users',
                            'Profile',
                            array('user' => $entry['username'])
                        )
                    );

                } else {
                    $tpl->SetVariable('user_url', Jaws_XSS::filter($entry['url']));
                }

                $nickname = empty($entry['nickname']) ? $entry['name'] : $entry['nickname'];
                $email = empty($entry['user_email']) ? $entry['email'] : $entry['user_email'];

                $tpl->SetVariable('nickname', Jaws_XSS::filter($nickname));
                $tpl->SetVariable('email', Jaws_XSS::filter($email));
                $tpl->SetVariable('username', Jaws_XSS::filter($entry['username']));
                // user's avatar
                $tpl->SetVariable(
                    'avatar',
                    $usrModel->GetAvatar(
                        $entry['avatar'],
                        $entry['email'],
                        80
                    )
                );
                $tpl->SetVariable('insert_time', $objDate->Format($entry['createtime']));
                $tpl->SetVariable('insert_time_iso', $objDate->ToISO($entry['createtime']));
                $tpl->SetVariable('message', Jaws_String::AutoParagraph($entry['msg_txt']));

                $tpl->ParseBlock('comments/entry');
            }
        }

        // page navigation
        $this->GetPagesNavigation(
            $tpl,
            'comments',
            $page,
            $perPage,
            $comments_count,
            _t('COMMENTS_COMMENTS_COUNT', $comments_count),
            'Comments',
            array('perpage'=>$perPage,
                  'order'=>$orderBy )
        );

        $tpl->ParseBlock('comments');
        return $tpl->Get();
    }

    /**
     * Adds a new entry to the comments, sets cookie with user data and redirects to main page
     *
     * @access  public
     * @return  void
     */
    function PostMessage()
    {
        $request =& Jaws_Request::getInstance();
        $post  = $request->get(array('message', 'name', 'email', 'url', 'url2', 'requested_gadget',
                                    'requested_action', 'reference', 'redirect_to'), 'post');

        $redirectTo = str_replace('&amp;', '&', $post['redirect_to']);
        $model = $GLOBALS['app']->LoadGadget('Comments', 'Model');

        if ($GLOBALS['app']->Session->Logged()) {
            $post['name']  = $GLOBALS['app']->Session->GetAttribute('nickname');
            $post['email'] = $GLOBALS['app']->Session->GetAttribute('email');
            $post['url']   = $GLOBALS['app']->Session->GetAttribute('url');
        }

        if (trim($post['message']) == ''|| trim($post['name']) == '') {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('COMMENTS_DONT_SEND_EMPTY_MESSAGES'), 'Comments');
            Jaws_Header::Location($redirectTo);
        }

        /* lets check if it's spam
        * it's rather common that spam engines
        * fill out all inputs and this one is hidden
        * via CSS so not many engines are smart enough
        * to not fill this out
        */
        if (!empty($post['url2'])) {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('COMMENTS_FAILED_SPAM_CHECK_MESSAGES'), 'Comments');
            Jaws_Header::Location($redirectTo);
        }

        $mPolicy = $GLOBALS['app']->LoadGadget('Policy', 'Model');
        $resCheck = $mPolicy->CheckCaptcha();
        if (Jaws_Error::IsError($resCheck)) {
            $GLOBALS['app']->Session->PushSimpleResponse($resCheck->getMessage(), 'Comments');
            Jaws_Header::Location($redirectTo);
        }

        $permalink = $GLOBALS['app']->GetSiteURL();
        $status = $this->gadget->registry->fetch('default_comment_status');
        if ($GLOBALS['app']->Session->GetPermission('Comments', 'ManageComments')) {
            $status = COMMENT_STATUS_APPROVED;
        }

        $res = $model->NewComment(
            strtolower($post['requested_gadget']), $post['reference'], $post['requested_action'], $post['name'],
            $post['email'], $post['url'], $post['message'], $_SERVER['REMOTE_ADDR'], $permalink, $status
        );


        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushSimpleResponse($res->getMessage(), 'Comments');
        } else {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('GLOBAL_MESSAGE_SENT'), 'Comments');
        }

        Jaws_Header::Location($redirectTo);
    }

}